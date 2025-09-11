<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Station;
use Illuminate\Support\Facades\Hash;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء محطة جديدة
        $station = Station::create([
            'name'      => 'محطة وسط المدينة',
            'slug'      => 'central-station',
            'phone'     => '0555000000',
            'address'   => 'الجزائر العاصمة - وسط المدينة',
            'latitude'  => 36.7538,
            'longitude' => 3.0588,
            'status'    => 'active',
        ]);

        // إنشاء حساب مستخدم للمحطة
        User::create([
            'name'     => $station->name,
            'email'    => 'centralstation@example.com',
            'phone'    => $station->phone,
            'password' => Hash::make('password123'),
            'role'     => 'station',
            'latitude' => $station->latitude,
            'longitude'=> $station->longitude,
        ]);
    }
}
