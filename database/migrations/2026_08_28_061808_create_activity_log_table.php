<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 255)->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->string('event')->nullable();
            $table->string('causer_type')->nullable();
            $table->string('causer_id', 255)->nullable();
            $table->index(['causer_type', 'causer_id']);
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }
};
