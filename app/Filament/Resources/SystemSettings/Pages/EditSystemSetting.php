<?php

namespace App\Filament\Resources\SystemSettings\Pages;

use App\Filament\Resources\SystemSettings\SystemSettingResource;
use App\Services\SystemSettingService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSystemSetting extends EditRecord
{
    protected static string $resource = SystemSettingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['group'] = $record->group;
        $data['key'] = $record->key;

        return app(SystemSettingService::class)->set($data, auth('staff')->user());
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
