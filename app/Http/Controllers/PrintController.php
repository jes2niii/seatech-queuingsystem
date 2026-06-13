<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;

class PrintController extends Controller
{
    public function printTicket($ticketNo)
    {
        // Get ticket info
        $ticket = Ticket::where('ticket_no', $ticketNo)->first();
        $purpose = $ticket->purpose ?? '';

        // Build ticket text
        $text = "
========================
       QUEUE TICKET
========================

Purpose: $purpose

        $ticketNo

Please wait for your turn

========================
";

        // Save temporary file
        $file = storage_path("app/ticket_$ticketNo.txt");
        file_put_contents($file, $text);

        // Silent print using Notepad (works on normal printers)
        exec("notepad /p \"$file\"");

        return response()->json(['status' => 'printed']);
    }
}
