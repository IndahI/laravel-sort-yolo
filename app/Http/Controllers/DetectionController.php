<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Detection;

class DetectionController extends Controller
{
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

        $originalFilename = $detection->filename_original;
        $detectedFilePath = $response['saved_file'] ?? '';
        $isVideo = $response['isVideo'] ?? false;
        $predictions = $response['predictions'] ?? [];
        $trackFrames = $response['trackpoint'] ?? [];
        $srtPath = $response['srt_file_path'] ?? null;

        $trackPoints = [];

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

        if (!$detection->is_video) {
            $imagePath = storage_path('app/public/uploads/' . $detection->filename_original);
            $gps = $this->getGPSFromImage($imagePath);

            if ($gps) {
                $trackPoints[] = [
                    'frame' => 0,
                    'latitude' => $gps['latitude'],
                    'longitude' => $gps['longitude'],
                ];
            }
        }

        $detection->update([
            'detected_file_path' => $detectedFilePath,
            'is_video' => $isVideo,
            'predictions' => json_encode($predictions),
            'track_points' => json_encode($trackPoints),
            'srt_file_path' => $srtPath,
            'status' => 'completed'
        ]);

        return response()->json(['id' => $detection->id]);
    }

    private function getGPSFromImage($filePath)
    {
        if (!file_exists($filePath)) return null;

        $exif = exif_read_data($filePath, 0, true);
        if (!$exif || !isset($exif['GPS'])) return null;

        $gps = $exif['GPS'];

        if (empty($gps['GPSLatitude']) || empty($gps['GPSLongitude']) || 
            empty($gps['GPSLatitudeRef']) || empty($gps['GPSLongitudeRef'])) {
            return null;
        }

        $lat = $this->getGPSCoordinate($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
        $lng = $this->getGPSCoordinate($gps['GPSLongitude'], $gps['GPSLongitudeRef']);

        return ['latitude' => $lat, 'longitude' => $lng];
    }

    private function getGPSCoordinate($coordinate, $hemisphere)
    {
        for ($i = 0; $i < 3; $i++) {
            $part = explode('/', $coordinate[$i]);
            $coordinate[$i] = count($part) === 1 ? floatval($part[0]) : floatval($part[0]) / floatval($part[1]);
        }

        $decimal = $coordinate[0] + $coordinate[1] / 60 + $coordinate[2] / 3600;
        return ($hemisphere == 'S' || $hemisphere == 'W') ? -$decimal : $decimal;
    }
}
