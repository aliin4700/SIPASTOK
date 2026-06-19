<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Services\RajaOngkirService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RajaOngkirController extends Controller
{
    public function provinces(): JsonResponse
    {
        return $this->get($this->endpointFor('province'));
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer'],
        ]);

        if ($this->mode() === 'komerce') {
            return $this->komerceCities((int) $validated['province_id']);
        }

        return $this->get($this->endpointFor('city'), [
            'province' => $validated['province_id'],
        ]);
    }

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer'],
        ]);

        if ($this->mode() !== 'komerce') {
            return $this->apiError(
                400,
                'District hanya tersedia untuk mode Komerce.',
                null,
                null,
                400
            );
        }

        return $this->komerceDistricts((int) $validated['city_id']);
    }

    public function cost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination' => ['required', 'integer'],
            'weight' => ['required', 'integer', 'min:1'],
            'courier' => ['required', 'string', 'in:' . implode(',', array_keys(RajaOngkirService::COURIERS))],
        ]);

        $validated['courier'] = Str::lower($validated['courier']);

        $originCityId = config('services.rajaongkir.origin_city_id');

        if ($this->mode() === 'komerce') {
            $originDistrictId = config('services.rajaongkir.origin_district_id');

            if (blank($originDistrictId)) {
                return $this->configurationError('RAJAONGKIR_ORIGIN_DISTRICT_ID wajib diisi untuk cek ongkir Komerce.');
            }

            return $this->komerceCost([
                'origin' => (string) $originDistrictId,
                'destination' => (string) $validated['destination'],
                'weight' => max($this->cartWeight(), (int) $validated['weight']),
                'courier' => $validated['courier'],
            ]);
        }

        if (blank($originCityId)) {
            return $this->configurationError('RAJAONGKIR_ORIGIN_CITY_ID wajib diisi untuk cek ongkir.');
        }

        return $this->post($this->endpointFor('cost'), [
            'origin' => (string) $originCityId,
            'destination' => (string) $validated['destination'],
            'weight' => max($this->cartWeight(), (int) $validated['weight']),
            'courier' => $validated['courier'],
        ]);
    }

    public function debugProvince(): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        return $this->get($this->endpointFor('province'), [], true);
    }

    public function debugConfig(): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        return response()->json([
            'mode' => $this->mode(),
            'base_url' => $this->baseUrl(),
            'origin_city_id' => config('services.rajaongkir.origin_city_id'),
            'origin_district_id' => config('services.rajaongkir.origin_district_id'),
            'default_weight' => (int) config('services.rajaongkir.default_weight', 1000),
            'key_exists' => filled(config('services.rajaongkir.key')),
            'key_header' => $this->keyHeader(),
        ]);
    }

    public function debugCities(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        $validated = $request->validate([
            'province_id' => ['required', 'integer'],
        ]);

        if ($this->mode() === 'komerce') {
            return $this->komerceCities((int) $validated['province_id'], true);
        }

        return $this->get($this->endpointFor('city'), [
            'province' => $validated['province_id'],
        ], true);
    }

    public function debugCost(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        $validated = $request->validate([
            'destination' => ['required', 'integer'],
            'courier' => ['required', 'string', 'in:' . implode(',', array_keys(RajaOngkirService::COURIERS))],
            'weight' => ['required', 'integer', 'min:1'],
        ]);

        if ($this->mode() !== 'komerce') {
            return response()->json([
                'success' => false,
                'mode' => $this->mode(),
                'message' => 'Debug cost ini disiapkan untuk mode Komerce.',
            ], 400);
        }

        $originDistrictId = config('services.rajaongkir.origin_district_id');

        if (blank($originDistrictId)) {
            return response()->json([
                'success' => false,
                'mode' => $this->mode(),
                'message' => 'RAJAONGKIR_ORIGIN_DISTRICT_ID wajib diisi untuk debug cost Komerce.',
            ], 422);
        }

        $payload = [
            'origin' => (string) $originDistrictId,
            'destination' => (string) $validated['destination'],
            'weight' => (int) $validated['weight'],
            'courier' => Str::lower($validated['courier']),
        ];
        $endpoint = $this->urlFor($this->endpointFor('cost'));
        $response = $this->rawPost($this->endpointFor('cost'), $payload);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return response()->json([
            'success' => $response->successful(),
            'mode' => $this->mode(),
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status' => $response->status(),
            'raw' => $this->safeBody($response),
        ], $response->successful() ? 200 : 502);
    }

    private function get(string $path, array $query = [], bool $debug = false): JsonResponse
    {
        return $this->request('get', $path, $query, $debug);
    }

    private function post(string $path, array $form = [], bool $debug = false): JsonResponse
    {
        return $this->request('post', $path, $form, $debug);
    }

    private function komerceCities(int $provinceId, bool $debug = false): JsonResponse
    {
        $primaryPath = 'destination/city/' . $provinceId;
        $primary = $this->rawGet($primaryPath);

        if ($primary instanceof JsonResponse) {
            return $primary;
        }

        if ($primary->status() === 404) {
            $fallbackPath = 'destination/city';
            $fallback = $this->rawGet($fallbackPath, [
                'province_id' => $provinceId,
            ]);

            if ($fallback instanceof JsonResponse) {
                return $fallback;
            }

            if ($fallback->successful()) {
                return $this->komerceCitySuccess($fallback, $this->urlFor($fallbackPath), $debug);
            }

            return $this->komerceCityError($fallback, $this->urlFor($fallbackPath));
        }

        if ($primary->successful()) {
            return $this->komerceCitySuccess($primary, $this->urlFor($primaryPath), $debug);
        }

        return $this->komerceCityError($primary, $this->urlFor($primaryPath));
    }

    private function komerceDistricts(int $cityId): JsonResponse
    {
        $path = 'destination/district/' . $cityId;
        $response = $this->rawGet($path);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if (! $response->successful()) {
            return $this->apiError(
                $response->status(),
                $response->status() === 404
                    ? 'Endpoint district Komerce tidak ditemukan atau format endpoint salah.'
                    : 'RajaOngkir mengembalikan HTTP ' . $response->status() . '.',
                $this->safeBody($response),
                $this->urlFor($path)
            );
        }

        $districts = collect($this->komerceData($response))->map(function (array $item) {
            $id = data_get($item, 'id')
                ?? data_get($item, 'district_id')
                ?? data_get($item, 'subdistrict_id')
                ?? data_get($item, 'destination_id');

            $name = data_get($item, 'name')
                ?? data_get($item, 'district_name')
                ?? data_get($item, 'subdistrict_name')
                ?? data_get($item, 'label');

            $postalCode = data_get($item, 'postal_code')
                ?? data_get($item, 'zip_code');

            return [
                'id' => (string) $id,
                'name' => (string) $name,
                'postal_code' => $postalCode ? (string) $postalCode : null,
            ];
        })->filter(fn (array $item) => $item['id'] !== '' && $item['name'] !== '')
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    private function komerceCost(array $payload): JsonResponse
    {
        $path = $this->endpointFor('cost');

        if (config('app.debug')) {
            Log::debug('RajaOngkir Komerce cost request.', [
                'origin' => $payload['origin'],
                'destination' => $payload['destination'],
                'weight' => $payload['weight'],
                'courier' => $payload['courier'],
                'endpoint' => $this->urlFor($path),
            ]);
        }

        $response = $this->rawPost($path, $payload);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if ($response->failed()) {
            return $this->apiError(
                $response->status(),
                $response->status() === 404
                    ? 'Endpoint cost Komerce belum sesuai atau origin/destination harus district/subdistrict id.'
                    : 'RajaOngkir mengembalikan HTTP ' . $response->status() . '. Periksa API key, base URL, dan jenis akun RajaOngkir.',
                $this->safeBody($response),
                $this->urlFor($path),
                502,
                $this->safeDebugPayload($this->urlFor($path), $payload)
            );
        }

        return response()->json([
            'success' => true,
            'data' => $this->normalizeKomerceCosts($response, $payload['courier']),
        ]);
    }

    private function rawGet(string $path, array $query = []): Response|JsonResponse
    {
        $apiKey = config('services.rajaongkir.key');
        $baseUrl = $this->baseUrl();

        if (blank($apiKey)) {
            return $this->configurationError('RAJAONGKIR_API_KEY wajib diisi.');
        }

        if (blank($baseUrl)) {
            return $this->configurationError('RAJAONGKIR_BASE_URL wajib diisi.');
        }

        try {
            return Http::timeout(15)
                ->withHeaders([$this->keyHeader() => $apiKey])
                ->get($this->urlFor($path), $query);
        } catch (ConnectionException $exception) {
            return $this->apiError(0, 'Tidak bisa terhubung ke RajaOngkir. Periksa koneksi internet dan base URL.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $this->urlFor($path),
            ], $this->urlFor($path));
        } catch (Throwable $exception) {
            return $this->apiError(0, 'Request RajaOngkir gagal diproses. Periksa konfigurasi SSL, base URL, dan koneksi.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $this->urlFor($path),
            ], $this->urlFor($path));
        }
    }

    private function rawPost(string $path, array $form = []): Response|JsonResponse
    {
        $apiKey = config('services.rajaongkir.key');
        $baseUrl = $this->baseUrl();

        if (blank($apiKey)) {
            return $this->configurationError('RAJAONGKIR_API_KEY wajib diisi.');
        }

        if (blank($baseUrl)) {
            return $this->configurationError('RAJAONGKIR_BASE_URL wajib diisi.');
        }

        try {
            return Http::timeout(15)
                ->withHeaders([$this->keyHeader() => $apiKey])
                ->asForm()
                ->post($this->urlFor($path), $form);
        } catch (ConnectionException $exception) {
            return $this->apiError(0, 'Tidak bisa terhubung ke RajaOngkir. Periksa koneksi internet dan base URL.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $this->urlFor($path),
            ], $this->urlFor($path));
        } catch (Throwable $exception) {
            return $this->apiError(0, 'Request RajaOngkir gagal diproses. Periksa konfigurasi SSL, base URL, dan koneksi.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $this->urlFor($path),
            ], $this->urlFor($path));
        }
    }

    private function komerceCitySuccess(Response $response, string $endpoint, bool $debug): JsonResponse
    {
        $cities = collect($this->komerceData($response))->map(function (array $item) {
            $id = data_get($item, 'id')
                ?? data_get($item, 'city_id')
                ?? data_get($item, 'destination_id');

            $name = data_get($item, 'name')
                ?? data_get($item, 'city_name')
                ?? data_get($item, 'label');

            $postalCode = data_get($item, 'postal_code')
                ?? data_get($item, 'zip_code');

            return [
                'id' => (string) $id,
                'name' => (string) $name,
                'postal_code' => $postalCode ? (string) $postalCode : null,
            ];
        })->filter(fn (array $item) => $item['id'] !== '' && $item['name'] !== '')
            ->values()
            ->all();

        return response()->json(array_filter([
            'success' => true,
            'endpoint' => $debug ? $endpoint : null,
            'data' => $cities,
        ], fn ($value) => $value !== null));
    }

    private function komerceCityError(Response $response, string $endpoint): JsonResponse
    {
        $message = $response->status() === 404
            ? 'Endpoint city Komerce tidak ditemukan atau format endpoint salah'
            : 'RajaOngkir mengembalikan HTTP ' . $response->status() . '. Periksa API key, base URL, dan jenis akun RajaOngkir.';

        return $this->apiError(
            $response->status(),
            $message,
            $this->safeBody($response),
            $endpoint
        );
    }

    private function request(string $method, string $path, array $payload = [], bool $debug = false): JsonResponse
    {
        $apiKey = config('services.rajaongkir.key');
        $baseUrl = $this->baseUrl();
        $keyHeader = $this->keyHeader();

        if (blank($apiKey)) {
            return $this->configurationError('RAJAONGKIR_API_KEY wajib diisi.');
        }

        if (blank($baseUrl)) {
            return $this->configurationError('RAJAONGKIR_BASE_URL wajib diisi.');
        }

        $url = $this->urlFor($path);

        try {
            $request = Http::timeout(15)->withHeaders([
                $keyHeader => $apiKey,
            ]);

            $response = $method === 'post'
                ? $request->asForm()->post($url, $payload)
                : $request->get($url, $payload);
        } catch (ConnectionException $exception) {
            return $this->apiError(0, 'Tidak bisa terhubung ke RajaOngkir. Periksa koneksi internet dan base URL.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $url,
            ]);
        } catch (Throwable $exception) {
            return $this->apiError(0, 'Request RajaOngkir gagal diproses. Periksa konfigurasi SSL, base URL, dan koneksi.', [
                'exception' => $exception->getMessage(),
                'endpoint' => $url,
            ], $url);
        }

        if ($debug && $response->status() === 401) {
            return $this->apiError(
                401,
                'API key tidak valid atau header key salah',
                $this->safeBody($response),
                $url,
                401
            );
        }

        if ($debug) {
            return response()->json([
                'success' => $this->responseIsSuccessful($response),
                'status' => $response->status(),
                'mode' => $this->mode(),
                'key_header' => $keyHeader,
                'endpoint' => $url,
                'body' => $this->safeBody($response),
            ], $response->successful() ? 200 : 502);
        }

        if ($response->failed()) {
            if ($response->status() === 401) {
                return $this->apiError(
                    401,
                    'API key tidak valid atau header key salah',
                    $this->safeBody($response),
                    $url,
                    401
                );
            }

            return $this->apiError(
                $response->status(),
                'RajaOngkir mengembalikan HTTP ' . $response->status() . '. Periksa API key, base URL, dan jenis akun RajaOngkir.',
                $this->safeBody($response),
                $url
            );
        }

        if ($this->mode() === 'komerce') {
            return response()->json($response->json());
        }

        $statusCode = $this->rajaOngkirStatusCode($response);
        if ($statusCode !== 200) {
            return $this->apiError(
                $statusCode ?: $response->status(),
                'RajaOngkir menolak request: ' . ($this->rajaOngkirStatusDescription($response) ?: 'status tidak dikenali') . '.',
                $this->safeBody($response),
                $url
            );
        }

        return response()->json($response->json());
    }

    private function cartWeight(): int
    {
        $defaultWeight = (int) config('services.rajaongkir.default_weight', 1000);

        if (! auth()->check()) {
            return $defaultWeight;
        }

        $customer = Customer::firstOrCreate([
            'user_id' => auth()->id(),
        ]);

        $cart = Cart::with('items.product')->firstOrCreate([
            'customer_id' => $customer->id,
        ]);

        $weight = (int) $cart->items->sum(function ($item) use ($defaultWeight) {
            return (int) ($item->product->weight ?: $defaultWeight) * (int) $item->quantity;
        });

        return max($defaultWeight, $weight);
    }

    private function configurationError(string $message): JsonResponse
    {
        return $this->apiError(0, 'Konfigurasi RajaOngkir belum lengkap: ' . $message, null);
    }

    private function apiError(int $status, string $message, mixed $body, ?string $endpoint = null, int $httpStatus = 502, ?array $debug = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'mode' => $this->mode(),
            'key_header' => $this->keyHeader(),
            'endpoint' => $endpoint,
            'body' => $body,
            'raw' => $body,
            'debug' => $debug,
        ], fn ($value) => $value !== null), $httpStatus);
    }

    private function baseUrl(): string
    {
        $baseUrl = trim((string) config('services.rajaongkir.base_url'));

        if ($baseUrl === '') {
            return '';
        }

        return preg_replace('#/(province|city|cost|destination/province|destination/city|destination/district|calculate/domestic-cost|calculate/district/domestic-cost)$#', '', rtrim($baseUrl, '/'));
    }

    private function mode(): string
    {
        return Str::lower((string) config('services.rajaongkir.mode', 'starter')) ?: 'starter';
    }

    private function keyHeader(): string
    {
        return trim((string) config('services.rajaongkir.key_header', 'key')) ?: 'key';
    }

    private function endpointFor(string $type): string
    {
        if ($this->mode() === 'komerce') {
            return match ($type) {
                'province' => 'destination/province',
                'city' => 'destination/city',
                'district' => 'destination/district',
                'cost' => 'calculate/district/domestic-cost',
                default => $type,
            };
        }

        return $type;
    }

    private function responseIsSuccessful(Response $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        if ($this->mode() === 'komerce') {
            return true;
        }

        return $this->rajaOngkirStatusCode($response) === 200;
    }

    private function urlFor(string $path): string
    {
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    private function komerceData(Response $response): array
    {
        $payload = $response->json();
        $data = data_get($payload, 'data', data_get($payload, 'rajaongkir.results', []));

        return is_array($data) ? $data : [];
    }

    private function normalizeKomerceCosts(Response $response, string $courier): array
    {
        return collect($this->komerceData($response))->map(function (array $item) use ($courier) {
            $cost = $this->extractKomerceCost($item);

            $service = data_get($item, 'service')
                ?? data_get($item, 'code')
                ?? data_get($item, 'name')
                ?? '';

            return [
                'courier' => data_get($item, 'courier') ?? $courier,
                'courier_name' => data_get($item, 'courier_name') ?? data_get($item, 'name') ?? strtoupper($courier),
                'service' => (string) $service,
                'description' => (string) (data_get($item, 'description') ?? data_get($item, 'service_name') ?? '-'),
                'cost' => $cost,
                'etd' => trim((string) (data_get($item, 'etd') ?? data_get($item, 'duration') ?? data_get($item, 'estimated_delivery') ?? '')),
                'estimation' => trim((string) (data_get($item, 'etd') ?? data_get($item, 'duration') ?? data_get($item, 'estimated_delivery') ?? '')),
            ];
        })->filter(fn (array $item) => $item['service'] !== '' && $item['cost'] > 0)
            ->values()
            ->all();
    }

    private function safeDebugPayload(string $endpoint, array $payload): ?array
    {
        if (! config('app.debug')) {
            return null;
        }

        return [
            'mode' => $this->mode(),
            'endpoint' => $endpoint,
            'origin' => $payload['origin'] ?? null,
            'destination' => $payload['destination'] ?? null,
            'weight' => $payload['weight'] ?? null,
            'courier' => $payload['courier'] ?? null,
        ];
    }

    private function extractKomerceCost(array $item): int
    {
        $candidates = [
            data_get($item, 'cost'),
            data_get($item, 'price'),
            data_get($item, 'value'),
            data_get($item, 'amount'),
            data_get($item, 'shipping_cost'),
            data_get($item, 'cost.0.value'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) || $candidate === null || $candidate === '') {
                continue;
            }

            if (is_numeric($candidate)) {
                return (int) $candidate;
            }

            $normalized = preg_replace('/[^\d]/', '', (string) $candidate);

            if ($normalized !== '') {
                return (int) $normalized;
            }
        }

        return 0;
    }

    private function safeBody(Response $response): mixed
    {
        $body = Str::limit($response->body(), 2000, '');
        $apiKey = (string) config('services.rajaongkir.key');

        if ($apiKey !== '') {
            $body = str_replace($apiKey, '[hidden]', $body);
        }

        return json_decode($body, true) ?? $body;
    }

    private function rajaOngkirStatusCode(Response $response): int
    {
        return (int) data_get($response->json(), 'rajaongkir.status.code', 0);
    }

    private function rajaOngkirStatusDescription(Response $response): ?string
    {
        return data_get($response->json(), 'rajaongkir.status.description');
    }
}
