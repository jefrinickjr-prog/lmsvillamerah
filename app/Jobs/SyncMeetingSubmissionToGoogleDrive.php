<?php

namespace App\Jobs;

use App\Models\MeetingSubmission;
use App\Services\GoogleSharedDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncMeetingSubmissionToGoogleDrive implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 180;
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public int $submissionId) {}

    public function handle(GoogleSharedDriveService $drive): void
    {
        $submission = MeetingSubmission::find($this->submissionId);
        if (! $submission) return;

        $submission->update(['drive_sync_status' => 'syncing', 'drive_sync_error' => null]);
        $file = $drive->sync($submission);
        $submission->update([
            'drive_sync_status' => 'synced',
            'drive_file_id' => $file['id'],
            'drive_web_view_link' => $file['webViewLink'],
            'drive_sync_error' => null,
            'drive_synced_at' => now(),
        ]);

        if (config('google-drive.delete_local_after_sync')) {
            Storage::disk('local')->delete($submission->work_path);
        }
    }

    public function failed(?Throwable $exception): void
    {
        MeetingSubmission::whereKey($this->submissionId)->update([
            'drive_sync_status' => 'failed',
            'drive_sync_error' => str($exception?->getMessage() ?: 'Sinkronisasi gagal.')->limit(2000),
        ]);
    }
}
