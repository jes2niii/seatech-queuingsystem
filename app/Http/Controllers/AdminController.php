<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Video;

class AdminController extends Controller
{
    public function storeUser(Request $request)
    {
       $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'usertype' => 'required|in:regular,admin',
            'email_verified_at' => 'nullable|date',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'usertype' => $request->usertype,
            'email_verified_at' => $request->email_verified_at,
        ]);

        return redirect()->back()->with('success', 'User created successfully!');
    }

     public function storeVideo(Request $request)
{
    $request->validate([
        'video_url' => 'required'
    ]);

    Video::create([
        'video_url' => $request->video_url
    ]);

    return back()->with('success', 'Video added successfully!');
}

public function viewVideo()
{
    $videos = Video::latest()->get();
    return view('adminDashboard', compact('videos'));
}
}
