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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['customer','driver','station','admin'])->default('customer')->after('id');
            $table->string('phone')->unique()->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('status', ['active','inactive','banned'])->default('active')->after('avatar');
            $table->decimal('latitude',10,7)->nullable()->after('status');
            $table->decimal('longitude',10,7)->nullable()->after('latitude');
            $table->decimal('wallet_balance',10,2)->default(0)->after('longitude');
            $table->string('vehicle_type')->nullable()->after('wallet_balance');
            $table->string('license_number')->nullable()->after('vehicle_type');
            $table->timestamp('last_seen_at')->nullable()->after('license_number');
            $table->string('fcm_token')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role','phone','avatar','status',
                'latitude','longitude','wallet_balance',
                'vehicle_type','license_number','last_seen_at','fcm_token'
            ]);
        });
    }
};
