<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{ asset('css/userPurpose.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Purpose</title>
</head>
<body>
    <div class="container-fluid">
        <div class="mainBody" style="height: 100vh;">
             <div class="companyName">
                <img name="logoTop" src="/img/seatechLogo.png" alt="Logo" width="120" height="120" >
                <p>SEATECH MARITIME TRAINING AND ASSESSMENT CENTER INC,. LEGAZPI <br>
                    (Choose Your Purpose To Assist You!)
                </p>
            </div>
            <div class="bodyButton">
                <div class="grid-container">
                    <button class="queue-button" onclick="showPopup('REGISTRATION (ENROLLMENT)')" >
                        REGISTRATION<br>(ENROLLMENT)
                    </button>
                    <button class="queue-button" onclick="showPopup('CERTIFICATE (RELEASING)')">
                        CERTIFICATE<br>(RELEASING)
                    </button>
                    <button class="queue-button" onclick="showPopup('REGISTRATION (INQUIRY)')">
                        REGISTRATION<br>(INQUIRY)
                    </button>
                    <button class="queue-button" onclick="showPopup('CASHIER (PAYMENT)')">
                        CASHIER<br>(PAYMENT)
                    </button>
                </div>
            </div>

            <!-- MODAL -->
            <div id="popupModal" class="popup-modal">
            <div class="popup-content">
                <h2 id="popupTitle"></h2>

                <p style="font-size: 30px">Ticket Number:</p>
                <div id="ticketNumber" style="font-size: 60px;">----</div>

                <div class="modal-buttons">
                    <button class="btn-cancel" onclick="closePopup()">← Cancel</button>
                    <button class="btn-continue" onclick="confirmTicket(); setTimeout(() => location.reload(), 2000);">OKAY →</button>
                </div>
            </div>
        </div>

        </div>

        {{-- <div id="printArea" style="display:none; margin-left: -5px;">
    <div style="text-align:center; font-family: monospace;">
        <p id="printPurpose" style="font-size: 12px; margin-top: 5px; margin-bottom: 5px;"></p>
        <h1 id="printTicketNo" style="font-size: 40px; margin-top: 5px; margin-bottom: 5px;"></h1>
    </div>
</div> --}}


</div>

</div>

<script src="{{ asset('js/userPurpose.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>