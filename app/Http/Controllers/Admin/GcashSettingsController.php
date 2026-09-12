<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GcashSettingsController extends Controller
{
    private const ENDPOINT = 'https://fati-api.alertaraqc.com/api/admin/settings/gcash';

    public function show(Request $request)
    {
        $gcash = null;
        $gcashError = null;
        try {
            $response = Http::withToken($request->session()->get('admin_token'))
                ->acceptJson()->timeout(30)->get(self::ENDPOINT);
            if ($response->successful()) {
                $gcash = $response->json('data');
            } else {
                $gcashError = $response->json('message') ?? 'Unable to load GCash settings. Please retry.';
            }
        } catch (\Exception $e) {
            $gcashError = 'Unable to connect to the payment settings service. Please retry.';
        }

        return response()->view('admin.settings', compact('gcash', 'gcashError'))
            ->header('Cache-Control', 'no-store');
    }

    public function update(Request $request)
    {
        $values = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'regex:/^(09[0-9]{9}|\\+639[0-9]{9})$/'],
            'qr_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'remove_qr' => ['sometimes', 'boolean'],
        ]);
        unset($values['qr_image']);
        $stream = null;
        try {
            $client = Http::withToken($request->session()->get('admin_token'))->acceptJson()->timeout(60);
            if ($request->hasFile('qr_image')) {
                $file = $request->file('qr_image');
                $stream = fopen($file->getRealPath(), 'rb');
                $client = $client->attach('qr_image', $stream, $file->getClientOriginalName());
            }
            $response = $client->post(self::ENDPOINT, $values);
            if (! $response->successful()) {
                return back()->withInput($request->only('account_name', 'account_number'))
                    ->withErrors($response->json('errors') ?: [
                        'gcash' => $response->json('message') ?? 'Unable to save GCash settings. Please retry.',
                    ]);
            }

            return redirect()->route('admin.settings')->with('gcash_success', 'GCash payment settings saved.');
        } catch (\Exception $e) {
            return back()->withInput($request->only('account_name', 'account_number'))
                ->withErrors(['gcash' => 'Could not confirm the save. Reload settings to check before trying again.']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
