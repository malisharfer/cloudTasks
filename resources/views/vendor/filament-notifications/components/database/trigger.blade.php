<div
    x-data="{}"
    x-on:click="$dispatch('open-modal', { id: 'my-database-notifications' })"
    {{ $attributes->class(['inline-block']) }}
>
    {{ $slot }}
</div>
