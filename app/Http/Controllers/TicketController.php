<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller; 
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Video;
use Illuminate\Support\Facades\File;

class TicketController extends Controller
{
    public function registrationDashboard()
    {
        $user = Auth::user();

        $tickets = Ticket::whereIn('status', ['Waiting', 'Serving', 'For Payment'])
            ->orderBy('created_at', 'asc')
            ->get();

        $nowServing = Ticket::where('served_by', $user->name)
            ->where('status', 'Serving', 'For Payment')
            ->latest()
            ->first();

        return view('registrationDashboard', compact('tickets', 'nowServing'));
    }
    
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (strtolower($user->name) === 'admin') {
                return redirect()->route('adminDashboard');
            }

            return redirect()->route('registrationDashboard');
        }

        return back()->withErrors(['login' => 'Invalid name or password']);
    }

    public function generate(Request $request)
    {
         try {
            $purpose = $request->purpose;

            // Assign prefix
            $prefix = match (true) {
                str_contains($purpose, 'ENROLLMENT') => 'E',
                str_contains($purpose, 'CERTIFICATE') => 'R',
                str_contains($purpose, 'INQUIRY') => 'I',
                str_contains($purpose, 'CASHIER') => 'C',
                default => 'X'
            };

            // CRITICAL PART: prevent duplicates
            $ticket = DB::transaction(function () use ($prefix, $purpose) {

                    $lastTicket = Ticket::where('prefix', $prefix)
                    ->orderByDesc('number')
                    ->first();

                $nextNumber = $lastTicket ? $lastTicket->number + 1 : 1;
                return Ticket::create([
                    'purpose'   => $purpose,
                    'prefix'    => $prefix,
                    'number'    => $nextNumber,
                    'ticket_no' => $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
                    'status'    => 'waiting',
                ]);
            });

            return response()->json($ticket);
        
        } catch (\Throwable $e) {
            // return JSON error instead of HTML
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function counterQueue()
    {
        $tickets = Ticket::where('status', '!=', 'Done')
        ->orderBy('created_at', 'asc')
        ->get();

        return view('counter.queue', compact('tickets'));
    }

    public function preview(Request $request)
    {
        $purpose = $request->purpose;

        $prefix = match (true) {
            str_contains($purpose, 'ENROLLMENT') => 'E',
            str_contains($purpose, 'CERTIFICATE') => 'R',
            str_contains($purpose, 'INQUIRY') => 'I',
            str_contains($purpose, 'CASHIER') => 'C',
            default => 'X'
        };

        $lastTicket = Ticket::where('prefix', $prefix)
            ->orderByDesc('number')
            ->first();

        $nextNumber = $lastTicket ? $lastTicket->number + 1 : 1;

        $ticket_no = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Return the next number without saving
        return response()->json([
            'ticket_no' => $ticket_no,
            'prefix' => $prefix,
            'number' => $nextNumber,
            'purpose' => $purpose,
        ]);
    }

    public function action(Request $request)
    {
        $ticket = Ticket::findOrFail($request->ticket_id);

        switch ($request->action) {
            case 'call':
                $ticket->update([
                    'status' => 'Serving',
                    'served_by' => auth()->user()->name,
                    'called_at' => now(),
                ]);
                break;

            case 'payment':
            $ticket->update([
                'status' => 'For Payment',
            ]);
            break;

            case 'done':
                $ticket->update([
                    'status' => 'Done',
                ]);
                break;

            case 'cancel':
                $ticket->update([
                    'status' => 'Cancelled',
                ]);
                break;
        }

        return back();
    }

    public function nowServing()
    {
        $users = User::with('servingTicket')->get();
       $videos = Video::where('is_active', 1)->pluck('video_url');
        return view('mainView', compact('users','videos'));
    }

    public function toggle(Request $request, $id)
    {
        $video = Video::findOrFail($id);
        $video->is_active = $request->has('is_active') ? 1 : 0;
        $video->save();

        return back();
    }

    // This returns JSON for AJAX updates
    public function nowServingStatus()
    {
        $users = User::with('servingTicket')->get();

        return response()->json(
            $users->mapWithKeys(function ($u) {
                return [
                    $u->id => $u->servingTicket ? $u->servingTicket->ticket_no : 'NONE'
                ];
            })
        );
    }

    public function dashboard()
    {
        $tickets = Ticket::whereIn('status', ['waiting', 'serving', 'For Payment'])
            ->orderBy('created_at')
            ->get();

        $nowServing = Ticket::where('status', 'serving', 'For Payment')
            ->where('served_by', Auth::user()->name)
            ->orderBy('called_at', 'desc')
            ->first();

            $user = Auth::user();

        if ($user->name === 'admin') {
            return redirect()->route('adminDashboard');
        }

        return view('registrationDashboard', compact('tickets','nowServing'));

    }

    public function adminDashboard()
    {
        $videos = Video::latest()->get();
        return view('adminDashboard', compact('videos'));
    }

    public function clearQueue()
    {
        Ticket::truncate(); // deletes all queue numbers

        return redirect()->back()->with('success', 'All queue numbers cleared.');
    }
    
    public function printResponse(Request $request)
    {
        // Make sure to get values from query parameters
        $ticket_no = $request->query('ticket', '000');
        $purpose = $request->query('purpose', 'General');

        // Initialize array for print entries
        $a = [];

        // 1️⃣ Purpose text
        $obj1 = new \stdClass();
        $obj1->type = 0;         // text
        $obj1->content = "Purpose: $purpose";
        $obj1->bold = 1;
        $obj1->align = 1;        // center
        $a[] = $obj1;

        // 2️⃣ Ticket number text
        $obj2 = new \stdClass();
        $obj2->type = 0;
        $obj2->content = "Ticket No: $ticket_no";
        $obj2->bold = 1;
        $obj2->align = 1;
        $obj2->format = 2; // double height + width
        $a[] = $obj2;

        // 3️⃣ Thank you text
        $obj3 = new \stdClass();
        $obj3->type = 0;         // text
        $obj3->content = "Thank you for visiting!";
        $obj3->bold = 0;
        $obj3->align = 1;
        $a[] = $obj3;

         // --------- Bottom Margin: only ONE blank line ---------
        $bottom = new \stdClass();
        $bottom->type = 0;
        $bottom->content = ' '; // single space
        $bottom->bold = 0;
        $bottom->align = 0;
        $bottom->format = 0;
        $a[] = $bottom;

        // Return clean JSON with correct content type
        return response()->json($a, 200, [], JSON_FORCE_OBJECT);
    }

    public function destroy(Video $video)
    {
        $path = public_path('vid/' . $video->video_url);

        if (File::exists($path)) {
            File::delete($path);
        }

        $video->delete();

        return back()->with('success', 'Video deleted successfully.');
    }
}

