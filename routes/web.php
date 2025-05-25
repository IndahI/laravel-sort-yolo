<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YOLOController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('uploads');
});

Route::get('/history', [YOLOController::class, 'history'])->name('yolo.history');
Route::get('/hasil/{id}', [YOLOController::class, 'show'])->name('yolo.show');

Route::post('/predict-combined', [YOLOController::class, 'predictCombined'])->name('predict-combined');

