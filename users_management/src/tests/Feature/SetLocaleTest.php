<?php

namespace Tests\Feature;

use App\Livewire\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Livewire\Livewire;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_set_locale(): void
    {
        $this->get('/admin')->assertSeeLivewire(SetLocale::class);
        Livewire::test(SetLocale::class)
            ->call('setLocale', 'en')
            ->assertSee('en')
            ->call('setLocale', 'he')
            ->assertSee('he')
            ->call('setLocale', 'fr')
            ->assertDontSee('fr');
    }
}
