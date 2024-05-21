<?php

namespace Tests\Unit;

use App\Enums\Options;
use App\Enums\Requests\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class OptionTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_get_options()
    {
        $array = ['new' => 'חדש', 'approved' => 'אושר', 'denied' => 'נדחה'];
        $this->assertEquals(Options::getOptions(Status::Cases()), $array);
    }
}
