<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id', 'type', 'plate_number', 'brand', 'model', 'color'
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
