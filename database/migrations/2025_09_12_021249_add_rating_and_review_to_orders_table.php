<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');   // الربط مع الطلب
            $table->unsignedBigInteger('customer_id'); // من أعطى التقييم
            $table->unsignedBigInteger('driver_id');   // السائق الذي أخذ التقييم
            $table->tinyInteger('rating')->check('rating >= 1 and rating <= 5');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn(['rating', 'review']);
    });
}

};
