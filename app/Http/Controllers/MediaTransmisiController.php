<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MediaTransmisiController extends Controller
{
    public function runTestAwsS3()
    {
        $startTime = microtime(true);
        $files = Storage::disk('s3')->files();
        $localDirectory = storage_path('app/s3-downloads/');

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
            } catch (\Exception $e) {
                // Anggap exception sebagai paket yang hilang
                $lostPackets++;
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

        return response()->json([
            'files' => $files,
            'total_size_bytes' => $totalSize,
            'download_time_seconds' => $duration,
            'throughput_bps' => $throughputBytesPerSecond * 8,
            'mean_delay_seconds' => $meanDelay,
            'jitter_seconds' => $jitter,
            'packet_loss_percentage' => $packetLoss,
        ]);
    }
}