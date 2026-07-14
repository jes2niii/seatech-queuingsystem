<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\PrintService;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function printTicket(Request $request)
    {
        $validated = $request->validate([
            'ticket_no' => 'required|string',
            'purpose'   => 'required|string',
        ]);

        try {
            $service = new PrintService();
            $service->printTicket($validated['ticket_no'], $validated['purpose']);

            return response()->json(['status' => 'ok', 'message' => 'Ticket printed.']);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
