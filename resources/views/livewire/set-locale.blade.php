<x-slot name="trigger">
    <x-filament::button>
        {{ $locale }}
    </x-filament::button>
</x-slot>

@foreach($languages as $key => $value)
    @if($key !== $locale)
        <x-filament::button wire:click="setLocale('{{ $key }}')">
            {{ $value }}
        </x-filament::button>
    @endif
@endforeach
