<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_users', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_admin')->default(false);

            $table->string('nickname')->nullable();

            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id');

            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_users');
    }
};
