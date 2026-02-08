<?php

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
        Schema::create('rain_giveaways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by'); // Admin who created the rain
            $table->decimal('total_amount', 10, 2); // Total amount to distribute
            $table->decimal('amount_per_user', 10, 2); // Amount each winner gets
            $table->integer('num_winners'); // Number of winners
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('winners')->nullable(); // JSON array of winner user IDs
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
        
        Schema::create('rain_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rain_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount_won', 10, 2)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
            
            $table->foreign('rain_id')->references('id')->on('rain_giveaways')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['rain_id', 'user_id']); // STRICT: One claim per user per rain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rain_participants');
        Schema::dropIfExists('rain_giveaways');
    }
};
