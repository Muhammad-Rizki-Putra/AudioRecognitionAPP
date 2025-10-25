<?php

namespace App\Jobs;

use App\Models\VideoProcessingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobRecordId;
    protected $filename;
    protected $source;
    protected $url;

    public function __construct(int $jobRecordId, string $filename, string $source, ?string $url = null)
    {
        $this->jobRecordId = $jobRecordId;
        $this->filename = $filename;
        $this->source = $source;
        $this->url = $url;
    }

    public function handle()
    {
        // Find the database record
        $jobRecord = VideoProcessingJob::find($this->jobRecordId);

        if (!$jobRecord) {
            Log::error("Job record not found for ID: {$this->jobRecordId}");
            return;
        }

        // Update the job_id and status in the database
        $jobRecord->update([
            'job_id' => $this->job->getJobId(), // Get the unique ID from the job
            'status' => 'processing'
        ]);

        try {
            // Update status to processing
            $jobRecord->update(['status' => 'processing']);

            $videoPath = null;

            // Handle YouTube downloads
            if ($this->source === 'youtube') {
                $videoPath = $this->downloadYouTubeVideo($this->url, $this->filename);
            } else {
                // For uploaded videos, get the path D:\Berkas_Rizki\Semester_7\Magang\web-demo\web-0\storage\app\private\public\videos
                $videoPath = storage_path('app/private/public/videos/' . $this->filename);
            }

            if (!$videoPath || !file_exists($videoPath)) {
                throw new \Exception("Video file not found at path: " . $videoPath);
            }

            // Process the video (your existing logic)
            $mp3Path = $this->convertToMp3($videoPath);
            $mp3Filename = basename($mp3Path);
            $flaskResults = $this->sendToFlaskApi($mp3Path, $mp3Filename);

            // Clean up MP3 file
            if (file_exists($mp3Path)) {
                unlink($mp3Path);
            }

            // Update job record with results
            $jobRecord->update([
                'status' => 'completed',
                'results' => $flaskResults['results'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error("Video processing job failed: " . $e->getMessage());
            $jobRecord->update(['status' => 'failed']);
            $this->fail($e);
        }
    }

    // Move your helper methods here from the controller
    private function downloadYouTubeVideo($url, $filename)
    {
        $downloadDir = storage_path('app/private/public/videos');
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        $outputPath = $downloadDir . DIRECTORY_SEPARATOR . $filename;
        $ytDlpPath = env('YT_DLP_PATH', 'yt-dlp');

        // This line is the fix: No quotes around %s for output and url
        $command = sprintf(
            '%s --no-playlist --no-overwrites --no-post-overwrites --no-keep-video --no-cache-dir --no-mtime --no-part --no-write-thumbnail --no-write-description --no-write-info-json --no-write-annotations --no-write-sub --convert-subs none -f "best[ext=mp4]" -o %s %s',
            escapeshellarg($ytDlpPath),
            escapeshellarg($outputPath),
            escapeshellarg($url)
        );

        Log::info("Executing YouTube download command: " . $command);

        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);

        Log::info("yt-dlp output: " . implode("\n", $output));

        if ($returnCode !== 0) {
            throw new \Exception("yt-dlp failed: " . implode("\n", $output));
        }

        if (!file_exists($outputPath)) {
            throw new \Exception("Downloaded file not found at: " . $outputPath);
        }

        return $outputPath;
    }

    private function convertToMp3($videoPath)
    {
        $audioDir = storage_path('app/private/public/audio') . DIRECTORY_SEPARATOR;
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0755, true);
        }

        $mp3Filename = pathinfo($videoPath, PATHINFO_FILENAME) . '.mp3';
        $fullMp3Path = $audioDir . $mp3Filename;

        $command = "ffmpeg -y -i " . escapeshellarg($videoPath) . " -vn -ar 44100 -ac 2 -b:a 192k " . escapeshellarg($fullMp3Path) . " 2>&1";

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($fullMp3Path)) {
            throw new \Exception("FFmpeg conversion failed: " . implode("\n", $output));
        }

        return $fullMp3Path;
    }

    private function sendToFlaskApi($mp3Path, $mp3Filename)
    {
        $flaskUrl = 'http://localhost:5000/recognize';

        $response = Http::timeout(300)->attach(
            'audio_file',
            file_get_contents($mp3Path),
            $mp3Filename
        )->post($flaskUrl, [
                    'mode' => 'multiple',
                    'segment_duration' => 15,
                    'min_confidence' => 10,
                    'overlap' => 5
                ]);

        if ($response->failed()) {
            throw new \Exception('Flask API request failed: ' . $response->body());
        }

        return $response->json();
    }
}