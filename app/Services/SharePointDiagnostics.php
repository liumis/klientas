<?php

namespace App\Services;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SharePointDiagnostics
{
    /** @var array<int, array{step: string, ok: bool, detail: string}> */
    private array $steps = [];

    /**
     * @return array<int, array{step: string, ok: bool, detail: string}>
     */
    public function run(): array
    {
        $this->steps = [];

        if (! Schema::hasTable('settings')) {
            $this->add('Database table', false, 'Table `settings` does not exist.');

            return $this->steps;
        }

        $settings = Setting::query()
            ->where('scope', 'sharepoint')
            ->pluck('value', 'setting');

        if (! filled($settings->get('client_secret'))) {
            $envSecret = config('services.sharepoint.client_secret');
            if (filled($envSecret)) {
                $settings->put('client_secret', $envSecret);
            }
        }

        if ($settings->isEmpty()) {
            $this->add('SharePoint settings', false, 'No rows with scope=sharepoint. Run migrations or import settings.');

            return $this->steps;
        }

        $required = ['tenant_id', 'client_id', 'client_secret', 'site_name', 'file_path', 'file_name'];
        foreach ($required as $key) {
            $value = $settings->get($key);
            $this->add(
                "Setting: {$key}",
                filled($value),
                filled($value)
                    ? ($key === 'client_secret' ? 'Set ('.strlen((string) $value).' chars)' : (string) $value)
                    : 'Missing or empty'.($key === 'client_secret' ? ' — set SHAREPOINT_CLIENT_SECRET env and run migrate' : '')
            );
        }

        $sheetName = $settings->get('sheet_name') ?? 'Automatizacija Klientas (default)';
        $this->add('Setting: sheet_name', true, (string) $sheetName);

        if ($settings->filter(fn ($v, $k) => in_array($k, $required, true) && ! filled($v))->isNotEmpty()) {
            return $this->steps;
        }

        try {
            $tokenClient = new Client;
            $tenantId = $settings->get('tenant_id');
            $response = $tokenClient->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'form_params' => [
                        'client_id' => $settings->get('client_id'),
                        'client_secret' => $settings->get('client_secret'),
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                    'http_errors' => false,
                ]
            );

            $tokenBody = (string) $response->getBody();
            $tokenData = json_decode($tokenBody, true);
            $accessToken = $tokenData['access_token'] ?? null;

            $this->add(
                'Azure OAuth token',
                $response->getStatusCode() === 200 && filled($accessToken),
                $response->getStatusCode() === 200 && filled($accessToken)
                    ? 'Token acquired successfully'
                    : $this->formatApiError($response->getStatusCode(), $tokenBody)
            );

            if (! filled($accessToken)) {
                return $this->steps;
            }

            $graph = new Client(['base_uri' => 'https://graph.microsoft.com/v1.0/']);
            $headers = [
                'Authorization' => "Bearer {$accessToken}",
                'Accept' => 'application/json',
            ];

            $siteName = (string) $settings->get('site_name');
            $parsed = parse_url($siteName);
            $host = $parsed['host'] ?? null;
            $path = $parsed['path'] ?? null;

            if (! $host || ! $path) {
                $this->add('Parse site_name', false, "Invalid site_name: {$siteName}");

                return $this->steps;
            }

            $siteResponse = $graph->get("sites/{$host}:{$path}", [
                'headers' => $headers,
                'http_errors' => false,
            ]);
            $siteBody = (string) $siteResponse->getBody();
            $siteData = json_decode($siteBody, true);
            $siteId = $siteData['id'] ?? null;

            $this->add(
                'Resolve SharePoint site',
                $siteResponse->getStatusCode() === 200 && filled($siteId),
                $siteResponse->getStatusCode() === 200 && filled($siteId)
                    ? "Site ID: {$siteId}"
                    : $this->formatApiError($siteResponse->getStatusCode(), $siteBody)
            );

            if (! filled($siteId)) {
                return $this->steps;
            }

            $filePath = trim((string) $settings->get('file_path'), '/');
            $fileName = ltrim((string) $settings->get('file_name'), '/');
            $relativePath = $filePath.'/'.$fileName;
            $encodedPath = implode('/', array_map(rawurlencode(...), explode('/', $relativePath)));

            $fileResponse = $graph->get("sites/{$siteId}/drive/root:/{$encodedPath}", [
                'headers' => $headers,
                'http_errors' => false,
            ]);
            $fileBody = (string) $fileResponse->getBody();
            $fileData = json_decode($fileBody, true);
            $fileId = $fileData['id'] ?? null;

            $this->add(
                'Find Excel file',
                $fileResponse->getStatusCode() === 200 && filled($fileId),
                $fileResponse->getStatusCode() === 200 && filled($fileId)
                    ? "File: {$relativePath} (ID: {$fileId})"
                    : $this->formatApiError($fileResponse->getStatusCode(), $fileBody)
            );

            if (! filled($fileId)) {
                return $this->steps;
            }

            $sessionResponse = $graph->post("sites/{$siteId}/drive/items/{$fileId}/workbook/createSession", [
                'headers' => [...$headers, 'Content-Type' => 'application/json'],
                'json' => ['persistChanges' => true],
                'http_errors' => false,
            ]);
            $sessionBody = (string) $sessionResponse->getBody();
            $sessionData = json_decode($sessionBody, true);
            $sessionId = $sessionData['id'] ?? null;

            $this->add(
                'Excel workbook session',
                in_array($sessionResponse->getStatusCode(), [200, 201], true) && filled($sessionId),
                in_array($sessionResponse->getStatusCode(), [200, 201], true) && filled($sessionId)
                    ? 'Session created — app can write to this file'
                    : $this->formatApiError($sessionResponse->getStatusCode(), $sessionBody)
            );

            if (! filled($sessionId)) {
                return $this->steps;
            }

            $encodedSheet = rawurlencode((string) $sheetName);
            $sheetHeaders = [...$headers, 'Content-Type' => 'application/json', 'workbook-session-id' => $sessionId];

            $sheetResponse = $graph->get(
                "sites/{$siteId}/drive/items/{$fileId}/workbook/worksheets/{$encodedSheet}/usedRange",
                ['headers' => $sheetHeaders, 'http_errors' => false]
            );
            $sheetBody = (string) $sheetResponse->getBody();

            $this->add(
                "Worksheet tab: {$sheetName}",
                $sheetResponse->getStatusCode() === 200,
                $sheetResponse->getStatusCode() === 200
                    ? 'Sheet found and readable'
                    : $this->formatApiError($sheetResponse->getStatusCode(), $sheetBody)
            );

            $graph->post("sites/{$siteId}/drive/items/{$fileId}/workbook/closeSession", [
                'headers' => $sheetHeaders,
                'http_errors' => false,
            ]);
        } catch (Throwable $e) {
            $this->add('Unexpected error', false, $e->getMessage());
        }

        return $this->steps;
    }

    private function add(string $step, bool $ok, string $detail): void
    {
        $this->steps[] = compact('step', 'ok', 'detail');
    }

    private function formatApiError(int $status, string $body): string
    {
        $data = json_decode($body, true);

        if (is_array($data)) {
            $code = $data['error']['code'] ?? null;
            $message = $data['error']['message'] ?? null;

            if ($code || $message) {
                return trim("HTTP {$status} — ".($code ? "[{$code}] " : '').($message ?? ''));
            }
        }

        return "HTTP {$status} — ".substr($body, 0, 500);
    }
}
