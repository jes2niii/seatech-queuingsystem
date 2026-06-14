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
            'usertype' => 'required|in:Regular,admin',
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
            'video_url' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg|max:204800',
        ]);

        $file = $request->file('video_url');
        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(public_path('vid'), $filename);

        Video::create([
            'video_url' => $filename
        ]);

        return back()->with('success', 'Video uploaded successfully!');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'You cannot delete yourself.']);
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    public function viewVideo()
    {
        $videos = Video::latest()->get();
        return view('adminDashboard', compact('videos'));
    }
}
