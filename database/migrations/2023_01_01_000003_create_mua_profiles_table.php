<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mua_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('certification')->nullable();
            $table->string('service_area')->nullable();
            $table->decimal('studio_lat', 10, 7)->nullable();
            $table->decimal('studio_lng', 10, 7)->nullable();
            $table->json('makeup_specializations')->nullable();
            $table->json('makeup_styles')->nullable();
            $table->timestamps();
        });        
        
    }        
};
