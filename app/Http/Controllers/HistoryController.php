<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Detection;

class HistoryController extends Controller
{
    //
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 25); // default 25
        $detections = Detection::orderBy('created_at', 'desc')->paginate($perPage);
        return view('history', compact('detections', 'perPage'));
    }
 
    public function show($id)
    {
        $detection = Detection::findOrFail($id);

        return view('results', [
            'filePath' => $detection->filename_original,
            'detectedFilePath' => $detection->detected_file_path,
            'isVideo' => $detection->is_video,
            'predictions' => json_decode($detection->predictions ?? '[]', true),
            'trackPoints' => json_decode($detection->track_points ?? '[]', true),
            'srt_file_path' => $detection->srt_file_path ?? null,
        ]);
    }
}
