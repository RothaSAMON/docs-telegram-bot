<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $fillable = [
        'chat_id',
        'username',
        'first_name',
        'last_name',
    ];

    public function messages()
    {
        return $this->hasMany(TelegramMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(TelegramMessage::class)->latest();
    }
}
