<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writeup_tools', function (Blueprint $table) {
            $table->unsignedBigInteger('writeup_id');
            $table->unsignedInteger('tool_id');

            $table->primary(['writeup_id', 'tool_id']);

            $table->foreign('writeup_id')->references('id')->on('writeups')->cascadeOnDelete();
            $table->foreign('tool_id')->references('id')->on('tools')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writeup_tools');
    }
};