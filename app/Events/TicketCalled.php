<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCalled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $ticket_no;
    public string $staff_name;

    public function __construct(Ticket $ticket)
    {
        $this->ticket_no = $ticket->ticket_no;
        $this->staff_name = $ticket->served_by ?? 'Unknown';
    }

    public function broadcastOn()
    {
        return new Channel('queue');
    }

    public function broadcastWith()
    {
        return [
            'ticket_no' => $this->ticket_no,
            'staff_name' => $this->staff_name,
        ];
    }
}
