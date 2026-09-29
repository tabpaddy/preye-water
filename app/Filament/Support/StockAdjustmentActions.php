<?php

namespace App\Filament\Support;

use App\Enums\StockAdjustmentStatus as Status;
use App\Services\StockAdjustmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class StockAdjustmentActions
{
    public static function make(): array
    {
        $actions = [];
        foreach ([
            'submit' => ['submit', [Status::DRAFT]],
            'approve' => ['approve', [Status::PENDING_APPROVAL]],
            'reject' => ['approve', [Status::PENDING_APPROVAL]],
            'cancel' => ['cancel', [Status::DRAFT, Status::PENDING_APPROVAL, Status::APPROVED]],
            'post' => ['post', [Status::APPROVED]],
        ] as $method => [$permission,$statuses]) {
            $action = Action::make($method)->requiresConfirmation()
                ->visible(fn ($record) => auth('staff')->user()->can($permission.' stock adjustments') && in_array($record->status, $statuses, true) && (! in_array($method, ['approve', 'reject'], true) || $record->created_by !== auth('staff')->id()))
                ->action(function ($record, array $data) use ($method) {
                    $service = app(StockAdjustmentService::class);
                    if ($method === 'reject') {
                        $service->reject($record, auth('staff')->user(), $data['reason']);
                    } else {
                        $service->$method($record, auth('staff')->user());
                    }
                    $record->refresh();
                    Notification::make()->title('Stock adjustment updated')->success()->send();
                });
            if ($method === 'reject') {
                $action->schema([Textarea::make('reason')->required()->maxLength(5000)]);
            }
            if ($method === 'post') {
                $action->color('danger')->modalDescription('Posting changes stock balances. Posted adjustments cannot be edited; corrections require a new reviewed adjustment.');
            }
            if (in_array($method, ['approve', 'reject', 'post'], true)) {
                $action->modalContent(fn ($record) => view('filament.stock-adjustment-review', [
                    'adjustment' => $record->loadMissing(['items.inventoryItem', 'creator', 'inventoryLocation']),
                ]));
            }
            $actions[] = $action;
        }

        return $actions;
    }
}
