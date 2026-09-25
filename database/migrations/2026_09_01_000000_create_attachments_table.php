<?php

declare(strict_types=1);

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
        Schema::dropIfExists('attachables');
        Schema::dropIfExists('attachments');

        Schema::create('attachments', function (Blueprint $table): void {
            $user_table = config('attachment.user_table', 'users');
            $user_key_name = config('attachment.user_key_name', 'id');
            $user_key_type = config('attachment.user_key_type', 'uuid');
            $user_column = 'user_id';

            $table->id();

            if ($user_key_type === 'uuid') {
                $table->uuid($user_column)->nullable();
            } elseif ($user_key_type === 'int') {
                $table->unsignedBigInteger($user_column)->nullable();
            } else {
                $table->string($user_column)->nullable();
            }

            $table->foreign($user_column)->references($user_key_name)->on($user_table)->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('path');
            $table->string('disk')->default('public')->index();
            $table->string('mime');
            $table->bigInteger('size');
            $table->boolean('is_private')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('attachables', function (Blueprint $table): void {
            $table->foreignId('attachment_id')->constrained('attachments')->cascadeOnDelete();
            $table->string('attachable_type');
            $table->string('attachable_id');

            $table->index(['attachable_type', 'attachable_id']);
            $table->unique(['attachment_id', 'attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachables');
        Schema::dropIfExists('attachments');
    }
};
