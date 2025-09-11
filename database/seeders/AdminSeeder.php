<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder {
    public function run(): void {
        User::create([
            'name'=>'Super Admin',
            'email'=>'admin@smart.com',
            'phone'=>'0000000000',
            'password'=>Hash::make('password123'),
            'role'=>'admin'
        ]);
    }
}

