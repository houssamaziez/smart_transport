<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'order_id',
        'amount',
    ];

    // العلاقة مع السائق
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // العلاقة مع الطلب
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
