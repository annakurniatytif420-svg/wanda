<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
      Schema::create('chats', function (Blueprint $table) {
        $table->id();
        $table->foreignId('booking_id')->constrained()->onDelete('cascade');
        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
        $table->text('message');
        $table->boolean('read')->default(false);
        $table->timestamps();
      });       
    }        
};
