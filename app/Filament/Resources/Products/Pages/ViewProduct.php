<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('replaceImage')->label('Upload image')
                ->visible(fn () => auth('staff')->user()->can('update products'))
                ->schema([
                    FileUpload::make('image')->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(2048)->storeFiles(false)->required(),
                ])
                ->action(function (array $data): void {
                    app(ProductService::class)->replaceImage($this->record, $data['image'], auth('staff')->user());
                    $this->record->refresh();
                }),
        ];
    }
}
