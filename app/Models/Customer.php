<?php

namespace App\Models;

use App\Support\Notifier;
use App\Traits\Auditable;
use App\Notifications\CustomerResetPassword;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Authenticatable implements MustVerifyEmailContract
{
    use SoftDeletes, Auditable, Notifiable, MustVerifyEmail;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed', 'email_verified_at' => 'datetime'];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPassword($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmail);
    }

    protected static function booted(): void
    {
        // Self-registrations (no logged-in staff member) notify the team.
        static::created(function (Customer $c) {
            if (auth()->guest()) {
                Notifier::toPermission('customers.view', 'New customer registration', $c->name, route('admin.customers.show', $c->id), 'bi-person-plus');
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
}
