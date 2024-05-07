<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportRequestsTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;
    
    public function testExportRequests()
    {

        Http::fake([
            'requests/export' => Http::response(null, 200),
        ]);

        $response = $this->get('requests/export');

        $response->assertStatus(200);

        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}