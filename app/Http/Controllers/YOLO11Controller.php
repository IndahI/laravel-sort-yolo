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



// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\Detection;
// use Illuminate\Support\Facades\Log;
// use Exception;

// class YOLO11Controller extends Controller
// {
//     public function predictYolo11(Request $request)
//     {
//         set_time_limit(0);

//         try {
//             Log::info('Upload request diterima', ['time' => now()->toDateTimeString()]);

//             // === Validasi input ===
//             $request->validate([
//                 'file' => 'required|mimes:jpeg,png,jpg,mp4,avi,mov,mkv|max:102400',
//                 'srt_file' => 'nullable|mimes:srt,txt',
//             ]);

//             // === Simpan file media ke public/media ===
//             $file = $request->file('file');
//             $filename = time() . '.' . $file->getClientOriginalExtension();
//             $file->move(public_path('media'), $filename);

//             // === Simpan file SRT jika ada ===
//             $srtFilename = null;
//             if ($request->hasFile('srt_file')) {
//                 $srt = $request->file('srt_file');
//                 $srtFilename = time() . '.srt';
//                 $srt->move(public_path('media'), $srtFilename);
//             }

//             Log::info('File tersimpan', [
//                 'media' => $filename,
//                 'srt'   => $srtFilename
//             ]);

//             // === Buat record awal di database ===
//             $detection = Detection::create([
//                 'filename_original' => $filename,
//                 'detected_file_path' => '',
//                 'is_video' => in_array($file->getClientOriginalExtension(), ['mp4', 'avi', 'mov', 'mkv']),
//                 'predictions' => null,
//                 'track_points' => null,
//                 'srt_file_path' => $srtFilename,
//                 'status' => 'uploaded'
//             ]);

//             Log::info('Record database dibuat', ['id' => $detection->id]);

//             return response()->json([
//                 'status' => 'success',
//                 'id'     => $detection->id
//             ]);

//         } catch (Exception $e) {
//             Log::error('Terjadi error saat upload YOLO11', [
//                 'message' => $e->getMessage(),
//                 'trace'   => $e->getTraceAsString()
//             ]);

//             return response()->json([
//                 'status'  => 'error',
//                 'message' => 'Gagal upload file, cek log untuk detail.'
//             ], 500);
//         }
//     }
// }
