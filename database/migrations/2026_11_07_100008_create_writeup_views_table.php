<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writeup_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('writeup_id');
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['writeup_id', 'viewed_at']);

            $table->foreign('writeup_id')->references('id')->on('writeups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writeup_views');
    }
};