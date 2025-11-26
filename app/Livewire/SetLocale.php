<?php

namespace App\Livewire;

use Livewire\Component;

class SetLocale extends Component
{
    public function setLocale($locale)
    {
        session()->put('locale', $locale);
        cookie()->queue(cookie()->forever('user-language', $locale));

        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.set-locale')->with([
            'languages' => ['he' => 'עברית', 'en' => 'English'],
            'locale' => app()->getLocale(),
        ]);
    }
}
