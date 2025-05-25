<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Detection;
use Intervention\Image\ImageManager;

class YOLOController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function predictCombined(Request $request)
    {
        set_time_limit(3000);

        $request->validate([
            'file' => 'required|mimes:jpeg,png,jpg,mp4,avi,mov,mkv|max:102400',
        ]);

        // ======== 1. Proses FILE Video/Gambar =========
        $file = $request->file('file');
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('uploads', $fileName, 'public');
        $fileFullPath = storage_path('app/public/' . $filePath);
        $isVideo = in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'avi', 'mov', 'mkv']);

        $gpsFromImage = null;

        // ✅ Ambil GPS dari EXIF jika file gambar
        if (!$isVideo) {
            try {
                $exif = @exif_read_data($fileFullPath);
                if ($exif && isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
                    $lat = $this->getGpsDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
                    $lng = $this->getGpsDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
                    $gpsFromImage = ['lat' => $lat, 'lng' => $lng];
                }
            } catch (\Exception $e) {
                Log::error("Gagal membaca metadata EXIF: " . $e->getMessage());
            }
        }

        // Eksekusi skrip YOLO
        $pythonScript = base_path('scripts/yolov11_predict.py');
        $python = "C:\\Python313\\python.exe";
        $command = escapeshellcmd("$python $pythonScript $fileFullPath");
        $output = shell_exec($command);

        if (!$output || !preg_match('/\{.*\}/s', $output, $matches)) {
            return back()->with('error', 'Gagal memproses YOLO atau format JSON tidak valid.');
        }

        $response = json_decode(trim($matches[0]), true);
        if (!$response || !isset($response['saved_file'])) {
            return back()->with('error', 'Format JSON tidak sesuai atau kosong.');
        }

        $detectedFilePath = $response['saved_file'];
        $isVideo = $response['isVideo'] ?? false;
        $predictions = $response['predictions'] ?? [];
        $frameTrackpoints = $response['trackpoint'] ?? [];

        // ======== 2. Proses FILE SRT =========
        $srtFile = $request->file('srt_file');
        $srtContent = '';

        if ($isVideo) {
            if ($srtFile && $srtFile->isValid()) {
                $srtContent = file_get_contents($srtFile->getRealPath());
            } else {
                return back()->with('error', 'File metadata (.srt) wajib di-upload untuk video.');
            }
        }

        preg_match_all('/\[latitude:\s*(-?\d+\.\d+)\]\s*\[longitude:\s*(-?\d+\.\d+)\]/', $srtContent, $matches, PREG_SET_ORDER);
        
        $trackPoints = [];

        foreach ($matches as $index => $match) {
            if (in_array($index, $frameTrackpoints)) {
                $trackPoints[] = [
                    'frame' => $index,
                    'lat' => floatval($match[1]),
                    'lng' => floatval($match[2]),
                ];
            }
        }

        // Tambahkan GPS dari EXIF jika bukan video dan tidak ada trackpoint dari metadata
        if (!$isVideo && empty($trackPoints) && $gpsFromImage) {
            $trackPoints[] = [
                'frame' => 0,
                'lat' => $gpsFromImage['lat'],
                'lng' => $gpsFromImage['lng'],
            ];
        }

        // ======== 3. Simpan ke database =========
        $detection = Detection::create([
            'filename_original' => $file->getClientOriginalName(),
            'detected_file_path' => $detectedFilePath,
            'is_video' => $isVideo,
            'predictions' => $predictions,
            'track_points' => $trackPoints,
        ]);

        return view('results', [
            'filePath' => $filePath,
            'detectedFilePath' => $detectedFilePath,
            'isVideo' => $isVideo,
            'predictions' => $predictions,
            'trackPoints' => $trackPoints
        ]);
    }


    private function getGpsDecimal($coordinate, $hemisphere)
    {
        for ($i = 0; $i < 3; $i++) {
            $part = explode('/', $coordinate[$i]);
            $coordinate[$i] = count($part) == 2 ? floatval($part[0]) / floatval($part[1]) : floatval($part[0]);
        }

        $decimal = $coordinate[0] + ($coordinate[1] / 60.0) + ($coordinate[2] / 3600.0);
        return ($hemisphere == 'S' || $hemisphere == 'W') ? -$decimal : $decimal;
    }


    public function history()
    {
        $detections = Detection::orderBy('created_at', 'desc')->get();
        return view('history', compact('detections'));
    }
        
    public function show($id)
    {
        $detection = Detection::findOrFail($id);
        return view('results', [
            'filePath' => $detection->filename_original,
            'detectedFilePath' => $detection->detected_file_path,
            'isVideo' => $detection->is_video,
            'predictions' => $detection->predictions ?? [],
            'trackPoints' => $detection->track_points ?? [],
        ]);
    }

}
