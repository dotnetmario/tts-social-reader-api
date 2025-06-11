<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserConfiguration extends Model
{
    use SoftDeletes;

    protected $table = "user_configurations";

        /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voice_gender',
        'language_code',
    ];


    /**
     * RELATIONS
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
