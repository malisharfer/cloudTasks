<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Filament\Resources\RequestResource\Pages\EditRequest;
use App\Filament\Resources\RequestResource;
use App\Models\Request;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteRequestTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_delete() {
        $request = Request::factory()->create();
     
        Livewire::test(EditRequest::class, [
            'record' => $request->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);
     
        $this->assertModelMissing($request);
    }
}
