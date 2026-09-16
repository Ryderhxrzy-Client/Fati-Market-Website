<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Receiving an item at the counter, in one call.
 *
 * Marking an item acquired is never a single API call: the turnover is
 * verified with its two photographs, the seller's cash is recorded, and the
 * item is left in whatever status the counter chose - published, with a
 * selling price, when that is what was decided. Doing that from the browser
 * meant three fetches in a row with no way to say which of them failed.
 *
 * Both entrances go through here, so the sequence cannot drift apart:
 *
 *   - the console's own "Mark as acquired", on the desk computer;
 *   - the phone page the counter QR opens, where the camera is.
 */
class TurnoverService
{
    /** The statuses the counter may put a received item in. */
    public const STATUSES = ['acquired', 'public', 'reserved'];

    public function api(): string
    {
        return rtrim(config('services.fati.url', 'https://fati-api.alertaraqc.com/api'), '/');
    }

    /**
     * The listing, as admin sees it.
     *
     * @return array{ok: bool, item: ?array, message: string, status: int}
     */
    public function item(string $token, int $itemId): array
    {
        try {
            $response = Http::withToken($token)
                ->accept('application/json')
                ->timeout(30)
                ->get($this->api() . '/items/' . $itemId);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'item' => null,
                'message' => 'The store API could not be reached: ' . $e->getMessage(),
                'status' => 503,
            ];
        }

        $payload = $response->json() ?? [];

        if (!$response->successful()) {
            return [
                'ok' => false,
                'item' => null,
                'message' => $payload['message'] ?? ('The item could not be loaded (HTTP ' . $response->status() . ').'),
                'status' => $response->status(),
            ];
        }

        return ['ok' => true, 'item' => $payload['data'] ?? [], 'message' => '', 'status' => 200];
    }

    /**
     * Receive the item: verify the turnover with its proof, pay the seller,
     * and leave the item in the chosen status.
     *
     * The three steps are not equal. The first is the turnover itself, and a
     * failure there means nothing happened. The two after it act on an item
     * that is already in the store, so a failure is reported as a warning
     * rather than pretending the turnover never took place.
     *
     * @param  array{acquisition_price?: ?string, public_price?: ?string, status?: ?string, notes?: ?string}  $input
     * @return array{ok: bool, message: string, item: ?array, warnings: array<int, string>, status: int}
     */
    public function complete(
        string $token,
        int $itemId,
        array $input,
        ?UploadedFile $itemPhoto,
        ?UploadedFile $payoutPhoto,
    ): array {
        $warnings = [];

        // 1. The turnover, with the counter's two photographs.
        $fields = array_filter([
            'acquisition_price' => $this->clean($input['acquisition_price'] ?? null),
            'notes' => $this->clean($input['notes'] ?? null),
        ], fn ($value) => $value !== null);

        $request = Http::withToken($token)->accept('application/json')->timeout(120);

        foreach ([['turnover_photo', $itemPhoto], ['payout_photo', $payoutPhoto]] as [$field, $file]) {
            if ($file instanceof UploadedFile) {
                $request = $request->attach(
                    $field,
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName() ?: ($field . '.jpg'),
                );
            }
        }

        try {
            $response = $request->post($this->api() . '/admin/items/' . $itemId . '/verify-turnover', $fields);
        } catch (\Throwable $e) {
            Log::error('Turnover failed', ['item' => $itemId, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => 'The store API could not be reached: ' . $e->getMessage(),
                'item' => null,
                'warnings' => [],
                'status' => 503,
            ];
        }

        $payload = $response->json() ?? [];

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => $payload['message'] ?? ('The item could not be marked acquired (HTTP ' . $response->status() . ').'),
                'item' => null,
                'warnings' => [],
                'status' => $response->status() === 422 ? 422 : 502,
            ];
        }

        $item = $payload['data'] ?? null;

        // 2. The seller's cash. The payout photograph above IS the money
        // changing hands, so it is recorded in the same breath rather than
        // left for someone to remember afterwards.
        $paid = $this->send('post', $token, 'admin/items/' . $itemId . '/seller-payout', []);

        if ($paid['ok']) {
            $item = $paid['item'] ?? $item;
        } else {
            $warnings[] = 'The item was received, but the seller payout was not recorded: ' . $paid['message'];
        }

        // 3. Where the item lands.
        $item = $this->applyOutcome(
            $token,
            $itemId,
            $this->clean($input['status'] ?? null),
            $this->clean($input['public_price'] ?? null),
            $item,
            $warnings,
        );

        return [
            'ok' => true,
            'message' => 'Item received and the seller paid.',
            'item' => $item,
            'warnings' => $warnings,
            'status' => 200,
        ];
    }

    /**
     * The selling price and the status alone, for an item the store already
     * holds - a mispriced or misfiled item corrected at the counter instead
     * of hunted down in the inventory lists afterwards.
     *
     * @return array{ok: bool, message: string, item: ?array, warnings: array<int, string>, status: int}
     */
    public function saveDetails(string $token, int $itemId, ?string $status, ?string $price): array
    {
        $warnings = [];

        $item = $this->applyOutcome(
            $token,
            $itemId,
            $this->clean($status),
            $this->clean($price),
            null,
            $warnings,
        );

        // Nothing here is a second-order step, so a failure is the answer
        // rather than a note attached to a success.
        if ($warnings !== [] && $item === null) {
            return ['ok' => false, 'message' => $warnings[0], 'item' => null, 'warnings' => [], 'status' => 422];
        }

        return ['ok' => true, 'message' => 'Saved.', 'item' => $item, 'warnings' => $warnings, 'status' => 200];
    }

    /**
     * Apply the status and selling price the counter chose.
     *
     * Publishing goes through its own endpoint, which is where the catalog
     * rules live; every other status is the plain item update.
     *
     * @param  array<int, string>  $warnings
     */
    private function applyOutcome(
        string $token,
        int $itemId,
        ?string $status,
        ?string $price,
        ?array $item,
        array &$warnings,
    ): ?array {
        if ($status !== null && !in_array($status, self::STATUSES, true)) {
            $warnings[] = 'That is not a status the counter can set, so the item was left as it was.';
            $status = null;
        }

        if ($status === 'public') {
            if ($price === null) {
                $warnings[] = 'A published item needs a selling price, so it was left as it was.';

                return $item;
            }

            $published = $this->send('post', $token, 'admin/items/' . $itemId . '/publish', ['public_price' => $price]);

            if ($published['ok']) {
                return $published['item'] ?? $item;
            }

            $warnings[] = 'Publishing the item failed: ' . $published['message'];

            return $item;
        }

        if ($price === null && ($status === null || $status === 'acquired')) {
            return $item;
        }

        $updated = $this->send('put', $token, 'admin/items/' . $itemId, array_filter([
            'public_price' => $price,
            'status' => $status,
        ], fn ($value) => $value !== null));

        if ($updated['ok']) {
            return $updated['item'] ?? $item;
        }

        $warnings[] = 'The price and status could not be saved: ' . $updated['message'];

        return $item;
    }

    /** @return array{ok: bool, item: ?array, message: string} */
    private function send(string $verb, string $token, string $path, array $body): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->{$verb}($this->api() . '/' . $path, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'item' => null, 'message' => $e->getMessage()];
        }

        $payload = $response->json() ?? [];

        return [
            'ok' => $response->successful(),
            'item' => $payload['data'] ?? null,
            'message' => $payload['message'] ?? ('HTTP ' . $response->status()),
        ];
    }

    /** An empty string means "not given", never "set this to nothing". */
    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
