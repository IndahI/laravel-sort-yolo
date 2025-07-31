<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YOLO11Controller;
use App\Http\Controllers\YOLO7Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DetectionController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('uploads');
});

Route::get('/histori', [PageController::class, 'histori'])->name('histori');
Route::get('/hasil/{id}', [PageController::class, 'show'])->name('hasil');
Route::get('/progress', [PageController::class, 'getProgress']);

Route::post('/predict-yolo11', [YOLO11Controller::class, 'predictYolo11']);
Route::post('/predict-yolo7', [YOLO7Controller::class, 'predictYolo7']);
Route::get('/get-result', [DetectionController::class, 'getResultView']);