<?php

namespace App\Support;

use App\Http\Controllers\SharePointController;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SharePointFileUrl
{
    /**
     * Browser URL for the SharePoint Excel file (Excel Online / Doc.aspx format).
     */
    public static function fromSettings(): ?string
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        return Cache::remember('sharepoint.excel.browser_url', now()->addHours(12), function (): ?string {
            $settings = Setting::query()
                ->where('scope', 'sharepoint')
                ->pluck('value', 'setting')
                ->all();

            $built = self::buildOpenUrlFromSettings($settings);

            if ($built !== null) {
                return $built;
            }

            try {
                return (new SharePointController)->getExcelWebUrl();
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @param  array<string, string>  $settings
     */
    public static function buildOpenUrlFromSettings(array $settings): ?string
    {
        foreach (['browser_url', 'web_url', 'excel_url'] as $key) {
            if (! empty($settings[$key])) {
                return $settings[$key];
            }
        }

        $siteName = $settings['site_name'] ?? null;
        $filePath = $settings['file_path'] ?? null;
        $fileName = $settings['file_name'] ?? null;

        if (! $siteName || ! $filePath || ! $fileName) {
            return null;
        }

        $site = self::parseSiteName($siteName);

        if ($site === null) {
            return null;
        }

        $sourcedoc = self::sourcedocFromSettings($settings);

        if ($sourcedoc !== null) {
            return self::buildDocAspxUrl($site['host'], $site['path'], $sourcedoc, $fileName);
        }

        return null;
    }

    /**
     * Excel Online link used in SharePoint (same shape as “Copy link” in the browser).
     *
     * Example:
     * https://greenlease.sharepoint.com/:x:/r/sites/Saugykla/_layouts/15/Doc.aspx?sourcedoc={GUID}&file=Klientu
     */
    public static function buildDocAspxUrl(string $host, string $sitePath, string $sourcedoc, string $fileName): string
    {
        $sourcedoc = trim($sourcedoc);
        $sourcedoc = trim($sourcedoc, '{}');
        $sourcedoc = '{'.strtoupper($sourcedoc).'}';

        $sitePath = '/'.trim($sitePath, '/');

        $fileLabel = self::fileQueryLabel($fileName);

        return 'https://'.$host.'/:x:/r'.$sitePath
            .'/_layouts/15/Doc.aspx?sourcedoc='.$sourcedoc
            .'&file='.rawurlencode($fileLabel);
    }

    /**
     * Parse site_name the same way SharePointController uses it for Graph (host + /sites/...).
     *
     * @return array{host: string, path: string}|null
     */
    public static function parseSiteName(string $siteName): ?array
    {
        if (preg_match('#^(https://[^/]+):?(/sites/.+)$#', $siteName, $matches)) {
            $host = parse_url($matches[1], PHP_URL_HOST);

            return [
                'host' => $host ?: parse_url($matches[1].$matches[2], PHP_URL_HOST),
                'path' => rtrim($matches[2], '/'),
            ];
        }

        $parsed = parse_url($siteName);

        if (empty($parsed['host']) || empty($parsed['path'])) {
            return null;
        }

        return [
            'host' => $parsed['host'],
            'path' => rtrim($parsed['path'], '/'),
        ];
    }

    /**
     * Encode drive-relative path the same way SharePointController::getFileId() does.
     */
    public static function encodeDriveRelativePath(string $filePath, string $fileName): string
    {
        $relativePath = trim($filePath, '/').'/'.ltrim($fileName, '/');

        return implode('/', array_map(rawurlencode(...), explode('/', $relativePath)));
    }

    /**
     * @param  array<string, string>  $settings
     */
    private static function sourcedocFromSettings(array $settings): ?string
    {
        foreach (['file_guid', 'sourcedoc', 'file_unique_id', 'list_item_unique_id'] as $key) {
            if (! empty($settings[$key])) {
                return $settings[$key];
            }
        }

        return null;
    }

    private static function fileQueryLabel(string $fileName): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);

        if ($base === '') {
            return $fileName;
        }

        $firstWord = explode(' ', $base)[0] ?? $base;

        return $firstWord !== '' ? $firstWord : $base;
    }
}
