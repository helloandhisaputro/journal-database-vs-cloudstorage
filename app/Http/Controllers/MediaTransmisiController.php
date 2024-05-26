<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaTransmisiController extends Controller
{
    public function runTestAwsS3(){
        $files = Storage::disk('s3')->files();
        return response()->json($files);
    }
}
