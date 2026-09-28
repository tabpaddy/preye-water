<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        @can('update staff employment details')
            <x-filament::button type="submit">Save employment details</x-filament::button>
        @endcan
    </form>
</x-filament-panels::page>
