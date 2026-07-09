<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print</title>
    <style>
        @page { margin: 0; size: 58mm auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: monospace;
            width: 58mm;
            padding: 4mm;
            text-align: center;
            font-size: 11px;
        }
        .purpose { font-size: 10px; margin-bottom: 2mm; }
        .ticket {
            font-size: 28px;
            font-weight: bold;
            margin: 3mm 0;
        }
        .footer { font-size: 9px; margin-top: 3mm; color: #555; }
        hr { border: none; border-top: 1px dashed #000; margin: 2mm 0; }
    </style>
</head>
<body>
    <p class="purpose">Purpose: {{ request('purpose') }}</p>
    <hr>
    <div class="ticket">{{ request('ticket') }}</div>
    <hr>
    <p class="footer">Thank you for visiting!<br>Seatech Maritime</p>
</body>
</html>
