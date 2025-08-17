<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('customer_profiles', function (Blueprint $table) {
            // PostgreSQL compatible way to change column types
            $table->json('skin_type')->nullable()->change();
            $table->json('makeup_preferences')->nullable()->change();
        });
    }
    
    public function down(): void {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('skin_type')->nullable()->change();
            $table->text('makeup_preferences')->nullable()->change();
        });
    }
};
