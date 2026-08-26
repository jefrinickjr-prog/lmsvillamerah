<?php

namespace App\Services;

use App\Models\MeetingSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSharedDriveService
{
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3';

    public function configured(): bool
    {
        return config('google-drive.enabled')
            && filled(config('google-drive.shared_drive_id'))
            && filled(config('google-drive.root_folder_id'))
            && is_readable((string) config('google-drive.service_account_path'));
    }

    public function sync(MeetingSubmission $submission): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Konfigurasi Google Workspace Shared Drive belum lengkap.');
        }

        $submission->loadMissing(['student', 'assignment.classroom']);
        $localPath = Storage::disk('local')->path($submission->work_path);
        if (! is_file($localPath)) {
            throw new RuntimeException('File karya lokal tidak ditemukan.');
        }

        $folderId = $this->ensureFolderPath($this->folderSegments($submission));
        $extension = pathinfo($localPath, PATHINFO_EXTENSION);
        $fileName = $this->safeName(sprintf(
            '%s - %s%s',
            $submission->student->student_code ?: 'Siswa-'.$submission->student_id,
            $submission->assignment->title,
            $extension ? '.'.$extension : ''
        ));

        $metadata = ['name' => $fileName, 'parents' => [$folderId]];
        $response = $this->uploadRequest($submission, $metadata, $localPath);

        return [
            'id' => $response['id'],
            'webViewLink' => $response['webViewLink'] ?? 'https://drive.google.com/open?id='.$response['id'],
        ];
    }

    public function testConnection(): array
    {
        if (! $this->configured()) throw new RuntimeException('Konfigurasi Google Workspace Shared Drive belum lengkap.');

        return Http::withToken($this->accessToken())->get(self::API.'/files/'.config('google-drive.root_folder_id'), [
            'supportsAllDrives' => 'true',
            'fields' => 'id,name,mimeType,driveId',
        ])->throw()->json();
    }

    private function uploadRequest(MeetingSubmission $submission, array $metadata, string $localPath): array
    {
        $token = $this->accessToken();
        $mime = mime_content_type($localPath) ?: 'application/octet-stream';
        $query = '?uploadType=multipart&supportsAllDrives=true&fields=id,name,webViewLink';
        $url = self::UPLOAD_API.'/files'.($submission->drive_file_id ? '/'.$submission->drive_file_id : '').$query;
        if ($submission->drive_file_id) unset($metadata['parents']);
        $request = Http::withToken($token)
            ->timeout(120)
            ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json; charset=UTF-8'])
            ->attach('file', fopen($localPath, 'rb'), basename($localPath), ['Content-Type' => $mime]);
        $response = $submission->drive_file_id ? $request->patch($url) : $request->post($url);

        if ($submission->drive_file_id && $response->status() === 404) {
            $submission->drive_file_id = null;
            return $this->uploadRequest($submission, $metadata, $localPath);
        }

        return $response->throw()->json();
    }

    private function ensureFolderPath(array $segments): string
    {
        $parentId = (string) config('google-drive.root_folder_id');
        $currentPath = [];
        foreach ($segments as $segment) {
            $name = $this->safeName($segment);
            $currentPath[] = $name;
            $path = implode('/', $currentPath);
            $hash = hash('sha256', $path);
            $cached = DB::table('google_drive_folders')->where('path_hash', $hash)->value('folder_id');
            if ($cached) {
                $parentId = $cached;
                continue;
            }

            $folderId = $this->findFolder($name, $parentId) ?: $this->createFolder($name, $parentId);
            DB::table('google_drive_folders')->updateOrInsert(
                ['path_hash' => $hash],
                ['folder_path' => $path, 'folder_id' => $folderId, 'created_at' => now(), 'updated_at' => now()]
            );
            $parentId = $folderId;
        }

        return $parentId;
    }

    private function findFolder(string $name, string $parentId): ?string
    {
        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $name);
        $response = Http::withToken($this->accessToken())->retry(3, 500)->get(self::API.'/files', [
            'q' => "name = '{$escaped}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'corpora' => 'drive',
            'driveId' => config('google-drive.shared_drive_id'),
            'includeItemsFromAllDrives' => 'true',
            'supportsAllDrives' => 'true',
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ])->throw()->json();

        return $response['files'][0]['id'] ?? null;
    }

    private function createFolder(string $name, string $parentId): string
    {
        $response = Http::withToken($this->accessToken())->retry(3, 500)
            ->post(self::API.'/files?supportsAllDrives=true&fields=id', [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ])->throw()->json();

        return $response['id'];
    }

    private function accessToken(): string
    {
        return cache()->remember('google-drive-service-token', 3300, function (): string {
            $credentials = json_decode(file_get_contents((string) config('google-drive.service_account_path')), true, flags: JSON_THROW_ON_ERROR);
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/drive',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsigned = $header.'.'.$claims;
            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Service account Google tidak dapat menandatangani token.');
            }
            $assertion = $unsigned.'.'.$this->base64Url($signature);
            $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])->throw()->json();

            return $response['access_token'];
        });
    }

    private function folderSegments(MeetingSubmission $submission): array
    {
        $student = $submission->student;
        $assignment = $submission->assignment;
        $classroom = $assignment->classroom;

        return [
            $student->academic_year ?: $assignment->meeting_date->format('Y').'-'.($assignment->meeting_date->year + 1),
            $classroom->title.' - '.$classroom->branch,
            ($student->student_code ?: 'Siswa-'.$student->id).' - '.$student->name,
            $assignment->meeting_date->format('Y-m-d').' - '.$assignment->title,
        ];
    }

    private function safeName(string $name): string
    {
        return Str::limit(trim(preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $name)), 180, '');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
