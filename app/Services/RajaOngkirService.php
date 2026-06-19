<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class RajaOngkirService
{
    public const COURIERS = [
        'jne' => 'JNE',
        'jnt' => 'J&T',
        'sicepat' => 'SiCepat',
        'tiki' => 'TIKI',
        'pos' => 'POS Indonesia',
    ];

    public const UNAVAILABLE_MESSAGE = 'Layanan ongkir sedang tidak tersedia. Silakan pilih pickup atau delivery lokal.';

    private string $lastFailureReason = 'rajaongkir_unavailable';

    private ?string $lastEndpoint = null;

    public function provinces(): array
    {
        return $this->get('province');
    }

    public function cities(string|int $provinceId): array
    {
        return $this->get('city', [
            'province' => $provinceId,
        ]);
    }

    public function costs(string|int $destinationCityId, string $courier, int $weight): array
    {
        if (! array_key_exists($courier, self::COURIERS)) {
            throw new RuntimeException('Kurir tidak tersedia.');
        }

        $originCityId = config('services.rajaongkir.origin_city_id');

        if (blank($originCityId)) {
            $this->lastFailureReason = 'missing_origin_city_id';
            $this->logFailure($this->lastFailureReason, 'post', 'cost');
            throw new RuntimeException('Konfigurasi RajaOngkir belum lengkap: RAJAONGKIR_ORIGIN_CITY_ID wajib diisi untuk cek ongkir.');
        }

        $results = $this->post('cost', [
            'origin' => (string) $originCityId,
            'destination' => (string) $destinationCityId,
            'weight' => max((int) config('services.rajaongkir.default_weight', 1000), $weight),
            'courier' => $courier,
        ]);

        $services = data_get($results, '0.costs', []);

        return collect($services)->map(function (array $service) use ($courier) {
            $cost = data_get($service, 'cost.0', []);

            return [
                'courier' => $courier,
                'courier_name' => self::COURIERS[$courier],
                'service' => (string) data_get($service, 'service', ''),
                'description' => (string) data_get($service, 'description', ''),
                'cost' => (int) data_get($cost, 'value', 0),
                'estimation' => trim((string) data_get($cost, 'etd', '')),
            ];
        })->filter(fn (array $service) => $service['service'] !== '' && $service['cost'] > 0)
            ->values()
            ->all();
    }

    public function findService(string|int $destinationCityId, string $courier, string $service, int $weight): ?array
    {
        return collect($this->costs($destinationCityId, $courier, $weight))
            ->first(fn (array $option) => Str::lower($option['service']) === Str::lower($service));
    }

    private function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, $query);
    }

    private function post(string $path, array $form = []): array
    {
        return $this->request('post', $path, $form);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $apiKey = config('services.rajaongkir.key');
        $baseUrl = $this->baseUrl();

        if (blank($apiKey)) {
            $this->lastFailureReason = 'missing_api_key';
            $this->logFailure($this->lastFailureReason, $method, $path);
            throw new RuntimeException('Konfigurasi RajaOngkir belum lengkap: RAJAONGKIR_API_KEY wajib diisi.');
        }

        if (blank($baseUrl)) {
            $this->lastFailureReason = 'missing_base_url';
            $this->logFailure($this->lastFailureReason, $method, $path);
            throw new RuntimeException('Konfigurasi RajaOngkir belum lengkap: RAJAONGKIR_BASE_URL wajib diisi.');
        }

        $url = $baseUrl . '/' . ltrim($path, '/');
        $this->lastEndpoint = $url;

        try {
            $request = Http::timeout(15)->withHeaders([
                'key' => $apiKey,
            ]);

            $response = $method === 'post'
                ? $request->asForm()->post($url, $payload)
                : $request->get($url, $payload);
        } catch (ConnectionException $exception) {
            $this->lastFailureReason = 'connection_exception';
            $this->logFailure($this->lastFailureReason, $method, $path, [
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Tidak bisa terhubung ke RajaOngkir. Periksa koneksi internet dan base URL.', 0, $exception);
        }

        if ($response->failed()) {
            $this->lastFailureReason = 'http_failure_' . $response->status();
            $this->logFailure($this->lastFailureReason, $method, $path, [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw new RuntimeException('RajaOngkir mengembalikan HTTP ' . $response->status() . '. Periksa API key, base URL, dan jenis akun RajaOngkir.');
        }

        $payload = $response->json();
        $statusCode = (int) data_get($payload, 'rajaongkir.status.code', 0);

        if ($statusCode !== 200) {
            $this->lastFailureReason = 'api_status_' . ($statusCode ?: 'unknown');
            $this->logFailure($this->lastFailureReason, $method, $path, [
                'status_code' => $statusCode,
                'description' => data_get($payload, 'rajaongkir.status.description'),
            ]);

            throw new RuntimeException('RajaOngkir menolak request: ' . (data_get($payload, 'rajaongkir.status.description') ?: 'status tidak dikenali') . '.');
        }

        return data_get($payload, 'rajaongkir.results', []);
    }

    public function lastFailureReason(): string
    {
        return $this->lastFailureReason;
    }

    public function lastEndpoint(): ?string
    {
        return $this->lastEndpoint;
    }

    public function endpointFor(string $path): string
    {
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    private function baseUrl(): string
    {
        $baseUrl = trim((string) config('services.rajaongkir.base_url'));

        if ($baseUrl === '') {
            return '';
        }

        return preg_replace('#/(province|city|cost)$#', '', rtrim($baseUrl, '/'));
    }

    private function logFailure(string $reason, string $method, string $path, array $context = []): void
    {
        Log::warning('RajaOngkir API failed.', array_merge([
            'reason' => $reason,
            'method' => $method,
            'path' => $path,
            'endpoint' => $this->lastEndpoint,
        ], $context));
    }
}
