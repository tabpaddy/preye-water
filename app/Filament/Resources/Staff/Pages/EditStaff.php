<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\Person;
use App\Services\StaffService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, $this->record->person->only((new Person)->getFillable()), ['password' => '', 'roles' => $this->record->roles()->pluck('id')->all()]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(StaffService::class)->update($record, $data, auth('staff')->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->using(function (Model $record) {
                app(StaffService::class)->delete($record, auth('staff')->user());

                return true;
            }),
        ];
    }
}
