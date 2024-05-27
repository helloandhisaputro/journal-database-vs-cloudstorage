<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaTransmisiController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DownloadMediaTransmisiAWSS3 extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = (new MediaTransmisiController)->runTestDownloadAwsS3();

        dd($response);

        $response->assertStatus(200);
    }
}
