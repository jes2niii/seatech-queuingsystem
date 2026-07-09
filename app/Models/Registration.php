<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'enrollee_type',
        'referral_type',
        'referral_source',
        'enrollment_date',
        'first_name',
        'middle_name',
        'last_name',
        'srn',
        'application_no',
        'address',
        'gender',
        'birthdate',
        'civil_status',
        'place_of_birth',
        'email',
        'contact_no',
        'rank',
        'contact_person',
        'relationship',
        'contact_mobile',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'enrollment_date' => 'date',
    ];

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}
