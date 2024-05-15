<x-filament::dropdown>
    <x-slot name="trigger">
        <x-filament::button>
            {{ $locale }}
        </x-filament::button>
    </x-slot>
    
    <x-filament::dropdown.list>
        @foreach($languages as $key => $value)
            @if($key !== $locale)
                <x-filament::dropdown.list.item wire:click="setLocale('{{ $key }}')">
                    {{ $value }}
                </x-filament::dropdown.list.item>
            @endif
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>