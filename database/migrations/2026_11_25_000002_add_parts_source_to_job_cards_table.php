<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->string('parts_source', 20)->default('warehouse')->after('warehouse_id'); // warehouse, site, direct
            $table->string('service_location')->nullable()->after('parts_source');
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['parts_source', 'service_location']);
        });
    }
};
