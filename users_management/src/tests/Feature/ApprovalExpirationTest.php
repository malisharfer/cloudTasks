<?php

namespace Tests\Feature;

use App\Enums\Requests\Status;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ApprovalExpirationTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_it_deletes_expired_requests()
    {
        $request = Request::factory()->create(['update_status_date' => now()->subWeek(), 'status' => Status::Approved]);

        Request::requestDeletion();

        $this->assertModelMissing($request);
    }
}
