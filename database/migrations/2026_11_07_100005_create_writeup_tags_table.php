<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writeup_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('writeup_id');
            $table->unsignedInteger('tag_id');

            $table->primary(['writeup_id', 'tag_id']);

            $table->foreign('writeup_id')->references('id')->on('writeups')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writeup_tags');
    }
};