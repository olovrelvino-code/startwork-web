<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'users';

    // Akun dari form registrasi umum menjadi jobseeker.
    protected $attributes = [
        'role' => 'jobseeker',
    ];

    protected $fillable = [
        'name', 'full_name', 'email', 'password', 'password_hash',
        'phone', 'position', 'location', 'role',
    ];

    protected $hidden = [
        'password', 'password_hash', 'remember_token',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // Form Laravel saat ini mengirim "name"; simpan ke "full_name".
    public function getNameAttribute(): ?string
    {
        return $this->attributes['full_name'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    // Kode Laravel saat ini mengisi "password"; simpan ke "password_hash".
    public function getPasswordAttribute(): ?string
    {
        return $this->attributes['password_hash'] ?? null;
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->setAttribute('password_hash', $value);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }
}