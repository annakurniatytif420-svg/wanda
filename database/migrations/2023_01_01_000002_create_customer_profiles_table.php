<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('skin_tone')->nullable();
            $table->string('skin_type')->nullable();
            $table->json('skin_issues')->nullable();
            $table->text('skincare_history')->nullable();
            $table->text('allergies')->nullable();
            $table->text('makeup_preferences')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();
        });
        
    }        
};
