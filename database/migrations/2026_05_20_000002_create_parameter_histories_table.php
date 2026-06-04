<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameter_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enclosure_id')->constrained()->onDelete('cascade');
            $table->foreignId('recommendation_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source')->default('manual'); // manual, ai_recommendation, system_default
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->decimal('old_bottom_humidity', 5, 2)->nullable();
            $table->decimal('old_top_humidity', 5, 2)->nullable();
            $table->unsignedInteger('old_duration_seconds')->nullable();

            $table->decimal('new_bottom_humidity', 5, 2);
            $table->decimal('new_top_humidity', 5, 2);
            $table->unsignedInteger('new_duration_seconds');

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['enclosure_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_histories');
    }
};
