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
        'pos' => 'POS Indonesia',
    ];

    public const UNAVAILABLE_MESSAGE = 'Layanan ongkir sedang tidak tersedia. Silakan pilih pickup atau delivery lokal.';

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

        $results = $this->post('cost', [
            'origin' => (string) config('services.rajaongkir.origin_city_id'),
            'destination' => (string) $destinationCityId,
            'weight' => max(1, $weight),
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

        if (! $apiKey) {
            $this->logFailure('missing_api_key', $method, $path);
            throw new RuntimeException(self::UNAVAILABLE_MESSAGE);
        }

        $url = rtrim((string) config('services.rajaongkir.base_url'), '/') . '/' . ltrim($path, '/');

        try {
            $request = Http::timeout(15)->withHeaders([
                'key' => $apiKey,
            ]);

            $response = $method === 'post'
                ? $request->asForm()->post($url, $payload)
                : $request->get($url, $payload);
        } catch (ConnectionException $exception) {
            $this->logFailure('connection_exception', $method, $path, [
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException(self::UNAVAILABLE_MESSAGE, 0, $exception);
        }

        if ($response->failed()) {
            $this->logFailure('http_failure', $method, $path, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(self::UNAVAILABLE_MESSAGE);
        }

        $payload = $response->json();
        $statusCode = (int) data_get($payload, 'rajaongkir.status.code', 0);

        if ($statusCode !== 200) {
            $this->logFailure('api_status_failure', $method, $path, [
                'status_code' => $statusCode,
                'description' => data_get($payload, 'rajaongkir.status.description'),
            ]);

            throw new RuntimeException(self::UNAVAILABLE_MESSAGE);
        }

        return data_get($payload, 'rajaongkir.results', []);
    }

    private function logFailure(string $reason, string $method, string $path, array $context = []): void
    {
        Log::warning('RajaOngkir API failed.', array_merge([
            'reason' => $reason,
            'method' => $method,
            'path' => $path,
        ], $context));
    }
}
