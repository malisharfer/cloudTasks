<?php

namespace App\Filament\Notifications\Concerns;

use Closure;

trait HasCommonKey
{
    protected string|Closure|null $commonKey = null;

    public function commonKey(string|Closure|null $commonKey): static
    {
        $this->commonKey = $commonKey;

        return $this;
    }

    public function getCommonKey(): ?string
    {
        return $this->evaluate($this->commonKey);
    }
}
