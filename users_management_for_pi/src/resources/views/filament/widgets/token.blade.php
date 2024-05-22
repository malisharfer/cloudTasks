<x-filament-widgets::widget>
    @if (!$accessToken ||($accessToken && !$this->AccessExpirationTime()))
    <x-filament::section icon="heroicon-m-shield-exclamation" collapsible>
        
        <x-slot name="heading">Token status:</x-slot>
        <x-slot name="description">Login is required</x-slot>
        
        <x-filament::modal icon="heroicon-m-shield-exclamation" alignment="center">
            
            <x-slot name="trigger">
                <x-filament::button size="xl" icon="heroicon-m-key">Login</x-filament::button>
            </x-slot>
            
            <x-slot name="heading">Login</x-slot>
            <x-slot name="description">Optionally provide existing access token</x-slot>
            
            <x-filament::input.wrapper>
                <x-filament::input type="password" wire:model="accessToken" />
            </x-filament::input.wrapper>
            
            <x-slot name="footerActions">
                <x-filament::button wire:click="createAccessToken">Login</x-filament::button>
            </x-slot>
            
        </x-filament::modal>
        
    </x-filament::section>
    @else
    <div wire:poll.1s>
    <x-filament::section icon="heroicon-s-shield-check" icon-size="md" collapsible>
            <x-slot name="heading"> Token status</x-slot>
            <x-slot name="description">Refresh before {{ $this->formatTimeAgo() }} ago</x-slot>
            <x-slot name="headerEnd">
                <x-filament::button wire:click="createAccessToken" size="lg" icon="heroicon-c-arrow-path">Refresh</x-filament::button>
            </x-slot>
            <h1>in {{ $this->AccessExpirationTime() }} from now &emsp;<b>Access expiration</b></h1>
            <br>
            <x-filament::button wire:click="revoke" size="xl" icon="heroicon-m-trash">Revoke</x-filament::button>
        </x-filament::section>
    </div>
        @endif
</x-filament-widgets::widget>