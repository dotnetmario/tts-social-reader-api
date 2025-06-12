<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class TextToSpeech extends Model
{
    use SoftDeletes;

    protected $table = "text_to_speeches";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'text',
        'path_to_file',
        'status',
        'characters_used',
        'credit_usages',
        'voice_name',
        'language_code',
        'voice_gender',
        'error_message'
    ];

    /**
     * RELATIONS
     * 
     */

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
