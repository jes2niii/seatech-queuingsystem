@foreach ($tickets as $ticket)
<tr class="ticket-row" data-ticket-id="{{ $ticket->id }}">
    <td>{{ $loop->iteration }}</td>
    <td><b>{{ $ticket->ticket_no }}</b> - {{ $ticket->purpose }}</td>
    <td>{{ $ticket->status }}</td>
    <td><b>{{ $ticket->served_by ?? 'NONE' }}</b></td>
</tr>
@endforeach
