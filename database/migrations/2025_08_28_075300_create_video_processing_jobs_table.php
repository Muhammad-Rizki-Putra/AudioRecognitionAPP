<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('video_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('source'); // 'upload' or 'youtube'
            $table->text('url')->nullable(); // For YouTube URLs
            $table->string('status'); // 'queued', 'processing', 'completed', 'failed'
            $table->string('job_id')->nullable(); // Laravel queue job ID
            $table->json('results')->nullable(); // Store recognition results
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_processing_jobs');
    }
};