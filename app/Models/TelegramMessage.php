<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'content',
        'from_admin',
        'is_read',
        'file_url',
        'file_type',
        'media_group_id',
    ];

    protected $casts = [
        'from_admin' => 'boolean',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
    }
}