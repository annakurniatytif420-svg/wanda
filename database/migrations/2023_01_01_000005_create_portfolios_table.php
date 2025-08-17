<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mua_id')->constrained('users')->onDelete('cascade');
            $table->enum('media_type', ['image', 'video']);
            $table->string('media_url');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
        
    }        
};
