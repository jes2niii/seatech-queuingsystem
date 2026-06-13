<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;


Route::get('/', [TicketController::class, 'dashboard'])
    ->middleware('auth')
    ->name('registrationDashboard');
Route::get('/tickets/page', [TicketController::class, 'registrationDashboard']);
 
Route::get('/userPurpose', function () {
    return view('userPurpose');
});

Route::get('/registrationDashboard', function () {
    return view('registrationDashboard');
})->name('registrationDashboard');


Route::get('/accountLogin', function () {
    return view('accountLogin'); 
})->name('accountLogin');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::post('/ticket/generate', [TicketController::class, 'generate']);
Route::get('/counter', [TicketController::class, 'counterQueue']);
Route::post('/ticket/preview', [TicketController::class, 'preview']);

Route::post('/tickets/action', [TicketController::class, 'action'])
    ->name('tickets.action');

Route::get('/mainView', [TicketController::class, 'nowServing']);
Route::get('/tv/serving-status', [TicketController::class, 'nowServingStatus']);

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/accountLogin'); 
})->name('logout');

Route::get('/registrationDashboard', [TicketController::class, 'registrationDashboard'])
    ->middleware('auth')
    ->name('registrationDashboard');

Route::get('/adminDashboard', [TicketController::class, 'adminDashboard'])
    ->middleware('auth')
    ->name('adminDashboard');


Route::post('/admin/create-account', [AdminController::class, 'storeUser']);
Route::post('/admin/videos/store', [AdminController::class, 'storeVideo']);

Route::patch('/admin/videos/toggle/{id}', [TicketController::class, 'toggle'])->name('videos.toggle');

Route::get('/admin/clear-queue', [TicketController::class, 'clearQueue'])->name('admin.clear.queue');
Route::delete('/admin/videos/{video}', [TicketController::class, 'destroy']);


Route::get('/tickets/live', function () {
    $tickets = \App\Models\Ticket::whereIn('status', ['Waiting', 'Serving', 'For Payment'])
            ->orderBy('created_at', 'asc')
            ->get();
    return view('profile.partials.ticket_rows', compact('tickets'));
});

Route::get('/ticket/print-response', [App\Http\Controllers\TicketController::class, 'printResponse']);