<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoProcessingJob extends Model
{
    use HasFactory;

    protected $table = 'video_processing_jobs';

    protected $fillable = [
        'filename',
        'source',
        'url',
        'status',
        'job_id',
        'results'
    ];

    protected $casts = [
        'results' => 'json'
    ];
}