<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ asset('css/registrationDashboard.css') }}?v={{ time() }}">
    <title>Staff Dashboard | SEATECH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

@if (session('success'))
    <div class="global-flash global-flash-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="global-flash global-flash-error">{{ session('error') }}</div>
@endif

<div class="dashboard-body">
<div class="dashboard-shell">

    <!-- SIDEBAR -->
    <aside class="dash-sidebar">
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h6 class="sidebar-name">{{ Auth::user()->name ?? 'Staff' }}</h6>
            <div class="sidebar-role">Staff</div>
        </div>

        @php
            $userType = strtolower((string) Auth::user()->usertype);
            $isCashier = $userType === 'cashier';
            $isCertificate = $userType === 'releasing';
            $isRestricted = $isCashier || $isCertificate;
        @endphp

        <nav class="sidebar-nav">
            <button class="sidebar-link active" data-tab="queue" onclick="switchTab('queue')">
                <i class="bi bi-list-ol"></i>
                @if($isCashier) Cashier Queue
                @elseif($isCertificate) Ready for Release
                @else Queue
                @endif
            </button>
            @if(!$isRestricted)
            <button class="sidebar-link" data-tab="registrations" onclick="switchTab('registrations')">
                <i class="bi bi-people-fill"></i> Registrations
            </button>
            @endif
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </button>
        </form>
    </aside>

    <!-- MAIN -->
    <main class="dash-main">

        <!-- HEADER -->
        <div class="dash-header">
            <div class="dash-header-cell dash-time">
                <div id="clock">--:--:--</div>
                <div class="dash-time-label">Current Time</div>
            </div>

            <div class="dash-header-cell dash-now-serving">
                <p class="dash-now-serving-label">Now Serving</p>
                <p class="ticket-number-display">{{ $nowServing?->ticket_no ?? '—' }}</p>
            </div>

            <div class="dash-header-cell dash-stats">
                @php
                    $waitingCount  = $tickets->where('status', 'Waiting')->count();
                    $servingCount  = $tickets->whereIn('status', ['Serving', 'For Payment'])->count();
                    $pendingCashier = $isCashier ? $tickets->whereIn('prefix', ['E', 'I'])->where('status', 'For Payment')->count() : 0;
                @endphp
                <div class="dash-stats-row">
                    <div class="dash-stat">
                        <div class="dash-stat-value">{{ $waitingCount }}</div>
                        <div class="dash-stat-label">Waiting</div>
                    </div>
                    <div class="dash-stat">
                        <div class="dash-stat-value">{{ $servingCount }}</div>
                        <div class="dash-stat-label">Active</div>
                    </div>
                    <div class="dash-stat">
                        <div class="dash-stat-value">{{ $tickets->count() }}</div>
                        <div class="dash-stat-label">{{ $isCashier ? 'Pending' : 'Total' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="dash-content">

            <!-- QUEUE TAB -->
            <div id="tabContentQueue">

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title"><i class="bi bi-list-task"></i>
                            @if($isCashier) Cashier Queue
                            @elseif($isCertificate) Ready for Release
                            @else Registration Queue
                            @endif
                        </h2>
                        <span style="font-size:12px; color:var(--color-text-muted);">Click a row to select</span>
                    </div>
                    <div class="dash-card-body">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Ticket &amp; Purpose</th>
                                    <th style="width: 160px;">Status</th>
                                    <th style="width: 200px;">Served By</th>
                                </tr>
                            </thead>
                            <tbody id="ticketBody">
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
                                        <div>No active tickets in the queue.</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <form id="ticketActionForm" method="POST" style="display:none;">
                    @csrf
                    <input type="hidden" name="ticket_id" id="selectedTicket">
                    <input type="hidden" name="action" id="ticketAction">
                </form>

                <!-- ACTION TOOLBAR -->
                @php
                    $toolbarClass = $isCertificate ? 'action-toolbar-2' : 'action-toolbar-3';
                @endphp
                <div class="action-toolbar {{ $toolbarClass }}">
                    <button class="action-btn action-btn-call" onclick="submitAction('call')">
                        <i class="bi bi-telephone-fill"></i> Call
                    </button>

                    @if($isCertificate)
                    <button class="action-btn action-btn-done" onclick="submitAction('done')">
                        <i class="bi bi-check-circle-fill"></i> Done
                    </button>
                    @elseif($isCashier)
                    <button class="action-btn action-btn-done" onclick="submitAction('done')">
                        <i class="bi bi-check-circle-fill"></i> Done
                    </button>
                    <button class="action-btn action-btn-cancel" onclick="submitAction('cancel')">
                        <i class="bi bi-x-circle-fill"></i> Cancel
                    </button>
                    @else
                    <button class="action-btn action-btn-payment" onclick="submitAction('done')">
                        <i class="bi bi-cash-coin"></i> For Payment
                    </button>
                    <button class="action-btn action-btn-cancel" onclick="submitAction('cancel')">
                        <i class="bi bi-x-circle-fill"></i> Cancel
                    </button>
                    @endif
                </div>
            </div>

            <!-- REGISTRATIONS TAB -->
            <div id="tabContentRegistrations" style="display:none;">

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h2 class="dash-card-title"><i class="bi bi-people-fill"></i> Registered Students</h2>
                        <span style="font-size:12px; color:var(--color-text-muted);">Total: {{ $registrations->count() }}</span>
                    </div>
                    <div class="dash-card-body">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Name</th>
                                    <th>Ticket</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Rank</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($registrations as $reg)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $reg->last_name }}, {{ $reg->first_name }} {{ $reg->middle_name }}</div>
                                    </td>
                                    <td class="ticket-cell">
                                        {{ $reg->ticket?->ticket_no ?? '—' }}
                                    </td>
                                    <td style="color: var(--color-text-muted);">{{ $reg->email }}</td>
                                    <td>{{ $reg->contact_no }}</td>
                                    <td>{{ $reg->rank ?? '—' }}</td>
                                    <td>{{ $reg->course ?? '—' }}</td>
                                    <td style="color: var(--color-text-muted); font-size: 13px;">{{ $reg->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <button class="view-btn" onclick="viewRegistration({{ $reg->id }})">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <a class="print-excel-btn" href="{{ route('registration.print.excel', $reg) }}" title="Print Excel">
                                                <i class="bi bi-file-earmark-excel"></i> Print Excel
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <div>No registrations yet.</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
</div>

<!-- CALL MODAL -->
<div class="modal fade" id="callModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="callModalTitle"><i class="bi bi-megaphone-fill"></i> Now Serving</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="call-modal-ticket">
                    <p class="call-modal-ticket-label">Ticket Number</p>
                    <p class="call-modal-ticket-number" id="callTicketNumber"></p>
                </div>
                <p class="call-modal-purpose" id="callTicketPurpose"></p>
                <div id="callRegistrationInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" id="btnCallClose" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Close
                </button>
                <div>
                    <button type="button" class="modal-btn modal-btn-info" id="btnCallPrint" style="display:none;">
                        <i class="bi bi-printer"></i> Print Excel
                    </button>
                    @if($isRestricted)
                    <button type="button" class="modal-btn modal-btn-success" id="btnCallDone">
                        <i class="bi bi-check-lg"></i> Mark as Done
                    </button>
                    @else
                    <button type="button" class="modal-btn modal-btn-success" id="btnCallDone">
                        <i class="bi bi-cash-coin"></i> Submit for Payment
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VIEW REGISTRATION MODAL -->
<div class="modal fade" id="viewRegModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-vcard-fill"></i> Registration Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewRegBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/registrationDashboard.js') }}"></script>

<audio id="callSound" src="{{ asset('sounds/call.mp3') }}" preload="auto"></audio>

</body>
</html>
