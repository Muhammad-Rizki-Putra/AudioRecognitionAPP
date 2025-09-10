<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessVideoJob;
use getID3;
use App\Models\VideoProcessingJob;


class VideoController extends Controller
{
    /**
     * ✅ Handles the entire single-page video processing workflow.
     * Receives an uploaded video, converts it to MP3, gets song recognition results,
     * and returns all necessary data as a single JSON response.
     */

    public function processVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,mkv|max:500000',
        ]);

        try {
            $videoFile = $request->file('video');
            $videoFilename = $videoFile->hashName();
            $videoFile->storeAs('/public/videos', $videoFilename);

            // Create a job record in database
            $jobRecord = VideoProcessingJob::create([
                'filename' => $videoFilename,
                'source' => 'upload',
                'status' => 'queued',
                'job_id' => null,
            ]);

            // Dispatch the job to the queue
            $job = new ProcessVideoJob($jobRecord->id, $videoFilename, 'upload');
            dispatch($job); // Just dispatch the job, don't try to get a return value

            return response()->json([
                'success' => true,
                'message' => 'Video queued for processing',
                'job_id' => $jobRecord->id 
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function processYouTube(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        try {
            $url = $request->input('url');

            if (!$this->isValidYouTubeUrl($url)) {
                throw new \Exception("Invalid YouTube URL");
            }

            // Generate a unique filename for the future download
            $filename = 'youtube_' . uniqid() . '.mp4';

            // Create a job record in database
            $jobRecord = VideoProcessingJob::create([
                'filename' => $filename,
                'source' => 'youtube',
                'url' => $url,
                'status' => 'queued',
                'job_id' => null, // The job_id will remain null
            ]);

            // Dispatch the job to the queue without getting a return value
            $job = new ProcessVideoJob($jobRecord->id, $filename, 'youtube', $url);
            dispatch($job);

            // Don't update the job record with the job ID
            // $jobRecord->update(['job_id' => $jobId]);

            return response()->json([
                'success' => true,
                'message' => 'YouTube video queued for processing',
                'job_id' => $jobRecord->id // Return your custom database ID
            ]);

        } catch (\Exception $e) {
            \Log::error('YouTube processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue YouTube video: ' . $e->getMessage()
            ], 500);
        }
    }

    // Add a method to check job status
    public function checkStatus($jobId)
    {
        try {
            \Log::info('Checking status for job id: ' . $jobId);
            $job = VideoProcessingJob::findOrFail($jobId);
            $duration = null;

            if ($job->status === 'completed') {
                $videoPath = storage_path('app/private/public/videos/' . $job->filename);
                \Log::info('Checking for file at path: ' . $videoPath);
            
                if (file_exists($videoPath)) {
                    if (file_exists($videoPath)) {
                        $getID3 = new getID3;
                        $fileInfo = $getID3->analyze($videoPath);
                        $duration = $fileInfo['playtime_seconds'] ?? 0;
                    }
                } else {
                    \Log::warning('File not found at path: ' . $videoPath);
                }
            }
            return response()->json([
                'status' => $job->status,
                'duration' => $duration,
                'results' => $job->results,
                'video_url' => $job->status === 'completed' ?
                    route('video.stream', ['filename' => $job->filename]) : null
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Job not found'], 404);
        }
    }


    // Helper method to validate YouTube URLs
    private function isValidYouTubeUrl($url)
    {
        $patterns = [
            '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+$/',
            '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+/',
            '/^https?:\/\/youtu\.be\/[\w-]+/',
            '/^https?:\/\/(www\.)?youtube\.com\/embed\/[\w-]+/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🛠️ HELPER: Processes a local video file (converts to MP3, sends for recognition).
     */
    private function processDownloadedVideo($videoPath)
    {
        // Convert the video to an MP3 file
        $mp3Path = $this->convertToMp3($videoPath);
        $mp3Filename = basename($mp3Path);

        // Send the new MP3 to the Flask API for recognition
        $flaskResults = $this->sendToFlaskApi($mp3Path, $mp3Filename);

        // Clean up the temporary MP3 file
        if (file_exists($mp3Path)) {
            unlink($mp3Path);
        }

        // Return only the recognition results
        return $flaskResults['results'] ?? [];
    }

    /**
     * Streams the stored video file to the browser.
     * This method is essential for the HTML <video> tag's 'src' attribute.
     */
    public function streamVideo($filename)
    {
        $path = 'public/videos/' . $filename;

        if (!Storage::exists($path)) {
            abort(404, 'Video file not found.');
        }

        $stream = Storage::readStream($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => Storage::mimeType($path),
            'Content-Length' => Storage::size($path),
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * 🛠️ HELPER: Converts a video file to an MP3.
     * (This is your existing logic, slightly cleaned up for robustness)
     */
    private function convertToMp3($videoPath)
    {
        $audioDirRelativePath = implode(DIRECTORY_SEPARATOR, ['app', 'private', 'public', 'audio']);
        $audioDir = storage_path($audioDirRelativePath) . DIRECTORY_SEPARATOR;
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0755, true);
        }

        $mp3Filename = pathinfo($videoPath, PATHINFO_FILENAME) . '.mp3';
        $fullMp3Path = $audioDir . $mp3Filename;

        $command = "ffmpeg -y -i " . escapeshellarg($videoPath) . " -vn -ar 44100 -ac 2 -b:a 192k " . escapeshellarg($fullMp3Path) . " 2>&1";

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($fullMp3Path)) {
            throw new Exception("FFmpeg conversion failed: " . implode("\n", $output));
        }

        return $fullMp3Path;
    }

    /**
     * 🛠️ HELPER: Sends an MP3 file to the Flask API for song recognition.
     * (This is your existing logic, unchanged)
     */
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
            throw new Exception('Flask API request failed: ' . $response->body());
        }

        return $response->json();
    }

    public function show($id)
    {
        // This is the correct way to find a record by its primary key
        $job = VideoProcessingJob::find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json($job);
    }
}