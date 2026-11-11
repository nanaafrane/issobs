<?php

namespace App\Services\Hubtel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Hubtel "verify mobile money name" lookup: is this number registered for MoMo, and in whose name?
 *
 * Credentials and the merchant number come from .env (HUBTEL_MOMO_*), never from code.
 * A slow or unreachable Hubtel no longer hangs the page: requests time out after a few
 * seconds and the caller gets a result it can save ("Could not reach Hubtel") instead of an exception.
 */
class MomoNameCheck
{
    public function enabled(): bool
    {
        $c = config('services.hubtel_momo');

        return filled($c['merchant_account'] ?? null) && filled($c['client_id'] ?? null) && filled($c['client_secret'] ?? null);
    }

    /**
     * @return array{message: ?string, responseCode: ?string, data: array, reachable: bool}
     */
    public function check(?string $number, ?string $channel): array
    {
        if (! $this->enabled()) {
            return $this->failed('Hubtel MoMo check is not set up (HUBTEL_MOMO_* in .env).');
        }

        $c = config('services.hubtel_momo');
        $url = str_replace('{merchant}', (string) $c['merchant_account'], (string) $c['verify_url']);

        try {
            $response = Http::acceptJson()
                ->withBasicAuth((string) $c['client_id'], (string) $c['client_secret'])
                ->connectTimeout(5)->timeout((int) ($c['timeout'] ?? 15))
                ->get($url, ['channel' => $channel, 'customerMsisdn' => $number]);
        } catch (ConnectionException) {
            return $this->failed('Could not reach Hubtel. Will try again.');
        }

        $json = $response->json();
        if (! is_array($json)) {
            return $this->failed('Hubtel answered ' . $response->status() . ' with no data.', reachable: $response->status() < 500);
        }

        return [
            'message' => $json['message'] ?? null,
            'responseCode' => isset($json['responseCode']) ? (string) $json['responseCode'] : null,
            'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
            'reachable' => true,
        ];
    }

    private function failed(string $message, bool $reachable = false): array
    {
        return ['message' => $message, 'responseCode' => null, 'data' => [], 'reachable' => $reachable];
    }
}
