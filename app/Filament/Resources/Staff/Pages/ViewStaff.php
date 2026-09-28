<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(), Action::make('employment')->label('Employment details')->visible(fn () => auth('staff')->user()->can('view staff employment details'))->url(fn () => StaffResource::getUrl('employment', ['record' => $this->record])),
        ];
    }
}
