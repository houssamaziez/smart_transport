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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('station_id')->nullable()->constrained('stations')->onDelete('set null');

            $table->enum('type',['ride','parcel'])->default('ride');

            // pickup
            $table->string('pickup_address');
            $table->decimal('pickup_lat',10,7)->nullable();
            $table->decimal('pickup_lng',10,7)->nullable();

            // dropoff
            $table->string('dropoff_address')->nullable();
            $table->decimal('dropoff_lat',10,7)->nullable();
            $table->decimal('dropoff_lng',10,7)->nullable();

            // parcel details
            $table->string('parcel_description')->nullable();
            $table->decimal('parcel_weight',8,2)->nullable();

            // metrics
            $table->decimal('distance',8,2)->nullable();
            $table->decimal('duration',8,2)->nullable();

            $table->decimal('price',10,2)->default(0);

            // ✅ new fields
            $table->enum('car_type', ['economy','comfort','luxury','family'])->nullable();
            $table->enum('payment_method', ['cash','card','wallet'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->enum('payment_status',['pending','paid'])->default('pending');

            $table->enum('status',[
                'pending','accepted','arrived_at_pickup','picked_up','in_progress','delivered','completed','canceled'
            ])->default('pending');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
