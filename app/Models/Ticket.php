<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'purpose',
        'prefix',
        'number',
        'ticket_no',
        'status',
        'served_by',
        'called_at',
    ];
    
    public function registrationDashboard()
    {
        $tickets = Ticket::orderBy('created_at', 'desc')->get();

        return view('registrationDashboard', compact('tickets'));
    }
}




