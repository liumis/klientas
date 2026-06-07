<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Setting;
use App\Support\SharePointFileUrl;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

class SharePointController extends Controller
{
    private const DEFAULT_EXCEL_SHEET_NAME = 'Automatizacija Klientas';

    private const EXCEL_COLUMN_COUNT = 15;

    private Client $client;

    private string|false $accessToken;

    private object $spSettings;

    private function sharepointSetting(string $key): ?string
    {
        $value = $this->spSettings->{$key} ?? null;

        if ($key === 'client_secret' && ! filled($value)) {
            $value = config('services.sharepoint.client_secret');
        }

        return filled($value) ? (string) $value : null;
    }

    public function __construct()
    {
        $this->spSettings = (object) Setting::query()
            ->where('scope', 'sharepoint')
            ->pluck('value', 'setting')->toArray();
        $this->accessToken = $this->getSharePointToken();
        $this->client = new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
        ]);
    }

    private function getSharePointToken(): string|false
    {
        try {
            $tenantId = $this->sharepointSetting('tenant_id');
            $clientId = $this->sharepointSetting('client_id');
            $clientSecret = $this->sharepointSetting('client_secret');

            if (! $tenantId || ! $clientId || ! $clientSecret) {
                Log::error('SharePointController: missing tenant_id, client_id, or client_secret in settings');

                return false;
            }

            $client = new Client;
            $response = $client->post(
                "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token",
                [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ]
            );
            $data = json_decode($response->getBody(), true);

            return $data['access_token'] ?? false;

        } catch (Throwable $e) {
            Log::error('SharePointController: '.$e->getMessage());

            return false;
        }
    }

    private function getSiteId(string $sharepointSiteUrl): string|false
    {
        if (! $this->accessToken) {
            return false;
        }

        $parsed = parse_url($sharepointSiteUrl);

        $host = $parsed['host'] ?? null;
        $path = $parsed['path'] ?? null;

        if (! $host || ! $path) {
            Log::error('SharePointController: invalid site_name setting');

            return false;
        }

        $endpoint = "sites/{$host}:{$path}";

        try {
            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::error('SharePointController: getSiteId failed', [
                    'status' => $response->getStatusCode(),
                    'body' => (string) $response->getBody(),
                ]);

                return false;
            }

            $data = json_decode($response->getBody(), true);

            return $data['id'] ?? false;
        } catch (Throwable $e) {
            Log::error('SharePointController: getSiteId '.$e->getMessage());

            return false;
        }
    }

    public function getExcelWebUrl(): ?string
    {
        if (! $this->accessToken) {
            return null;
        }

        $siteName = $this->spSettings->site_name ?? null;
        $filePath = $this->spSettings->file_path ?? null;
        $fileName = $this->spSettings->file_name ?? null;

        if (! $siteName || ! $filePath || ! $fileName) {
            return null;
        }

        $siteId = $this->getSiteId($siteName);
        $item = $this->getDriveItem($siteId, $filePath.'/'.$fileName);

        if (! is_array($item)) {
            return null;
        }

        if (! empty($item['webUrl'])) {
            return $item['webUrl'];
        }

        $site = SharePointFileUrl::parseSiteName($siteName);
        $uniqueId = $item['listItem']['uniqueId'] ?? null;

        if ($site !== null && $uniqueId) {
            return SharePointFileUrl::buildDocAspxUrl(
                $site['host'],
                $site['path'],
                $uniqueId,
                $fileName,
            );
        }

        return null;
    }

    private function getDriveItem(string|false $siteId, string $filePath): array|false
    {
        if (! $this->accessToken || ! $siteId) {
            return false;
        }

        $encodedPath = implode('/', array_map(rawurlencode(...), explode('/', $filePath)));

        $endpoint = "sites/{$siteId}/drive/root:/{$encodedPath}";

        try {
            $response = $this->client->get($endpoint, [
                'query' => ['$expand' => 'listItem'],
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                    'http_errors' => false,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $error = "Nepavyko rasti failo pagal kelią: {$filePath}. API klaida: ".$response->getBody();
                Log::error('SharePointController: '.$error);

                return false;
            }

            return json_decode($response->getBody(), true);

        } catch (Throwable $e) {
            Log::error('SharePointController: '.$e->getMessage());
        }

        return false;
    }

    private function getFileId(string|false $siteId, string $filePath): string|false
    {
        $item = $this->getDriveItem($siteId, $filePath);

        if (! is_array($item)) {
            return false;
        }

        return $item['id'] ?? false;
    }

    /**
     * @return array<int, string|int>
     */
    public function buildClaimRow(Claim $claim): array
    {
        $days = '';
        if ($claim->rental_start && $claim->rental_end) {
            $date1 = new DateTime($claim->rental_start->format('Y-m-d'));
            $date2 = new DateTime($claim->rental_end->format('Y-m-d'));
            $days = $date1->diff($date2)->days + 1;
        }

        return [
            $claim->created_at?->format('Y-m-d') ?? '',           // A: Rezervacijos data
            $claim->repair_vehicle_plates,                        // B: Valst. nr.
            trim($claim->first_name.' '.$claim->last_name),       // C: Klientas
            $claim->phone,                                        // D: Kontaktinis telefono numeris
            $claim->email,                                        // E: El. Pašto adresas
            $claim->rental_start?->format('Y-m-d') ?? '',         // F: Nuo
            $claim->rental_end?->format('Y-m-d') ?? '',           // G: Iki
            $days,                                                // H: Dienų skaičius
            '',                                                   // I: Ar paimti dokumentai iš AutoPC
            '',                                                   // J: Sąskaita
            '',                                                   // K: Suma
            '',                                                   // L: Išsiųsta draudimui
            '',                                                   // M: Apmokėta
            '',                                                   // N: Gautas mokėjimas
            '',                                                   // O: Likutis
        ];
    }

    private function createWorkbookSession(string $siteId, string $fileId): ?string
    {
        try {
            $response = $this->client->post(
                "sites/{$siteId}/drive/items/{$fileId}/workbook/createSession",
                [
                    'headers' => $this->authHeaders(),
                    'json' => ['persistChanges' => true],
                    'http_errors' => false,
                ]
            );

            if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
                Log::error('SharePointController: createSession failed', [
                    'status' => $response->getStatusCode(),
                    'body' => (string) $response->getBody(),
                ]);

                return null;
            }

            $data = json_decode($response->getBody(), true);

            return $data['id'] ?? null;
        } catch (Throwable $e) {
            Log::error('SharePointController: createSession '.$e->getMessage());

            return null;
        }
    }

    private function closeWorkbookSession(string $siteId, string $fileId, string $sessionId): void
    {
        try {
            $this->client->post(
                "sites/{$siteId}/drive/items/{$fileId}/workbook/closeSession",
                [
                    'headers' => $this->authHeaders($sessionId),
                    'http_errors' => false,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('SharePointController: closeSession '.$e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(?string $sessionId = null): array
    {
        $headers = [
            'Authorization' => "Bearer {$this->accessToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($sessionId) {
            $headers['workbook-session-id'] = $sessionId;
        }

        return $headers;
    }

    private function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter;
    }

    private function appendRowToExcel(string $siteId, string $fileId, string $sheetName, array $rowData): bool
    {
        if (! $this->accessToken) {
            return false;
        }

        $sessionId = $this->createWorkbookSession($siteId, $fileId);

        if ($sessionId === null) {
            return false;
        }

        $encodedSheetName = rawurlencode($sheetName);
        $headers = $this->authHeaders($sessionId);

        try {
            $usedRangeEndpoint = "sites/{$siteId}/drive/items/{$fileId}/workbook/worksheets/{$encodedSheetName}/usedRange";

            $response = $this->client->get($usedRangeEndpoint, [
                'headers' => $headers,
                'http_errors' => false,
            ]);

            $lastRowNumber = 1;
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $lastRowNumber = (int) ($data['rowCount'] ?? count($data['values'] ?? [[]]));
            }

            $nextRowNumber = max(2, $lastRowNumber + 1);
            $lastColumnLetter = $this->columnLetter(count($rowData));
            $targetRange = "A{$nextRowNumber}:{$lastColumnLetter}{$nextRowNumber}";
            $updateEndpoint = "sites/{$siteId}/drive/items/{$fileId}/workbook/worksheets/{$encodedSheetName}/range(address='{$targetRange}')";

            $response = $this->client->patch($updateEndpoint, [
                'headers' => $headers,
                'json' => ['values' => [$rowData]],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::error('SharePointController: append row failed', [
                    'range' => $targetRange,
                    'sheet' => $sheetName,
                    'status' => $response->getStatusCode(),
                    'body' => (string) $response->getBody(),
                ]);

                return false;
            }

            Log::info('SharePointController: row appended', [
                'sheet' => $sheetName,
                'range' => $targetRange,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('SharePointController: appendRowToExcel '.$e->getMessage());

            return false;
        } finally {
            $this->closeWorkbookSession($siteId, $fileId, $sessionId);
        }
    }

    public function run(int $id): bool
    {
        try {
            if (! $this->accessToken) {
                Log::error('SharePointController: no access token — check SharePoint settings');

                return false;
            }

            $siteName = $this->spSettings->site_name ?? null;
            $filePath = $this->spSettings->file_path ?? null;
            $fileName = $this->spSettings->file_name ?? null;

            if (! $siteName || ! $filePath || ! $fileName) {
                Log::error('SharePointController: missing site_name, file_path, or file_name in settings');

                return false;
            }

            $siteId = $this->getSiteId($siteName);
            $fileId = $this->getFileId($siteId, $filePath.'/'.$fileName);
            $sheet = $this->spSettings->sheet_name
                ?? config('services.sharepoint.sheet_name', self::DEFAULT_EXCEL_SHEET_NAME);

            if (! $siteId || ! $fileId) {
                Log::error('SharePointController: could not resolve SharePoint site or file', [
                    'site_name' => $siteName,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                ]);

                return false;
            }

            $claim = Claim::findOrFail($id);
            $row = $this->buildClaimRow($claim);

            if (count($row) !== self::EXCEL_COLUMN_COUNT) {
                Log::error('SharePointController: row column count mismatch', [
                    'expected' => self::EXCEL_COLUMN_COUNT,
                    'actual' => count($row),
                ]);

                return false;
            }

            return $this->appendRowToExcel($siteId, $fileId, $sheet, $row);
        } catch (Throwable $e) {
            Log::error('SharePointController: run failed', [
                'claim_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
