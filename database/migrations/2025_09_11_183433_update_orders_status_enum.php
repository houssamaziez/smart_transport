<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'accepted',
                'on_the_way',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'accepted',
                'arrived_at_pickup',
                'picked_up',
                'in_progress',
                'delivered',
                'completed',
                'canceled'
            ])->default('pending')->change();
        });
    }
};
