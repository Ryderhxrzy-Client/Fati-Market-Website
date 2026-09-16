<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Carrying one counter job from the desk computer to a phone.
 *
 * Both halves of the counter need a camera the desk does not have: receiving
 * an item is two photographs, handing one over is another. So the console
 * mints one of these, draws it as a QR, and whichever phone scans it opens
 * that one job already authorised - for that job, for half an hour, once.
 *
 * The handoff, not the phone, holds the admin's API token: it stays on this
 * server for the few minutes the handoff lives, and the phone only ever
 * carries the random key that points at it.
 */
class CounterHandoff
{
    /** Long enough to walk to the counter, short enough to be worthless later. */
    public const MINUTES = 30;

    /**
     * Mint one, and return the key the QR will carry.
     *
     * @param  array<string, mixed>  $payload  what the job needs: the id, the ref, the token.
     */
    public static function mint(string $kind, array $payload): string
    {
        $key = Str::random(48);

        Cache::put(self::cacheKey($kind, $key), $payload, now()->addMinutes(self::MINUTES));

        return $key;
    }

    /**
     * The job behind a scanned link, or null when the key is missing, expired,
     * or points at something other than the link says.
     *
     * @return array<string, mixed>|null
     */
    public static function resolve(string $kind, Request $request, string $ref): ?array
    {
        $key = self::keyFrom($request);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        $handoff = Cache::get(self::cacheKey($kind, $key));

        if (!is_array($handoff) || ($handoff['ref'] ?? null) !== $ref || empty($handoff['token'])) {
            return null;
        }

        return $handoff;
    }

    /**
     * Spend it. A handoff is good for one job, so whoever photographed the QR
     * off the console's screen gets nothing out of it afterwards.
     */
    public static function forget(string $kind, Request $request): void
    {
        Cache::forget(self::cacheKey($kind, self::keyFrom($request)));
    }

    /** The key as the QR carried it: in the link on a GET, in the form on a POST. */
    public static function keyFrom(Request $request): string
    {
        return (string) ($request->input('k') ?? $request->query('k', ''));
    }

    private static function cacheKey(string $kind, string $key): string
    {
        return 'fm-' . $kind . '-handoff:' . $key;
    }
}
