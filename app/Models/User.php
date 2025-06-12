<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use App\Traits\BaseModelTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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

    public function textToSpeeches(): HasMany
    {
        return $this->hasMany(TextToSpeech::class);
    }

    /**
     * HELPERS
     */
    /**
     * Generate a unique filename for storing the generated TTS audio.
     *
     * @return string
     */
    public function getTtsFileName(): string
    {
        return $this->id . '/' . now()->toDateString() . '/audio_' . now()->timestamp . '_' . Str::uuid() . '.mp3';
    }
    /**
     * Retrieve all non-expired credit records with remaining characters.
     *
     * @return Collection
     */
    public function getAvailableCredits(): Collection
    {
        return $this->credits()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereRaw('characters_used < characters')
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Get the total number of characters available across all valid credits.
     *
     * @return int
     */
    public function getAvailableCharacterCount(): int
    {
        return $this->getAvailableCredits()->sum(function ($credit) {
            return $credit->characters - $credit->characters_used;
        });
    }

    /**
     * Attempt to deduct a specified number of characters from available credits.
     *
     * Deduction prioritizes credits that expire sooner.
     * Returns an array of usage logs if successful, or false if not enough credits are available.
     *
     * @param int $count
     * @return array|false
     */
    public function deductCharacters(int $count): array|false
    {
        return DB::transaction(function () use ($count) {
            $remaining = $count;
            $usageLog = [];

            foreach ($this->getAvailableCredits() as $credit) {
                if ($remaining <= 0)
                    break;

                $available = $credit->characters - $credit->characters_used;
                if ($available <= 0)
                    continue;

                $toDeduct = min($available, $remaining);
                $credit->characters_used += $toDeduct;
                $credit->save();

                $usageLog[] = [
                    'credit_id' => $credit->id,
                    'used' => $toDeduct,
                ];

                $remaining -= $toDeduct;
            }

            // Fail if not enough credits could be deducted
            if ($remaining > 0)
                return false;

            return $usageLog;
        });
    }

    /**
     * Refund previously used characters based on usage log.
     *
     * Each log entry must contain a valid credit_id and amount used.
     * Skips invalid or excessive refunds safely.
     *
     * @param array $creditUsages
     * @return void
     */
    public function recreditFromUsage(array $creditUsages): void
    {
        DB::transaction(function () use ($creditUsages) {
            foreach ($creditUsages as $usage) {
                $credit = Credit::find($usage['credit_id']);

                // Only recredit if the usage is valid
                if ($credit && $credit->characters_used >= $usage['used']) {
                    $credit->characters_used -= $usage['used'];
                    $credit->save();
                }
            }
        });
    }
}
