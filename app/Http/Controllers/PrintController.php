<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
    public function printTicket($ticketNo)
    {
        $ticket = Ticket::where('ticket_no', $ticketNo)->first();

        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $purpose = $ticket->purpose ?? '';

        $text = "
========================
       QUEUE TICKET
========================

Purpose: $purpose

        $ticketNo

Please wait for your turn

========================
";

        $file = storage_path("app/ticket_$ticketNo.txt");
        file_put_contents($file, $text);

        $output = null;
        $resultCode = null;
        exec("notepad /p \"$file\"", $output, $resultCode);

        if ($resultCode !== 0) {
            Log::warning("Print exec failed for ticket $ticketNo, exit code: $resultCode");
        }

        return response()->json(['status' => 'printed']);
    }
}
