<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaTransmisiController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DownloadMediaTransmisiDatabase extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = (new MediaTransmisiController)->runTestMediaDownloadDatabase();

        dd($response);

        $response->assertStatus(200);
    }
}
