<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Setting;
use App\Support\SharePointFileUrl;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SharePointController extends Controller
{
    private const EXCEL_SHEET_NAME = 'Automatizacija Klientas';

    private Client $client;
    private string $accessToken;
    private object $spSettings;
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
    private function getSharePointToken(){
        try {
            $tenantId = $this->spSettings->tenant_id;
            $clientId = $this->spSettings->client_id;
            $clientSecret = $this->spSettings->client_secret;
            $client = new Client();
            $response = $client->post(
                "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token",
                [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ]
                ]
            );
            $data = json_decode($response->getBody(), true);
            return $data['access_token'];

        } catch (\Exception $e) {
            Log::error('SharePointController: ' . $e->getMessage());
            return false;
        }

    }
    private function getSiteId(string $sharepointSiteUrl): string
    {
        if(!$this->accessToken) return false;
        $parsed = parse_url($sharepointSiteUrl);

        $host = $parsed['host'];
        $path = $parsed['path'];

        $endpoint = "sites/{$host}:{$path}";

        $response = $this->client->get($endpoint, [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['id'];
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

    private function getDriveItem(string $siteId, string $filePath): array|false
    {
        if (! $this->accessToken) {
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

        } catch (\Exception $e) {
            Log::error('SharePointController: '.$e->getMessage());
        }

        return false;
    }

    private function getFileId(string $siteId, string $filePath): string|false
    {
        $item = $this->getDriveItem($siteId, $filePath);

        if (! is_array($item)) {
            return false;
        }

        return $item['id'] ?? false;
    }
    private function appendRowToExcel(string $siteId, string $fileId, string $sheetName, array $rowData)
    {
        if(!$this->accessToken) return false;
        $encodedSheetName = rawurlencode($sheetName);
        $usedRangeEndpoint = "sites/{$siteId}/drive/items/{$fileId}/workbook/worksheets/{$encodedSheetName}/usedRange";

        try {
            $response = $this->client->get($usedRangeEndpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $lastRowIndex = $data['address'] ? count($data['values']) : 0;

        } catch (\Exception $e) {
            $lastRowIndex = 0;
        }
        $nextRowNumber = $lastRowIndex + 1;
        $lastColumnLetter = chr(64 + count($rowData));
        $targetRange = "A{$nextRowNumber}:{$lastColumnLetter}{$nextRowNumber}";
        $updateEndpoint = "sites/{$siteId}/drive/items/{$fileId}/workbook/worksheets/{$encodedSheetName}/range(address='{$targetRange}')";
        try {
            $response = $this->client->patch($updateEndpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'values' => [$rowData]
                ]
            ]);
        } catch (\Exception $e) {
            $error = "Nepavyko įrašyti duomenų į Excel rėžį {$targetRange}: " . $e->getMessage();
            Log::error('SharePointController: ' . $error);
        }
    }
    public function run(int $id)
    {
        try{
            $siteName = $this->spSettings->site_name;
            $siteId = $this->getSiteId($siteName);
            $filePath = $this->spSettings->file_path;
            $fileName = $this->spSettings->file_name;
            $fileId = $this->getFileId($siteId,$filePath.'/'.$fileName);
            $sheet = self::EXCEL_SHEET_NAME;
            $claim = Claim::findOrFail($id);
            $days = "";
            if($claim->rental_start and $claim->rental_end){
                $date1 = new DateTime($claim->rental_start);
                $date2 = new DateTime($claim->rental_end);
                $days = $date1->diff($date2)->days+1;
            }
            $row = [
                date_format($claim->created_at,"Y-m-d H:i"),
                $claim->repair_vehicle_plates,
                $claim->first_name." ".$claim->last_name,
                $claim->rental_start?date_format($claim->rental_start,"Y-m-d"):"",
                $claim->rental_end?date_format($claim->rental_end,"Y-m-d"):"",
                $days,
                $claim->id_or_passport_number,
                $claim->id_or_passport_expires_at?date_format($claim->id_or_passport_expires_at,"Y-m-d"):"",
                $claim->bank_card_number,
                $claim->bank_card_expires_at?date_format($claim->bank_card_expires_at,"Y-m-d"):"",
            ];
        } catch (\Exception $e) {
            Log::error('SharePointController: ' . $e->getMessage());
        }
        try {
            $this->appendRowToExcel($siteId, $fileId, $sheet, $row);
        } catch (\Exception $e) {
            Log::error('SharePointController: ' . $e->getMessage());
        }
    }
}
