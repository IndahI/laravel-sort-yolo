<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Detection;
use Intervention\Image\ImageManager;

class YOLOController extends Controller
{
    public function getProgress()
    {
        $path = storage_path('app/progress.txt');
        $progress = file_exists($path) ? intval(file_get_contents($path)) : 0;
        return response()->json(['progress' => $progress]);
    }

    public function predictAjax(Request $request)
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

    public function getResultView()
    {
        $jsonPath = storage_path('app/result.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'No result found'], 404);
        }

        $response = json_decode(file_get_contents($jsonPath), true);

        $detectionId = $response['id'] ?? null;
        if (!$detectionId) {
            return response()->json(['error' => 'Detection ID not found in result'], 400);
        }

        $detection = Detection::find($detectionId);
        if (!$detection) {
            return response()->json(['error' => 'Detection not found in database'], 404);
        }

        $originalFilename = $detection->filename_original; // sudah disimpan di awal saat upload
        $detectedFilePath = $response['saved_file'] ?? '';
        $isVideo = $response['isVideo'] ?? false;
        $predictions = $response['predictions'] ?? [];
        $trackFrames = $response['trackpoint'] ?? [];
        $srtPath = $response['srt_file_path'] ?? null;

        $trackPoints = [];

        // Ekstrak GPS dari SRT file
        if ($srtPath && file_exists($srtPath)) {
            $srtContent = file_get_contents($srtPath);

            $pattern = '/SrtCnt\s*:\s*(\d+).*?\[latitude:\s*(-?\d+\.\d+)\]\s*\[longitude:\s*(-?\d+\.\d+)\]/s';
            preg_match_all($pattern, $srtContent, $matches, PREG_SET_ORDER);

            $gpsByFrame = [];
            foreach ($matches as $match) {
                $frame = (int)$match[1] - 1;
                $gpsByFrame[$frame] = [
                    'latitude' => (float)$match[2],
                    'longitude' => (float)$match[3],
                ];
            }

            foreach ($trackFrames as $frame) {
                if (isset($gpsByFrame[$frame])) {
                    $trackPoints[] = [
                        'frame' => $frame,
                        'latitude' => $gpsByFrame[$frame]['latitude'],
                        'longitude' => $gpsByFrame[$frame]['longitude'],
                    ];
                }
            }
        }

          // Update ke database
        $detection->update([
            'detected_file_path' => $detectedFilePath,
            'is_video' => $isVideo,
            'predictions' => json_encode($predictions),
            'track_points' => json_encode($trackPoints),
            'srt_file_path' => $srtPath,
            'status' => 'completed'
        ]);

        return view('results', [
            'filePath' => $originalFilename,
            'detectedFilePath' => $detectedFilePath,
            'isVideo' => $isVideo,
            'predictions' => $predictions,
            'trackPoints' => $trackPoints,
            'srt_file_path' => $srtPath,
            'status' => 'completed'
        ]);
    }
}
