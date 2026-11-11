<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uploader_id');
            $table->unsignedBigInteger('writeup_id')->nullable();
            $table->string('original_name', 255);
            $table->string('stored_name', 255)->unique();
            $table->string('file_path', 500);
            $table->enum('file_ext', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->timestamp('created_at')->useCurrent();

            $table->index('writeup_id');

            $table->foreign('uploader_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('writeup_id')->references('id')->on('writeups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_uploads');
    }
};