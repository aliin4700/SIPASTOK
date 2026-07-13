<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pesan individual dalam chat customer-admin.
 */
class Message extends Model
{
    use HasFactory;

    // sender_type membedakan pesan customer dan admin untuk tampilan chat.
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function conversation()
    {
        // Pesan selalu berada di satu conversation.
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        // Sender mengarah ke users, baik customer maupun admin.
        return $this->belongsTo(User::class, 'sender_id');
    }
}
