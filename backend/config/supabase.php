<?php
/**
 * Supabase Client & Storage Service for PHP Backend
 * Book Banko
 */

require_once __DIR__ . '/constants.php';

class SupabaseService {
    private static ?SupabaseService $instance = null;

    private string $supabaseUrl;
    private string $anonKey;
    private string $serviceRoleKey;

    private function __construct() {
        $this->supabaseUrl = defined('SUPABASE_URL') ? SUPABASE_URL : (getenv('SUPABASE_URL') ?: '');
        $this->anonKey = defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : (getenv('SUPABASE_ANON_KEY') ?: '');
        $this->serviceRoleKey = defined('SUPABASE_SERVICE_ROLE_KEY') ? SUPABASE_SERVICE_ROLE_KEY : (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConfigured(): bool {
        return !empty($this->supabaseUrl) && (!empty($this->serviceRoleKey) || !empty($this->anonKey));
    }

    public function getStoragePublicUrl(string $bucket, string $path): string {
        if (empty($this->supabaseUrl)) {
            return '';
        }
        $cleanUrl = rtrim($this->supabaseUrl, '/');
        $cleanPath = ltrim($path, '/');
        return "{$cleanUrl}/storage/v1/object/public/{$bucket}/{$cleanPath}";
    }

    /**
     * Upload a file to Supabase Storage via REST API
     */
    public function uploadToStorage(string $bucket, string $destinationPath, string $sourceFilePath, string $mimeType = 'application/octet-stream'): array {
        if (!$this->isConfigured()) {
            return ['status' => false, 'message' => 'Supabase credentials are not configured.'];
        }

        $key = !empty($this->serviceRoleKey) ? $this->serviceRoleKey : $this->anonKey;
        $url = rtrim($this->supabaseUrl, '/') . "/storage/v1/object/{$bucket}/" . ltrim($destinationPath, '/');

        if (!file_exists($sourceFilePath)) {
            return ['status' => false, 'message' => 'Source file does not exist.'];
        }

        $fileData = file_get_contents($sourceFilePath);
        if ($fileData === false) {
            return ['status' => false, 'message' => 'Failed to read source file.'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$key}",
            "apikey: {$key}",
            "Content-Type: {$mimeType}",
            "x-upsert: true"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $publicUrl = $this->getStoragePublicUrl($bucket, $destinationPath);
            return [
                'status' => true,
                'path' => $destinationPath,
                'public_url' => $publicUrl,
                'message' => 'File uploaded successfully to Supabase Storage.'
            ];
        }

        return [
            'status' => false,
            'message' => "Supabase Storage Upload failed (HTTP {$httpCode}): " . ($response ?: $curlError)
        ];
    }

    /**
     * Delete a file from Supabase Storage
     */
    public function deleteFromStorage(string $bucket, array $prefixes): array {
        if (!$this->isConfigured()) {
            return ['status' => false, 'message' => 'Supabase credentials not configured.'];
        }

        $key = !empty($this->serviceRoleKey) ? $this->serviceRoleKey : $this->anonKey;
        $url = rtrim($this->supabaseUrl, '/') . "/storage/v1/object/{$bucket}";

        $payload = json_encode(['prefixes' => $prefixes]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$key}",
            "apikey: {$key}",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => ($httpCode >= 200 && $httpCode < 300),
            'response' => $response
        ];
    }
}
