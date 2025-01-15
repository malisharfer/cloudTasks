@php
    $id = $getId();
    $isDisabled = $isDisabled();
    $prefixIcon = $getPrefixIcon();
    $statePath = $getStatePath();
    $config =$getConfig();
    $attribs = [
        "disabled" => $isDisabled,
        "themeAsset" => $getThemeAsset(),
        'mode' => $getMode(),
    ];
@endphp
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <link rel="stylesheet" id="pickr-theme" type="text/css" href="{{$getThemeAsset()}}">
    <div
        x-data="flatpickrDatepicker({
                state: $wire.{{ $applyStateBindingModifiers("entangle('{$getStatePath()}')") }},
                packageConfig: @js($config),
                attribs: @js($attribs)
            })"
        x-ignore
        ax-load
        x-load-css="[
            '{{ asset('css/coolsam/flatpickr/flatpickr-css.css') }}'
        ]"
        ax-load-src="{{ asset('js/coolsam/flatpickr/components/flatpickr-component.js') }}"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            :prefix-icon="$prefixIcon"
            class="fi-fo-text-input"
        >
            <x-filament::input
                :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                    ->merge([
                        'disabled' => $isDisabled,
                        'id' => $id,
                        'x-ref' => 'picker',
                        'x-model' => 'state',
                        'readonly' => $isReadOnly(),
                        'required' => $isRequired() && (! $isConcealed),
                        'type' => 'text',
                    ], escape: false)
            "
            />
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
