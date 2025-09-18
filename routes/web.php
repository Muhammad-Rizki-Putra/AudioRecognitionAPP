<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/single', function () {
    return view('singlePage');
});

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

// == VIDEO PROCESSING ROUTES ==

// 1. Handles the AJAX file upload from the dashboard
Route::post('/video/upload', [VideoController::class, 'upload'])->name('video.upload');

// 2. Displays the results page with the video player
Route::get('/results/{filename}', [VideoController::class, 'playMultiple'])->name('video.results');

// 3. Streams the video file content to the <video> tag
Route::get('/stream/{filename}', [VideoController::class, 'streamVideo'])->name('video.stream');

// 4. Handles the AJAX request for conversion and Flask API call



// add on
Route::post('/process-video', [VideoController::class, 'processVideo'])->name('video.process');

Route::post('/process-youtube', [VideoController::class, 'processYouTube'])->name('video.process.youtube');

Route::get('/job-status/{cueSheet}', [VideoController::class, 'checkStatus']);

Route::get('/jobs/{jobId}', [VideoController::class, 'show']);
Route::post('/finalize-job/{jobId}', [VideoController::class, 'finalizeJob']);
Route::get('/cuesheet-item/{szcuesheetid}/{shitem}', [VideoController::class, 'showDetail']);