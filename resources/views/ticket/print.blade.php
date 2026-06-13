<!-- resources/views/ticket/print.blade.php -->
<div style="width:80mm; padding:10px; font-family: 'Courier New', monospace; font-size:14px; text-align:center;">
    <p style="font-size:16px; font-weight:bold;">{{ $purpose }}</p>
    <h1 style="font-size:36px; margin:10px 0;">{{ $ticketNo }}</h1>
    <p style="font-size:12px; margin:10px 0;">Please wait for your turn</p>
    <hr style="border:1px dashed #000; margin:10px 0;">
    <p style="font-size:10px; margin:0;">{{ now()->format('Y-m-d H:i') }}</p>
</div>
