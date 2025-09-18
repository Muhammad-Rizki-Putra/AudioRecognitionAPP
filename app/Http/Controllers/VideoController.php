<?php
declare(strict_types=1);

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessVideoJob;
use getID3;
use App\Models\VideoProcessingJob;
use Supabase\CreateClient;
use App\Models\CalaTrxFile;
use App\Models\CalaTrxCueSheet;
use App\Models\CalaTrxCueSheetItem;

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

    public function finalizeJob($jobId)
    {
        try {
            $job = VideoProcessingJob::findOrFail($jobId);

            if ($job->status !== 'completed') {
                return response()->json(['error' => 'Job is not yet complete.'], 409);
            }

            $videoPath = storage_path('app/private/public/videos/' . $job->filename);
            if (!file_exists($videoPath)) {
                throw new \Exception("Video file not found for finalization: " . $videoPath);
            }

            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($videoPath);

            // Get metadata for the cala_trx_file table
            $fileId = 'TRXF_' . uniqid();
            $fileDuration = $fileInfo['playtime_seconds'] ?? 0;
            $fileSizeInMb = round(($fileInfo['filesize'] ?? filesize($videoPath)) / 1048576, 2);
            $fileFormat = $fileInfo['fileformat'] ?? pathinfo($videoPath, PATHINFO_EXTENSION);
            $fileUrl = route('video.stream', ['filename' => $job->filename]);

            // Create the file record (this should automatically sync to Supabase if your model is configured)
            $trxFile = CalaTrxFile::create([
                'szfileid' => $fileId,
                'szfilename' => $job->filename,
                'szurl' => $fileUrl,
                'szformat' => $fileFormat,
                'dtmduration' => now(), // Using timestamp instead of duration calculation
                'decsizemb' => $fileSizeInMb,
                'szdescription' => 'File processed from job: ' . $jobId,
                'szstatus' => 'completed',
                'bactive' => true,
                'szcreatedby' => 'system',
                'szupdatedby' => 'system',
                'dtmcreated' => now(),
                'dtmupdated' => now(),
            ]);

            // Parse the job results to get song recognition data
            $results = $job->results;

            // Handle different result formats
            if (is_string($results)) {
                $results = json_decode($results, true);
            } elseif (is_null($results)) {
                $results = [];
            }

            $recognizedSongs = $results['results'] ?? $results ?? [];

            // Get metadata for the cue sheet
            $cueSheetId = 'CS_' . uniqid();
            $totalSongs = count($recognizedSongs);

            // Create the cue sheet record
            $trxCueSheet = CalaTrxCueSheet::create([
                'szcuesheetid' => $cueSheetId,
                'szfileid' => $trxFile->szfileid,
                'dectotalsongs' => $totalSongs,
                'dtmtotalduration' => now(), // Using timestamp instead of duration calculation
                'bactive' => true,
                'szcreatedby' => 'system',
                'szupdatedby' => 'system',
                'dtmcreated' => now(),
                'dtmupdated' => now(),
            ]);

            // Create cue sheet items from real recognition results
            $createdItems = [];
            foreach ($recognizedSongs as $index => $song) {
                // Extract song information from the recognition results
                $songTitle = $song['song'] ?? 'Unknown Title';

                // Parse artist and title from the song field (format: "artist - title")
                $songParts = explode(' - ', $songTitle, 2);
                $artistName = count($songParts) > 1 ? trim($songParts[0]) : 'Unknown Artist';
                $actualTitle = count($songParts) > 1 ? trim($songParts[1]) : $songTitle;

                // Parse position (format: "0:00 - 3:55")
                $position = $song['position'] ?? '0:00 - 0:15';
                $positionParts = explode(' - ', $position);
                $startTimeStr = trim($positionParts[0] ?? '0:00');
                $endTimeStr = trim($positionParts[1] ?? '0:15');

                // Convert time strings to seconds
                $startTime = $this->timeStringToSeconds($startTimeStr);
                $endTime = $this->timeStringToSeconds($endTimeStr);

                $confidence = $song['confidence'] ?? 0;

                // Generate IDs
                $songId = $this->generateOrLookupSongId($actualTitle, $artistName);
                $artistId = $this->generateOrLookupArtistId($artistName);
                $composerId = $this->generateOrLookupComposerId($artistName);

                try {
                    $trxCueSheetItem = CalaTrxCueSheetItem::create([
                        'szcuesheetid' => $trxCueSheet->szcuesheetid,
                        'szfileid' => $trxFile->szfileid,
                        'shitem' => $index + 1,
                        'szswc' => $song['swc'] ?? ('SWC_' . uniqid()),
                        'szisrc' => $song['isrc'] ?? ('ISRC_' . uniqid()),
                        'songid' => $songId,
                        'szsongtitle' => $actualTitle,
                        'szcomposerid' => $composerId,
                        'szcomposername' => $artistName,
                        'szartistid' => $artistId,
                        'szartistname' => $artistName,
                        'dtmstarttime' => now(), // Using timestamp
                        'dtmendtime' => now(), // Using timestamp
                        'dtmduration' => now(), // Using timestamp
                        'rn_songsong_id' => $confidence,
                        'bactive' => true,
                        'szcreatedby' => 'system',
                        'szupdatedby' => 'system',
                        'dtmcreated' => now(),
                        'dtmupdated' => now(),
                    ]);

                    $createdItems[] = $trxCueSheetItem;
                } catch (\Exception $itemException) {
                    Log::warning("Failed to create cue sheet item for song: " . $actualTitle . " - " . $itemException->getMessage());
                    continue;
                }
            }

            // If no songs were recognized or all failed, create a placeholder item
            if (empty($createdItems)) {
                $trxCueSheetItem = CalaTrxCueSheetItem::create([
                    'szcuesheetid' => $trxCueSheet->szcuesheetid,
                    'szfileid' => $trxFile->szfileid,
                    'shitem' => 1,
                    'szswc' => 'SWC_NO_RECOGNITION',
                    'szisrc' => 'ISRC_NO_RECOGNITION',
                    'songid' => 'SONG_NO_RECOGNITION',
                    'szsongtitle' => 'No Songs Recognized',
                    'szcomposerid' => 'COMP_NO_RECOGNITION',
                    'szcomposername' => 'Unknown',
                    'szartistid' => 'ART_NO_RECOGNITION',
                    'szartistname' => 'Unknown',
                    'dtmstarttime' => now(),
                    'dtmendtime' => now(),
                    'dtmduration' => now(),
                    'rn_songsong_id' => 0,
                    'bactive' => true,
                    'szcreatedby' => 'system',
                    'szupdatedby' => 'system',
                    'dtmcreated' => now(),
                    'dtmupdated' => now(),
                ]);

                $createdItems[] = $trxCueSheetItem;
            }

            return response()->json([
                'success' => true,
                'message' => 'File and cue sheet records finalized successfully.',
                'data' => [
                    'file' => $trxFile,
                    'cue_sheet' => $trxCueSheet,
                    'items' => $createdItems,
                    'total_songs_recognized' => count($recognizedSongs),
                    'total_items_created' => count($createdItems)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to finalize job {$jobId}: " . $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['error' => 'Failed to finalize job record: ' . $e->getMessage()], 500);
        }
    }

    private function generateOrLookupSongId($title, $artist)
    {
        // First, try to find existing song in CALA_MDM_SONGS table
        // This is a placeholder - implement according to your database structure

        // For now, generate a unique ID
        return 'SONG_' . md5($title . $artist);
    }

    /**
     * Helper method to generate or lookup artist ID
     */
    private function generateOrLookupArtistId($artistName)
    {
        // First, try to find existing artist in your artist table
        // This is a placeholder - implement according to your database structure

        // For now, generate a unique ID
        return 'ART_' . md5($artistName);
    }

    /**
     * Helper method to generate or lookup composer ID
     */
    private function generateOrLookupComposerId($composerName)
    {
        // First, try to find existing composer in your composer table
        // This is a placeholder - implement according to your database structure

        // For now, generate a unique ID
        return 'COMP_' . md5($composerName);
    }

    private function timeStringToSeconds($timeString)
    {
        $parts = explode(':', $timeString);
        $seconds = 0;

        if (count($parts) == 2) {
            // MM:SS format
            $seconds = (int) $parts[0] * 60 + (int) $parts[1];
        } elseif (count($parts) == 3) {
            // H:MM:SS format
            $seconds = (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
        }

        return $seconds;
    }

    /**
     * Helper method to convert seconds to time format (HH:MM:SS)
     */
    private function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function showDetail($szcuesheetid, $shitem)
    {
        $item = CalaTrxCueSheetItem::where([
            ['szcuesheetid', $szcuesheetid],
            ['shitem', $shitem]
        ])->first();

        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        return response()->json([
            'song_title' => $item->szsongtitle,
            'artist_name' => $item->szartistname,
            'composer_name' => $item->szcomposername,
            // Add more fields if needed
        ]);
    }
}