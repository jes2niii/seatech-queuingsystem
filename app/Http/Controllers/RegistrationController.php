<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollee_type'    => 'nullable|string|max:50',
            'referral_type'    => 'nullable|string|max:50',
            'referral_source'  => 'nullable|string|max:255',
            'enrollment_date'  => 'nullable|date',
            'first_name'       => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'last_name'        => 'required|string|max:255',
            'srn'              => 'nullable|string|max:50',
            'application_no'   => 'nullable|string|max:50',
            'address'          => 'nullable|string',
            'gender'           => 'nullable|string|max:50',
            'birthdate'        => 'nullable|date',
            'civil_status'     => 'nullable|string|max:50',
            'place_of_birth'   => 'nullable|string|max:255',
            'email'            => 'required|email|max:255',
            'contact_no'       => ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'rank'             => 'nullable|string|max:100',
            'contact_person'   => 'nullable|string|max:255',
            'contact_mobile'   => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
        ]);

        // Normalize empty strings to NULL for nullable fields
        foreach ($validated as $key => $value) {
            if ($value === '' || $value === null) {
                // Only normalize fields that are not required (we already validated required ones)
                $validated[$key] = null;
            }
        }

        $registration = Registration::create($validated);

        return response()->json([
            'message' => 'Registration submitted successfully!',
            'id' => $registration->id,
        ]);
    }

    public function show(Registration $registration)
    {
        return response()->json($registration);
    }
}
