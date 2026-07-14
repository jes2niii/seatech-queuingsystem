@forelse ($tickets as $ticket)
<tr class="ticket-row" data-ticket-id="{{ $ticket->id }}">
    <td>{{ $loop->iteration }}</td>
    <td class="ticket-cell">
        {{ $ticket->ticket_no }}
        <span style="color: var(--color-text-muted); font-weight: 500;">— {{ $ticket->purpose }}</span>
    </td>
    <td>
        <span class="status-badge status-{{ strtolower(str_replace(' ', '', $ticket->status)) }}">
            {{ $ticket->status }}
        </span>
    </td>
    <td>
        @if($ticket->served_by)
            <span style="font-weight: 600;">{{ $ticket->served_by }}</span>
        @else
            <span style="color: var(--color-text-muted);">—</span>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="4" class="empty-state">
        <i class="bi bi-inbox"></i>
        <div>
            @php $ut = strtolower((string) \Illuminate\Support\Facades\Auth::user()->usertype); @endphp
            @if($ut === 'cashier')
                No tickets waiting for cashier.
            @elseif($ut === 'releasing')
                No certificates waiting for release.
            @else
                No active tickets in the queue.
            @endif
        </div>
    </td>
</tr>
@endforelse
