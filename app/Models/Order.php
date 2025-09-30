<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

   protected $fillable = [
    'customer_id','driver_id','station_id','type',
    'pickup_address','pickup_lat','pickup_lng',
    'dropoff_address','dropoff_lat','dropoff_lng',
    'parcel_description','parcel_weight','distance','duration','price',
    'payment_status','status','meta','region',
    'car_type','payment_method','notes','scheduled_at',   'package_type','pickup_location','dropoff_location'

];


    protected $casts = [
        'meta' => 'array',
        'price' => 'decimal:2',
        'distance' => 'decimal:2',
    ];

    public function customer(){ return $this->belongsTo(User::class,'customer_id'); }
    public function driver(){ return $this->belongsTo(User::class,'driver_id'); }
    public function station(){ return $this->belongsTo(Station::class,'station_id'); }
    public function payments(){ return $this->hasMany(Payment::class,'order_id'); }
}
