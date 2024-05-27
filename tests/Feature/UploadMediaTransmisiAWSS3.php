<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Http\Controllers\MediaTransmisiController;

class UploadMediaTransmisiAWSS3 extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = (new MediaTransmisiController)->runTestUploadAwsS3();

        dd($response);

        $response->assertStatus(200);
    }
}
