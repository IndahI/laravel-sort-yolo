<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detection extends Model
{
    //
        protected $fillable = [
        'filename_original',
        'detected_file_path',
        'is_video',
        'predictions',
        'track_points',
    ];

    protected $casts = [
        'predictions' => 'array',
        'track_points' => 'array',
    ];
}
