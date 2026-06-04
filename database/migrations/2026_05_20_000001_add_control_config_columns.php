<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enclosures', function (Blueprint $table) {
            if (!Schema::hasColumn('enclosures', 'device_key')) {
                $table->string('device_key')->nullable()->unique()->after('last_seen_at');
            }
        });

        Schema::table('enclosure_parameters', function (Blueprint $table) {
            if (!Schema::hasColumn('enclosure_parameters', 'misting_duration_seconds')) {
                $table->unsignedInteger('misting_duration_seconds')->default(10)->after('misting_top_threshold');
            }
        });

        Schema::table('sensor_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('sensor_logs', 'misting_duration_executed')) {
                $table->unsignedInteger('misting_duration_executed')->nullable()->after('misting_status');
            }

            if (!Schema::hasColumn('sensor_logs', 'device_timestamp')) {
                $table->timestamp('device_timestamp')->nullable()->after('logged_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensor_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sensor_logs', 'device_timestamp')) {
                $table->dropColumn('device_timestamp');
            }

            if (Schema::hasColumn('sensor_logs', 'misting_duration_executed')) {
                $table->dropColumn('misting_duration_executed');
            }
        });

        Schema::table('enclosure_parameters', function (Blueprint $table) {
            if (Schema::hasColumn('enclosure_parameters', 'misting_duration_seconds')) {
                $table->dropColumn('misting_duration_seconds');
            }
        });

        Schema::table('enclosures', function (Blueprint $table) {
            if (Schema::hasColumn('enclosures', 'device_key')) {
                $table->dropUnique(['device_key']);
                $table->dropColumn('device_key');
            }
        });
    }
};
