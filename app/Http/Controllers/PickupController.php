<?php

namespace App\Http\Controllers;

use App\Support\CounterHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Handing an order over, on a phone.
 *
 * The other half of the counter. Receiving an item is photographed, and so is
 * handing one over: completing an order is what credits the buyer's reward
 * points, and the photograph is the proof it really changed hands. The desk
 * computer rarely has a usable camera, so "Complete handover" offers the same
 * carry-on-with-a-phone the turnover does - a QR that opens this one order's
 * completion screen, already authorised.
 *
 * Nothing here decides anything the API would not: it forwards the photo and
 * the completion, and repeats whatever the API says about an order that is
 * not ready to be handed over.
 */
class PickupController extends Controller
{
    /** The cache namespace for these, kept apart from the item turnovers. */
    private const KIND = 'pickup';

    private function api(): string
    {
        return rtrim(config('services.fati.url', 'https://fati-api.alertaraqc.com/api'), '/');
    }

    // ── The desk computer ────────────────────────────────────────────────

    /**
     * Mint a handoff for a phone and hand back the URL its QR should carry.
     * POST /counter/pickup-handoff
     */
    public function handoff(Request $request): JsonResponse
    {
        $validated = $request->validate(['transaction_id' => ['required', 'integer', 'min:1']]);

        $token = (string) session('admin_token', '');
        $orderId = (int) $validated['transaction_id'];

        $lookup = $this->order($token, $orderId);

        if (!$lookup['ok']) {
            return response()->json(['message' => $lookup['message']], $lookup['status'] ?: 502);
        }

        $order = $lookup['order'] ?? [];

        if (($order['status'] ?? '') === 'completed') {
            return response()->json(['message' => 'This order has already been handed over.'], 422);
        }

        // The buyer's own pickup code when the order carries one, so the admin
        // app's scanner recognises the link as an order it knows and opens its
        // native counter screen instead of a browser tab.
        $ref = $order['qr_code'] ?: ('ORDER-' . $orderId);
        $expiresAt = now()->addMinutes(CounterHandoff::MINUTES);

        $key = CounterHandoff::mint(self::KIND, [
            'transaction_id' => $orderId,
            'ref' => $ref,
            'token' => $token,
            'admin' => trim(session('admin_first_name', '') . ' ' . session('admin_last_name', '')),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return response()->json([
            'url' => route('pickup.show', ['ref' => $ref]) . '?k=' . $key,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in' => CounterHandoff::MINUTES * 60,
            'order' => $order,
        ]);
    }

    // ── The phone the QR was scanned with ────────────────────────────────

    /**
     * The completion screen itself.
     * GET /pickup/{ref}?k=...
     */
    public function show(Request $request, string $ref)
    {
        $handoff = CounterHandoff::resolve(self::KIND, $request, $ref);

        if ($handoff === null) {
            return response()->view('turnover.expired', [], 410);
        }

        $lookup = $this->order($handoff['token'], (int) $handoff['transaction_id']);

        return response()->view('pickup.show', [
            'order' => $lookup['ok'] ? ($lookup['order'] ?? []) : null,
            'loadError' => $lookup['ok'] ? null : $lookup['message'],
            'key' => CounterHandoff::keyFrom($request),
            'ref' => $ref,
            'admin' => $handoff['admin'] ?? '',
        ]);
    }

    /**
     * Hand it over.
     * POST /pickup/{ref}
     */
    public function complete(Request $request, string $ref): JsonResponse
    {
        $handoff = CounterHandoff::resolve(self::KIND, $request, $ref);

        if ($handoff === null) {
            return response()->json([
                'message' => 'This counter link has expired. Open "Complete handover" on the console again for a new QR.',
            ], 410);
        }

        $request->validate(['handover_photo' => ['required', 'image', 'max:8192']]);

        $token = (string) $handoff['token'];
        $orderId = (int) $handoff['transaction_id'];
        $photo = $request->file('handover_photo');

        try {
            $response = Http::withToken($token)
                ->accept('application/json')
                ->timeout(120)
                ->attach(
                    'handover_photo',
                    file_get_contents($photo->getRealPath()),
                    $photo->getClientOriginalName() ?: 'handover.jpg',
                )
                ->post($this->api() . '/admin/transactions/' . $orderId . '/complete');
        } catch (\Throwable $e) {
            Log::error('Pickup completion failed', ['order' => $orderId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'The store API could not be reached: ' . $e->getMessage()], 503);
        }

        $payload = $response->json() ?? [];

        if (!$response->successful()) {
            return response()->json([
                'message' => $payload['message'] ?? ('The order could not be completed (HTTP ' . $response->status() . ').'),
            ], $response->status() === 422 ? 422 : ($response->status() === 409 ? 409 : 502));
        }

        // One handoff, one handover.
        CounterHandoff::forget(self::KIND, $request);

        return response()->json([
            'ok' => true,
            'message' => $payload['message'] ?? 'Order completed.',
            'order' => $payload['data'] ?? null,
        ]);
    }

    /**
     * One order, as admin sees it.
     *
     * @return array{ok: bool, order: ?array, message: string, status: int}
     */
    private function order(string $token, int $orderId): array
    {
        if ($token === '') {
            return ['ok' => false, 'order' => null, 'message' => 'Your session has ended. Sign in again.', 'status' => 401];
        }

        try {
            $response = Http::withToken($token)
                ->accept('application/json')
                ->timeout(30)
                ->get($this->api() . '/admin/transactions/' . $orderId);
        } catch (\Throwable $e) {
            return ['ok' => false, 'order' => null, 'message' => 'The store API could not be reached: ' . $e->getMessage(), 'status' => 503];
        }

        $payload = $response->json() ?? [];

        if (!$response->successful()) {
            return [
                'ok' => false,
                'order' => null,
                'message' => $payload['message'] ?? ('The order could not be loaded (HTTP ' . $response->status() . ').'),
                'status' => $response->status(),
            ];
        }

        return ['ok' => true, 'order' => $payload['data'] ?? [], 'message' => '', 'status' => 200];
    }
}
