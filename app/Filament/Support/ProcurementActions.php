<?php

namespace App\Filament\Support;

use App\Enums\SupplierPaymentStatus;
use App\Models\SupplierInvoice;
use App\Services\BusinessSettingService;
use App\Services\GoodsReceiptService;
use App\Services\ProcurementService;
use App\Services\SupplierPaymentService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;

class ProcurementActions
{
    public static function purchaseOrder(): array
    {
        return self::workflow(ProcurementService::class, 'purchase orders', 'order', [
            'submit' => ['submit', ['DRAFT']],
            'approve' => ['approve', ['PENDING_APPROVAL'], 'created_by'],
            'cancel' => ['cancel', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED']],
            'close' => ['close', ['RECEIVED']],
        ]);
    }

    public static function goodsReceipt(): array
    {
        return self::workflow(GoodsReceiptService::class, 'goods receipts', 'receipt', [
            'inspect' => ['inspect', ['DRAFT']],
            'reject' => ['inspect', ['DRAFT', 'INSPECTED']],
            'post' => ['post', ['INSPECTED']],
            'cancel' => ['cancel', ['DRAFT', 'INSPECTED']],
        ]);
    }

    public static function payment(): array
    {
        return [
            ...self::workflow(SupplierPaymentService::class, 'supplier payments', 'payment', [
                'complete' => ['approve', ['PENDING'], 'recorded_by'],
                'cancel' => ['approve', ['PENDING']],
                'fail' => ['approve', ['PENDING']],
            ]),
            Action::make('allocate')->visible(fn($record) => auth('staff')->user()->can('allocate supplier payments') && $record->status === SupplierPaymentStatus::COMPLETED)
                ->modalContent(fn($record) => view('filament.procurement-payment-summary', ['payment' => $record->load('allocations')]))
                ->schema(fn($record) => [
                    Repeater::make('allocations')->schema([
                        Select::make('supplier_invoice_id')->label('Invoice - total / paid / outstanding')->required()->searchable()
                            ->getSearchResultsUsing(fn(string $search) => SupplierInvoice::where('supplier_id', $record->supplier_id)->where('amount_due', '>', 0)->where('invoice_number', 'like', "%$search%")->limit(50)->get()->mapWithKeys(fn($invoice) => [$invoice->id => self::invoiceLabel($invoice)])->all())
                            ->getOptionLabelUsing(fn($value) => ($invoice = SupplierInvoice::where('supplier_id', $record->supplier_id)->find($value)) ? self::invoiceLabel($invoice) : null),
                        ProcurementFields::decimal('amount'),
                    ])->minItems(1)->maxItems(200),
                ])->action(function ($record, array $data) {
                    app(SupplierPaymentService::class)->allocate($record, $data['allocations'], auth('staff')->user());
                    $record->refresh();
                    Notification::make()->title('Payment allocated')->success()->send();
                }),
        ];
    }

    private static function invoiceLabel(SupplierInvoice $invoice): string
    {
        return $invoice->invoice_number . ' - ' . $invoice->total_amount . ' / ' . $invoice->amount_paid . ' / ' . $invoice->amount_due;
    }

    private static function workflow(string $service, string $domain, string $kind, array $configuration): array
    {
        $actions = [];
        foreach ($configuration as $method => $config) {
            [$permission, $statuses] = $config;
            $separateActor = $config[2] ?? null;
            $action = Action::make($method)->requiresConfirmation()
                ->visible(fn($record) => auth('staff')->user()->can($permission . ' ' . $domain) && in_array($record->status->value, $statuses, true) && (! $separateActor || auth('staff')->id() !== $record->$separateActor))
                ->modalContent(fn($record) => view('filament.procurement-review', ['record' => $record, 'kind' => $kind]))
                ->action(function ($record, array $data) use ($method, $service) {
                    if ($method === 'reject') {
                        app($service)->reject($record, auth('staff')->user(), $data['reason']);
                    } else {
                        app($service)->$method($record, auth('staff')->user());
                    }
                    $record->refresh();
                    Notification::make()->title('Procurement record updated')->success()->send();
                });
            if ($method === 'reject') {
                $action->schema([Textarea::make('reason')->required()->maxLength(5000)]);
            }
            if ($method === 'post') {
                $action->color('danger')->modalDescription('Posting increases inventory by the accepted quantities. Posted receipts cannot be edited.');
            }
            if ($method === 'complete') {
                $action->modalDescription('Confirm this supplier payment was made. Completed payment amounts cannot be edited.');
            }
            $actions[] = $action;
        }

        return $actions;
    }

    public static function dates(string $field): Filter
    {
        return Filter::make($field . '_range')->label(ucwords(str_replace('_', ' ', $field)))->schema([
            DatePicker::make('from'),
            DatePicker::make('until'),
        ])->query(function ($query, array $data) use ($field) {
            $datetime = in_array($field, ['received_at', 'paid_at']);
            $timezone = app(BusinessSettingService::class)->get()->timezone;

            return $query->when($data['from'] ?? null, fn($q, $date) => $q->where($field, '>=', $datetime ? CarbonImmutable::parse($date, $timezone)->startOfDay()->utc() : $date))
                ->when($data['until'] ?? null, fn($q, $date) => $q->where($field, '<=', $datetime ? CarbonImmutable::parse($date, $timezone)->endOfDay()->utc() : $date));
        });
    }
}
