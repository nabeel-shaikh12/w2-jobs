<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resume_downloads', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('user_id'); // User who downloads the resume
            $table->string('resume_path'); // Path to the downloaded resume
            $table->timestamp('downloaded_at')->useCurrent(); // Timestamp of the download event
            $table->timestamps(); // Created_at and Updated_at timestamps

            // Foreign key constraint to the users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resume_downloads');
    }
};

