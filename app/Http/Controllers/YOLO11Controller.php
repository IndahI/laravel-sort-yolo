<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Detection;

class YOLO11Controller extends Controller
{
    public function predictYolo11(Request $request)
    {
        Log::info("Upload request diterima", [
            "time"     => now()->toDateTimeString(),
            "has_file" => $request->hasFile('file'),
            "has_srt"  => $request->hasFile('srt_file'),
        ]);

        $request->validate([
            'file'     => 'required|mimes:mp4,avi,mov,jpg,jpeg,png',
            'srt_file' => 'nullable|mimes:srt,txt'
        ]);

        // === Path target di volume sharing ===
        $uploadPath = public_path('media/input'); // /var/www/html/public/media/input
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // === Reset progress.txt ke 0 ===
        $progressFile = public_path('media/progress.txt');
        file_put_contents($progressFile, "0");

        // === Pindahkan file utama ===
        $mediaFile = $request->file('file');
        $filename  = time() . '_' . $mediaFile->getClientOriginalName();
        $mediaFile->move($uploadPath, $filename);

        // === Kalau ada file SRT, pindahkan juga ===
        $srtFilename = null;
        if ($request->hasFile('srt_file')) {
            $srtFilename = time() . '.srt';
            $request->file('srt_file')->move($uploadPath, $srtFilename);
        }

        // === Tentukan apakah file video ===
        $isVideo = in_array(
            strtolower($mediaFile->getClientOriginalExtension()),
            ['mp4', 'avi', 'mov']
        );

        // === Simpan data awal ke database ===
        $detection = Detection::create([
            'filename_original'   => $filename,
            'detected_file_path'  => null,  // nanti diisi watcher setelah YOLO selesai
            'is_video'            => $isVideo,
            'predictions'         => null,
            'track_points'        => null,
            'srt_file_path'       => $srtFilename ? "media/input/$srtFilename" : null,
            'status'              => 'pending', // watcher akan ambil job ini
        ]);

        return response()->json([
            'success'     => true,
            'detectionId' => $detection->id,
            'message'     => 'Upload berhasil, menunggu pemrosesan oleh worker Python.'
        ]);
    }
}
