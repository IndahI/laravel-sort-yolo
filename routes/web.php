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

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\File;


// Route::get('/', function () {
//      return view('uploads2');
// });

// Route::post('/upload-test', function (Request $request) {
//     try {
//         $uploadPath = public_path('media/input');

//         if (!File::exists($uploadPath)) {
//             File::makeDirectory($uploadPath, 0755, true);
//         }

//         $savedFiles = [];

//         if ($request->hasFile('file')) {
//             $file = $request->file('file');
//             $file->move($uploadPath, $file->getClientOriginalName());
//             $savedFiles[] = $file->getClientOriginalName();
//         }

//         if ($request->hasFile('srt_file')) {
//             $srt = $request->file('srt_file');
//             $srt->move($uploadPath, $srt->getClientOriginalName());
//             $savedFiles[] = $srt->getClientOriginalName();
//         }

//         return response()->json([
//             'message' => 'Files berhasil disimpan',
//             'files' => $savedFiles
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'Error: ' . $e->getMessage()
//         ], 500);
//     }
// });