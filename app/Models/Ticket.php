<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_WAITING     = 'Waiting';
    public const STATUS_SERVING     = 'Serving';
    public const STATUS_FOR_PAYMENT = 'For Payment';
    public const STATUS_DONE        = 'Done';
    public const STATUS_CANCELLED   = 'Cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_SERVING,
        self::STATUS_FOR_PAYMENT,
    ];

    protected $fillable = [
        'purpose',
        'prefix',
        'number',
        'ticket_no',
        'status',
        'served_by',
        'called_at',
        'registration_id',
    ];

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by', 'name');
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
