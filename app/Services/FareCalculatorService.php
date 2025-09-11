<?php
namespace App\Services;

class FareCalculatorService
{
    // مثال بسيط: base fare + per_km * distance
    protected $base = 1.00;
    protected $perKm = 0.5;

    public function calculate(array $data)
    {
        // إذا جلبت distance من Google Matrix في meta، استخدمه
        $distance = $data['distance'] ?? null;
        if(!$distance && isset($data['pickup_lat'],$data['dropoff_lat'])){
            // call GeoService to get distance (implement)
            // $distance = (new GeoService)->distance(...)
            $distance = 1.0; // placeholder
        }
        $price = $this->base + ($this->perKm * ($distance ?? 0));
        return round($price,2);
    }
}
