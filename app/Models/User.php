<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','phone','role','avatar','region',
        'latitude','longitude','wallet_balance','vehicle_type','license_number','fcm_token'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
    ];

    // helpers
    public function isDriver(){ return $this->role === 'driver'; }
    public function isCustomer(){ return $this->role === 'customer'; }
    public function isStation(){ return $this->role === 'station'; }
    public function isAdmin(){ return $this->role === 'admin'; }

    // relationships
    public function vehicles(){ return $this->hasMany(Vehicle::class,'driver_id'); }
    public function ordersAsCustomer(){ return $this->hasMany(Order::class,'customer_id'); }
    public function ordersAsDriver(){ return $this->hasMany(Order::class,'driver_id'); }
    public function payments(){ return $this->hasMany(Payment::class,'user_id'); }
    public function reviews(){ return $this->hasMany(DriverReview::class,'driver_id'); }
    public function walletTransactions(){ return $this->hasMany(WalletTransaction::class,'user_id'); }
}
