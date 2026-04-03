<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('p2p_peers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->boolean('status')->default(1); // 1 = online, 0 = offline
            $table->decimal('min_limit', 15, 2)->default(100.00);
            $table->decimal('max_limit', 15, 2)->default(100000.00);
            $table->decimal('success_rate', 5, 2)->default(99.00);
            $table->string('avg_time')->default('2-5 mins');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('p2_p_peers');
    }
};
