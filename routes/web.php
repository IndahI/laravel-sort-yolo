<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YOLOController;
use App\Http\Controllers\HistoryController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('uploads');
});

Route::get('/history', [HistoryController::class, 'history'])->name('history.history');
Route::get('/output/{id}', [HistoryController::class, 'show'])->name('history.show');

Route::get('/progress', [YOLOController::class, 'getProgress']);
Route::post('/predict-ajax', [YOLOController::class, 'predictAjax']);
Route::get('/result-page', [YOLOController::class, 'getResultView']);