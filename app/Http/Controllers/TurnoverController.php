<?php

namespace App\Http\Controllers;

use App\Services\TurnoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Receiving an item at the counter - on the desk computer, or on a phone.
 *
 * The console can mark an item acquired on its own, but the two things that
 * make a turnover accountable are photographs: the item in hand, and the
 * seller holding their cash. A desk computer rarely has a usable camera, so
 * "Mark as acquired" also offers to carry on somewhere that does. It mints a
 * short-lived handoff, draws it as a QR, and whichever phone scans it opens
 * the item's turnover page already authorised for that one item.
 *
 * The handoff, not the phone, holds the admin's API token: it stays on this
 * server for the few minutes the handoff lives, and the phone only ever
 * carries the random key that points at it.
 */
class TurnoverController extends Controller
{
    /** Long enough to walk to the counter, short enough to be worthless later. */
    private const HANDOFF_MINUTES = 30;

    public function __construct(private readonly TurnoverService $turnover)
    {
    }

    // ── The desk computer ────────────────────────────────────────────────

    /**
     * Mint a handoff for a phone and hand back the URL its QR should carry.
     * POST /counter/handoff
     */
    public function handoff(Request $request): JsonResponse
    {
        $validated = $request->validate(['item_id' => ['required', 'integer', 'min:1']]);

        $token = (string) session('admin_token', '');
        $itemId = (int) $validated['item_id'];

        $lookup = $this->turnover->item($token, $itemId);

        if (!$lookup['ok']) {
            return response()->json(['message' => $lookup['message']], $lookup['status'] ?: 502);
        }

        $item = $lookup['item'] ?? [];

        if (($item['status'] ?? '') === 'sold') {
            return response()->json(['message' => 'This item has already been sold.'], 422);
        }

        // The seller's own turnover code when the item still carries one, so
        // the admin app's scanner recognises the link as an item it knows and
        // opens its native turnover screen instead of a browser tab.
        $ref = $item['qr_code'] ?: ('ITEM-' . $itemId);
        $key = Str::random(48);
        $expiresAt = now()->addMinutes(self::HANDOFF_MINUTES);

        Cache::put($this->cacheKey($key), [
            'item_id' => $itemId,
            'ref' => $ref,
            'token' => $token,
            'admin' => trim(session('admin_first_name', '') . ' ' . session('admin_last_name', '')),
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return response()->json([
            'url' => route('turnover.show', ['ref' => $ref]) . '?k=' . $key,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in' => self::HANDOFF_MINUTES * 60,
            'item' => $item,
        ]);
    }

    /**
     * Receive the item here, on the computer the console is open on.
     * POST /counter/turnover/{item_id}
     */
    public function consoleComplete(Request $request, int $itemId): JsonResponse
    {
        return $this->run((string) session('admin_token', ''), $itemId, $request);
    }

    // ── The phone the QR was scanned with ────────────────────────────────

    /**
     * The turnover page itself.
     * GET /turnover/{ref}?k=...
     */
    public function show(Request $request, string $ref)
    {
        $handoff = $this->handoffFor($request, $ref);

        if ($handoff === null) {
            return response()->view('turnover.expired', [], 410);
        }

        $lookup = $this->turnover->item($handoff['token'], (int) $handoff['item_id']);

        return response()->view('turnover.show', [
            'item' => $lookup['ok'] ? ($lookup['item'] ?? []) : null,
            'loadError' => $lookup['ok'] ? null : $lookup['message'],
            'key' => (string) $request->query('k', ''),
            'ref' => $ref,
            'statuses' => TurnoverService::STATUSES,
            'admin' => $handoff['admin'] ?? '',
        ]);
    }

    /**
     * Receive the item from the phone.
     * POST /turnover/{ref}
     */
    public function complete(Request $request, string $ref): JsonResponse
    {
        $handoff = $this->handoffFor($request, $ref);

        if ($handoff === null) {
            return response()->json([
                'message' => 'This counter link has expired. Open "Mark as acquired" on the console again for a new QR.',
            ], 410);
        }

        $response = $this->run($handoff['token'], (int) $handoff['item_id'], $request);

        // A handoff is good for one turnover. Anyone who photographed the QR
        // off the console's screen gets nothing out of it afterwards, and the
        // phone that used it is already showing its own result.
        if ($response->getStatusCode() < 300) {
            Cache::forget($this->cacheKey($this->keyFrom($request)));
        }

        return $response;
    }

    // ── Shared ───────────────────────────────────────────────────────────

    /**
     * One turnover, wherever it was started from.
     *
     * An item the store already holds is not a dead end: its selling price
     * and its status are saved without asking for photographs of a handover
     * that happened days ago.
     */
    private function run(string $token, int $itemId, Request $request): JsonResponse
    {
        if ($token === '') {
            return response()->json(['message' => 'Your session has ended. Sign in again.'], 401);
        }

        $validated = $request->validate([
            'turnover_photo' => ['nullable', 'image', 'max:8192'],
            'payout_photo' => ['nullable', 'image', 'max:8192'],
            'acquisition_price' => ['nullable', 'numeric', 'min:0'],
            'public_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:' . implode(',', TurnoverService::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lookup = $this->turnover->item($token, $itemId);

        if (!$lookup['ok']) {
            return response()->json(['message' => $lookup['message']], $lookup['status'] ?: 502);
        }

        $item = $lookup['item'] ?? [];

        // Already in the store: the price and the status are all that is
        // left to decide.
        if (!empty($item['is_turnover_verified'])) {
            $result = $this->turnover->saveDetails(
                $token,
                $itemId,
                $validated['status'] ?? null,
                $validated['public_price'] ?? null,
            );

            $result['item'] = $result['item'] ?? $this->turnover->item($token, $itemId)['item'] ?? $item;
            $result['already_received'] = true;

            return response()->json($result, $result['status']);
        }

        $itemPhoto = $request->file('turnover_photo');
        $payoutPhoto = $request->file('payout_photo');

        if ($itemPhoto === null || $payoutPhoto === null) {
            return response()->json([
                'message' => 'Both photos are needed: the item you received, and the seller holding their cash.',
                'errors' => array_filter([
                    'turnover_photo' => $itemPhoto === null ? ['Photograph the item you received.'] : null,
                    'payout_photo' => $payoutPhoto === null ? ['Photograph the seller being paid.'] : null,
                ]),
            ], 422);
        }

        // Nothing can be received at a price nobody agreed: the payout is
        // computed from it.
        if (($item['acquisition_price'] ?? null) === null && ($validated['acquisition_price'] ?? null) === null) {
            return response()->json([
                'message' => 'No price was agreed with the seller yet. Enter what the store is paying them.',
                'errors' => ['acquisition_price' => ['Enter the agreed price.']],
            ], 422);
        }

        $result = $this->turnover->complete($token, $itemId, [
            'acquisition_price' => $validated['acquisition_price'] ?? null,
            'public_price' => $validated['public_price'] ?? null,
            'status' => $validated['status'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $itemPhoto, $payoutPhoto);

        return response()->json($result, $result['status']);
    }

    /**
     * The handoff behind a scanned link, or null when the key is missing,
     * expired, or points at a different item than the link says.
     *
     * @return array{item_id: int, ref: string, token: string, admin: string, expires_at: string}|null
     */
    private function handoffFor(Request $request, string $ref): ?array
    {
        $key = $this->keyFrom($request);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        $handoff = Cache::get($this->cacheKey($key));

        if (!is_array($handoff) || ($handoff['ref'] ?? null) !== $ref || empty($handoff['token'])) {
            return null;
        }

        return $handoff;
    }

    /** The key as the QR carried it: in the link on a GET, in the form on a POST. */
    private function keyFrom(Request $request): string
    {
        return (string) ($request->input('k') ?? $request->query('k', ''));
    }

    private function cacheKey(string $key): string
    {
        return 'fm-turnover-handoff:' . $key;
    }
}
