<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        @can('update business settings')
            <x-filament::button type="submit">Save settings</x-filament::button>
        @endcan
    </form>
</x-filament-panels::page>
