<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{ asset('css/registrationDashboard.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <title>Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body>

<div class="mainBody">
<div class="container-fluid vh-100 p-2">
    <div class="row h-100 g-2">
        <!-- SIDEBAR -->
        <div class="col-lg-2 col-md-3 sidebar text-center p-3" style="background-color: rgba(255, 255, 255, 0.2); color: #ffffff;">
            <div class="profile mb-3">
                <i class="bi bi-person-circle fs-1"></i>
                <h6 class="mt-2">{{ Auth::user()->name ?? 'Staff' }}</h6>
                <small>USER</small>
            </div>

           <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-warning w-100 mt-auto" style="color: #ffffff; font-weight:bold;">
                    LOG OUT
                </button>
            </form>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-lg-10 col-md-9 main-panel p-3">

            <!-- HEADER -->
            <div class="row text-center align-items-center header-box mb-3">
                <div class="col-12 col-md-4 time-box">
                    <p id="clock">--:--:--</>
                </div>
                <div class="col-12 col-md-4 now-serving">
                    <p>NOW SERVING:</p>
                </div>
                <div class="col-12 col-md-4 ticket-number">
                    <p>{{ $nowServing?->ticket_no ?? 'NONE' }}</p>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-responsive ticket-table mb-3">
                <table class="table table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Ticket No - Purpose</th>
                            <th>Status</th>
                            <th>Served by:</th>
                        </tr>
                    </thead>
                   <tbody id="ticketBody">
                    @foreach ($tickets as $ticket)
                    <tr class="ticket-row"
                        data-ticket-id="{{ $ticket->id }}"
                        style="cursor:pointer">
                        <td>{{ $ticket->id }}</td>
                        <td><b>{{ $ticket->ticket_no }}</b> - {{ $ticket->purpose }}</td>
                        <td>{{ $ticket->status }}</td>
                        <td><b>{{ $ticket->served_by ?? 'NONE' }}</b></td>
                    </tr>
                    @endforeach
                    </tbody>


                </table>
            </div>

            <form id="ticketActionForm" method="POST">
                @csrf
                <input type="hidden" name="ticket_id" id="selectedTicket">
                <input type="hidden" name="action" id="ticketAction">
            </form>


            <!-- ACTION BUTTONS -->
            <div class="row text-center g-2 action-buttons">
                <div class="col-6 col-md-3">
                    <button class="btn btn-outline-primary w-100"
                            onclick="submitAction('call')">
                        <i class="bi bi-telephone-fill"></i><br>CALL
                    </button>
                </div>

                <div class="col-6 col-md-3">
                    <button class="btn btn-outline-warning w-100"
                            onclick="submitAction('payment')">
                        <i class="bi bi-cash-coin"></i><br>PAYMENT
                    </button>
                </div>

                <div class="col-6 col-md-3">
                    <button class="btn btn-outline-success w-100"
                            onclick="submitAction('done')">
                        <i class="bi bi-check-circle-fill"></i><br>DONE
                    </button>
                </div>

                <div class="col-6 col-md-3">
                    <button class="btn btn-outline-danger w-100"
                            onclick="submitAction('cancel')">
                        <i class="bi bi-x-circle-fill"></i><br>CANCEL
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
    <script src="{{ asset('js/registrationDashboard.js') }}"></script>
    
</body>
</html>
