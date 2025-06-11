<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, BaseModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * RELATIONS
     */

    public function configuration(): HasOne
    {
        return $this->hasOne(UserConfiguration::class);
    }

    // public function subscriptions(): BelongsToMany
    // {
    //     return $this->belongsToMany(Subscription::class);
    // }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function textToSpeeches():HasMany
    {
        return $this->hasMany(TextToSpeech::class);
    }

    /**
     * HELPERS
     */
    public function getTtsFileName(): string
    {
        return $this->id . '/' . now()->toDateString() . '/audio_' . now()->timestamp . '_' . Str::uuid() . '.mp3';
    }
    /**
     * Calculate user available credit
     */
    public function getAvailableCredits(): int
    {
        return 0;
    }
}
