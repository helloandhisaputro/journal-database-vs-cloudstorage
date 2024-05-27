<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\MediaFile;

class MediaTransmisiController extends Controller
{
    public function runTestDownloadAwsS3()
    {
        $startTime = microtime(true);
        $files = Storage::disk('s3')->files();
        $localDirectory = storage_path('app/media-downloads-cloudstorage/');
    
        if (!file_exists($localDirectory)) {
            mkdir($localDirectory, 0777, true);
        }
    
        $totalSize = 0;
        $totalPackets = 0;
        $lostPackets = 0;
        $delays = [];
    
        foreach ($files as $file) {
            $packetStartTime = microtime(true);
            try {
                $content = Storage::disk('s3')->get($file);
                file_put_contents($localDirectory . basename($file), $content);
                $totalSize += strlen($content); // ukuran file dalam byte
                $totalPackets++;
         
                // Simpan informasi file ke database
                if(env('APP_ENV') == 'development'){
                    $insert = new MediaFile;
                    $insert->file_name = basename($file);
                    $insert->file_blob = $content;
                    $insert->save();
                }
     
            } catch (\Exception $e) {
                // Anggap exception sebagai paket yang hilang
                $lostPackets++;
    
                // Log kesalahan jika ada
                Log::error('Error downloading or saving file ' . $file . ': ' . $e->getMessage());
            }
            $packetEndTime = microtime(true);
            $delays[] = $packetEndTime - $packetStartTime; // waktu dalam detik
        }
    
        $endTime = microtime(true);
        $duration = $endTime - $startTime; // waktu dalam detik
    
        // Throughput dalam bit per detik (bps)
        $throughputBytesPerSecond = $totalSize / $duration;
        $throughputKBytesPerSecond = $throughputBytesPerSecond / 1024;
        $throughputKbps = $throughputBytesPerSecond * 8 / 1024;
    
        // Hitung Mean Delay
        $meanDelay = array_sum($delays) / count($delays);
    
        // Hitung Jitter sebagai variasi standar dari delay
        $jitter = sqrt(array_sum(array_map(function ($delay) use ($meanDelay) {
            return pow($delay - $meanDelay, 2);
        }, $delays)) / count($delays));
    
        // Hitung Packet Loss dalam persentase
        $packetLoss = ($lostPackets / ($totalPackets + $lostPackets)) * 100;
    
        // Data QoS yang akan disimpan dalam log
        $qosData = [
            'total_size_bytes' => $totalSize,
            'download_time_seconds' => $duration,
            'throughput' => [
                'bytes_per_second' => $throughputBytesPerSecond,
                'kbytes_per_second' => $throughputKBytesPerSecond,
                'kbps' => $throughputKbps,
            ],
            'delay' => [
                'total_packets' => $totalPackets,
                'total_time_seconds' => $duration,
                'mean_delay_seconds' => $meanDelay,
            ],
            'jitter' => [
                'mean_delay_seconds' => $meanDelay,
                'jitter_seconds' => $jitter,
            ],
            'packet_loss' => [
                'total_packets' => $totalPackets,
                'lost_packets' => $lostPackets,
                'packet_loss_percentage' => $packetLoss,
            ],
        ];
    
        // Log detail perhitungan dengan format beautify JSON
        Log::info('QoS Calculation Details: ' . json_encode($qosData, JSON_PRETTY_PRINT));
    
        return response()->json([
            'total_size_bytes' => $totalSize,
            'download_time_seconds' => $duration,
            'throughput_bps' => $throughputBytesPerSecond * 8,
            'mean_delay_seconds' => $meanDelay,
            'jitter_seconds' => $jitter,
            'packet_loss_percentage' => $packetLoss,
        ]);
    }
    
    public function runTestMediaDownloadDatabase()
    {
        $startTime = microtime(true);
        $files = MediaFile::all();
        $localDirectory = storage_path('app/media-downloads-database/');
    
        if (!file_exists($localDirectory)) {
            mkdir($localDirectory, 0777, true);
        }
    
        $totalSize = 0;
        $totalPackets = $files->count();
        $lostPackets = 0;
        $delays = [];
    
        foreach ($files as $file) {
            $packetStartTime = microtime(true);
            try {
                // Simpan file ke sistem lokal
                file_put_contents($localDirectory . $file->file_name, $file->file_blob);
    
                // Hitung ukuran total file yang diunduh
                $totalSize += strlen($file->file_blob);
    
                // Log file yang berhasil diunduh
                Log::info('File downloaded and saved: ' . $file->file_name);
            } catch (\Exception $e) {
                // Anggap exception sebagai paket yang hilang
                $lostPackets++;
    
                // Log kesalahan jika ada
                Log::error('Error downloading or saving file: ' . $e->getMessage());
            }
    
            $packetEndTime = microtime(true);
            $delays[] = $packetEndTime - $packetStartTime; // waktu dalam detik
        }
    
        $endTime = microtime(true);
        $duration = $endTime - $startTime; // waktu dalam detik
    
        // Hitung throughput dalam bit per detik (bps)
        $throughputBytesPerSecond = $totalSize / $duration;
        $throughputKBytesPerSecond = $throughputBytesPerSecond / 1024;
        $throughputKbps = $throughputBytesPerSecond * 8 / 1024;
    
        // Hitung Mean Delay
        $meanDelay = array_sum($delays) / count($delays);
    
        // Hitung Jitter sebagai variasi standar dari delay
        $jitter = sqrt(array_sum(array_map(function ($delay) use ($meanDelay) {
            return pow($delay - $meanDelay, 2);
        }, $delays)) / count($delays));
    
        // Hitung Packet Loss dalam persentase
        $packetLoss = ($lostPackets / ($totalPackets + $lostPackets)) * 100;
    
        // Data QoS yang akan disimpan dalam log
        $qosData = [
            'total_size_bytes' => $totalSize,
            'download_time_seconds' => $duration,
            'throughput' => [
                'bytes_per_second' => $throughputBytesPerSecond,
                'kbytes_per_second' => $throughputKBytesPerSecond,
                'kbps' => $throughputKbps,
            ],
            'delay' => [
                'total_packets' => $totalPackets,
                'total_time_seconds' => $duration,
                'mean_delay_seconds' => $meanDelay,
            ],
            'jitter' => [
                'delays' => $delays,
                'mean_delay_seconds' => $meanDelay,
                'jitter_seconds' => $jitter,
            ],
            'packet_loss' => [
                'total_packets' => $totalPackets,
                'lost_packets' => $lostPackets,
                'packet_loss_percentage' => $packetLoss,
            ],
        ];
    
        // Log detail perhitungan dengan format beautify JSON
        Log::info('QoS Calculation Details: ' . json_encode($qosData, JSON_PRETTY_PRINT));
    
        // Menyiapkan respons JSON
        $response = [
            'total_size_bytes' => $totalSize,
            'download_time_seconds' => $duration,
            'throughput_bps' => $throughputBytesPerSecond * 8,
            'mean_delay_seconds' => $meanDelay,
            'jitter_seconds' => $jitter,
            'packet_loss_percentage' => $packetLoss,
        ];
    
        // Mengembalikan respons JSON
        return response()->json($response);
    }
    
    public function runTestUploadAwsS3()
    {
        $startTime = microtime(true);
        $localDirectory = public_path('medias/sample-upload/');
        $bucketName = config('filesystems.disks.s3.bucket');
        $subfolder = 'test-upload-s3/';
    
        $files = scandir($localDirectory);
        $totalSize = 0;
        $totalPackets = 0;
        $lostPackets = 0;
        $delays = [];
    
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
    
            $packetStartTime = microtime(true);
            try {
                // Upload file to S3
                $filePath = $localDirectory . $file;
                $s3Path = $subfolder . $file;
    
                // Use Laravel Storage facade to upload file to S3
                Storage::disk('s3')->put($s3Path, file_get_contents($filePath), 'public');
    
                // Log file uploaded successfully
                Log::info('File ' . $file . ' uploaded to S3 at path ' . $s3Path . ' .  local path : '.$filePath);
    
                $fileSize = filesize($filePath);
                $totalSize += $fileSize; // ukuran file dalam byte
                $totalPackets++;
    
            } catch (\Exception $e) {
                // Anggap exception sebagai paket yang hilang
                $lostPackets++;
    
                // Log kesalahan jika ada
                Log::error('Error uploading file ' . $file . ' to S3: ' . $e->getMessage());
            }
            $packetEndTime = microtime(true);
            $delays[] = $packetEndTime - $packetStartTime; // waktu dalam detik
        }
    
        $endTime = microtime(true);
        $duration = $endTime - $startTime; // waktu dalam detik
    
        // Throughput dalam bit per detik (bps)
        $throughputBytesPerSecond = $totalSize / $duration;
        $throughputKBytesPerSecond = $throughputBytesPerSecond / 1024;
        $throughputKbps = $throughputBytesPerSecond * 8 / 1024;
    
        // Hitung Mean Delay
        $meanDelay = array_sum($delays) / count($delays);
    
        // Hitung Jitter sebagai variasi standar dari delay
        $jitter = sqrt(array_sum(array_map(function ($delay) use ($meanDelay) {
            return pow($delay - $meanDelay, 2);
        }, $delays)) / count($delays));
    
        // Hitung Packet Loss dalam persentase
        $packetLoss = ($lostPackets / ($totalPackets + $lostPackets)) * 100;
    
        // Data QoS yang akan disimpan dalam log
        $qosData = [
            'total_size_bytes' => $totalSize,
            'upload_time_seconds' => $duration,
            'throughput' => [
                'bytes_per_second' => $throughputBytesPerSecond,
                'kbytes_per_second' => $throughputKBytesPerSecond,
                'kbps' => $throughputKbps,
            ],
            'delay' => [
                'total_files' => $totalPackets,
                'total_time_seconds' => $duration,
                'mean_delay_seconds' => $meanDelay,
            ],
            'jitter' => [
                'mean_delay_seconds' => $meanDelay,
                'jitter_seconds' => $jitter,
            ],
            'packet_loss' => [
                'total_files' => $totalPackets,
                'lost_files' => $lostPackets,
                'packet_loss_percentage' => $packetLoss,
            ],
        ];
    
        // Log detail perhitungan dengan format beautify JSON
        Log::info('QoS Calculation Details (Upload to S3): ' . json_encode($qosData, JSON_PRETTY_PRINT));
    
        return response()->json([
            'total_size_bytes' => $totalSize,
            'upload_time_seconds' => $duration,
            'throughput_bps' => $throughputBytesPerSecond * 8,
            'mean_delay_seconds' => $meanDelay,
            'jitter_seconds' => $jitter,
            'packet_loss_percentage' => $packetLoss,
        ]);
    }
}
