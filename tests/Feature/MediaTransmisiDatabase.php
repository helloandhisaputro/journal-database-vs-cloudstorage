<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaTransmisiController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MediaTransmisiDatabase extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = (new MediaTransmisiController)->runTestMediaDatabase();

        dd($response);

        $response->assertStatus(200);
    }
}
