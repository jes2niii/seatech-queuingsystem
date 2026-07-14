<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller; 
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use App\Models\Video;
use App\Events\TicketCalled;
use Illuminate\Support\Facades\File;
use App\Models\Registration;

class TicketController extends Controller
{
    public function registrationDashboard()
    {
        $user = Auth::user();

        $tickets = $this->getQueueTicketsForUser($user);

        $nowServing = $this->getNowServingForUser($user);

        $registrations = Registration::with('ticket')->latest()->get();

        return view('registrationDashboard', compact('tickets', 'nowServing', 'registrations'));
    }
    
    public function generate(Request $request)
    {
             try {
            $purpose = $request->purpose;
            $registrationId = $request->registration_id;

            // Assign prefix
            $prefix = match (true) {
                str_contains($purpose, 'ENROLLMENT') => 'E',
                str_contains($purpose, 'CERTIFICATE') => 'R',
                str_contains($purpose, 'INQUIRY') => 'I',
                str_contains($purpose, 'CASHIER') => 'C',
                default => 'X'
            };

            $ticket = DB::transaction(function () use ($prefix, $purpose, $registrationId) {

                    $lastTicket = Ticket::where('prefix', $prefix)
                    ->orderByDesc('number')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $lastTicket ? $lastTicket->number + 1 : 1;
                return Ticket::create([
                    'purpose'         => $purpose,
                    'prefix'          => $prefix,
                    'number'          => $nextNumber,
                    'ticket_no'       => $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
                    'status'          => 'Waiting',
                    'registration_id' => $registrationId,
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

        // Acquire a row lock for this prefix to avoid concurrent previews
        // colliding with `generate`. The lock is released when this request ends.
        $nextNumber = DB::transaction(function () use ($prefix) {
            $lastTicket = Ticket::where('prefix', $prefix)
                ->orderByDesc('number')
                ->lockForUpdate()
                ->first();

            return $lastTicket ? $lastTicket->number + 1 : 1;
        });

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
        $user = Auth::user();
        $ticket = Ticket::findOrFail($request->ticket_id);

        // Cashier and Releasing users can only call, done, cancel — no payment
        $userType = strtolower((string) ($user->usertype ?? ''));
        if (in_array($userType, ['cashier', 'releasing'], true) && $request->action === 'payment') {
            return back()->with('error', ucfirst($userType) . ' users cannot perform payment actions.');
        }

        switch ($request->action) {
            case 'call':
                $ticket->update([
                    'status' => Ticket::STATUS_SERVING,
                    'served_by' => auth()->user()->name,
                    'called_at' => now(),
                ]);
                broadcast(new TicketCalled($ticket->fresh()))->toOthers();

                if ($request->expectsJson()) {
                    $ticket->load('registration');
                    $nextTicket = Ticket::where('status', Ticket::STATUS_WAITING)
                        ->orderBy('created_at', 'asc')
                        ->first();

                    return response()->json([
                        'ticket' => $ticket,
                        'registration' => $ticket->registration,
                        'next_ticket_id' => $nextTicket?->id,
                    ]);
                }
                break;

            case 'payment':
            $ticket->update([
                'status' => Ticket::STATUS_FOR_PAYMENT,
            ]);
            break;

            case 'done':
                $userType = strtolower((string) ($user->usertype ?? ''));
                $newStatus = in_array($userType, ['cashier', 'releasing'], true)
                    ? Ticket::STATUS_DONE
                    : Ticket::STATUS_FOR_PAYMENT;
                $ticket->update([
                    'status' => $newStatus,
                ]);
                break;

            case 'cancel':
                $ticket->update([
                    'status' => Ticket::STATUS_CANCELLED,
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
        $user = Auth::user();

        if ($user->usertype === 'admin') {
            return redirect()->route('adminDashboard');
        }

        $tickets = $this->getQueueTicketsForUser($user);

        $nowServing = $this->getNowServingForUser($user);

        $registrations = Registration::with('ticket')->latest()->get();

        return view('registrationDashboard', compact('tickets', 'nowServing', 'registrations'));
    }

    /**
     * Get the queue tickets appropriate for the logged-in user.
     *  - Cashier users see:
     *      - C-prefix tickets with status in (Waiting, Serving, For Payment)
     *      - Registration tickets (E/I) that are For Payment or Serving
     *  - Releasing users see:
     *      - R-prefix tickets with status Done (ready for release)
     *  - Other staff see E/I-prefix active tickets (Waiting, Serving)
     */
    private function getQueueTicketsForUser($user)
    {
        $query = Ticket::query()->orderBy('created_at', 'asc');

        if (!$user) {
            return $query->whereIn('status', ['Waiting', 'Serving', 'For Payment'])->get();
        }

        $userType = strtolower((string) $user->usertype);

        if ($userType === 'cashier') {
            $query->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('prefix', 'C')
                       ->whereIn('status', ['Waiting', 'Serving', 'For Payment']);
                })->orWhere(function ($q2) {
                    $q2->whereIn('prefix', ['E', 'I'])
                       ->whereIn('status', ['For Payment', 'Serving']);
                });
            });
        } elseif ($userType === 'releasing') {
            $query->where('prefix', 'R')
                  ->where('status', 'Done');
        } else {
            $query->whereIn('prefix', ['E', 'I'])
                  ->whereIn('status', ['Waiting', 'Serving']);
        }

        return $query->get();
    }

    /**
     * Get the "Now Serving" ticket for the logged-in user.
     *  - Releasing users: most recent Done R ticket.
     *  - Other staff: the ticket they are actively serving (status = Serving, served_by = user).
     */
    private function getNowServingForUser($user)
    {
        if (!$user) {
            return null;
        }

        $userType = strtolower((string) $user->usertype);

        if ($userType === 'releasing') {
            return Ticket::where('prefix', 'R')
                ->where('status', 'Done')
                ->latest('id')
                ->first();
        }

        return Ticket::where('served_by', $user->name)
            ->where('status', 'Serving')
            ->latest('id')
            ->first();
    }

    public function adminDashboard()
    {
        $videos = Video::latest()->get();
        $users = User::latest()->get();
        return view('adminDashboard', compact('videos', 'users'));
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

