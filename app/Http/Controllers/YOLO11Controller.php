<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Detection;
use Intervention\Image\ImageManager;

class YOLO11Controller extends Controller
{
    public function predictYolo11(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,mp4,avi,mov,mkv|max:102400',
            'srt_file' => 'nullable|file|mimetypes:text/plain,text/srt',
        ]);

        $file = $request->file('file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('uploads', $filename, 'public');

        // Simpan srt jika ada
        $srtPath = null;
        if ($request->hasFile('srt_file')) {
            $srt = $request->file('srt_file');
            $srtPath = $srt->storeAs('uploads', time() . '.srt', 'public');
        }

        // Kosongkan progress
        file_put_contents(storage_path('app/progress.txt'), '0');

        // Buat file result.json kosong
        $resultPath = storage_path('app/result.json');
        file_put_contents($resultPath, json_encode([]));

        // Simpan data awal ke database
        $detection = Detection::create([
            'filename_original' => $filename,
            'detected_file_path' => '', // masih kosong, diisi nanti di getResultView
            'is_video' => in_array($file->getClientOriginalExtension(), ['mp4', 'avi', 'mov', 'mkv']),
            'predictions' => null, // akan diisi nanti
            'track_points' => null,
            'srtPath' => $srtPath,
            'status' => 'processing' // jika ada kolom status
        ]);

        // Jalankan script Python
        $python = "C:\\Users\\Indah\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
        $script = base_path('scripts/yolov11_predict.py');
        $fullInputPath = storage_path('app/public/' . $filePath);
        $fullSrtPath = $srtPath ? storage_path('app/public/' . $srtPath) : '';
        $detectionId = $detection->id;

        if ($fullSrtPath) {
            $cmd = "start /B \"\" \"$python\" \"$script\" \"$fullInputPath\" \"$fullSrtPath\" \"$detectionId\" >nul 2>&1";
        } else {
            $cmd = "start /B \"\" \"$python\" \"$script\" \"$fullInputPath\" \"$detectionId\" >nul 2>&1";
        }

        Log::info("Menjalankan Python script:", [
            'cmd' => $cmd,
            'timestamp' => now()->toDateTimeString()
        ]);

        pclose(popen($cmd, "r"));

        return response()->json(['status' => 'processing']);
    }
}
