<?php

namespace App\Filament\Pages;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Services\BusinessSettingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class BusinessSettings extends Page
{
    protected string $view = 'filament.pages.business-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth('staff')->user()?->can('view business settings') ?? false;
    }

    public function mount(): void
    {
        $settings = app(BusinessSettingService::class)->get();
        $this->form->fill(['business' => $settings->business->only(array_diff((new Business)->getFillable(), ['logo_path', 'is_active'])), 'settings' => $settings->only(array_diff((new BusinessSetting)->getFillable(), ['business_id']))]);
    }

    public function form(Schema $schema): Schema
    {
        $fields = [TextInput::make('business.name')->required()->maxLength(255)];
        foreach (['legal_name', 'registration_number', 'phone', 'alternate_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code'] as $field) {
            $fields[] = TextInput::make('business.'.$field)->maxLength(255);
        }
        $fields[] = TextInput::make('business.email')->email()->maxLength(255);
        $fields[] = TextInput::make('settings.currency')->required()->length(3);
        $fields[] = Select::make('settings.timezone')->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))->searchable()->required();
        $fields[] = Select::make('settings.date_format')->options(['d/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'm/d/Y' => 'MM/DD/YYYY'])->required();
        $fields[] = Select::make('settings.time_format')->options(['H:i' => '24 hour', 'h:i A' => '12 hour'])->required();
        foreach (['low_stock_notification', 'allow_customer_online_orders', 'allow_cash_payments', 'allow_bank_transfer', 'allow_pos_payments', 'online_payment_enabled'] as $field) {
            $fields[] = Toggle::make('settings.'.$field);
        }
        $fields[] = Select::make('settings.default_payment_provider')->options(['paystack' => 'Paystack']);
        foreach (['invoice', 'receipt', 'order', 'sale'] as $prefix) {
            $fields[] = TextInput::make('settings.'.$prefix.'_prefix')->required()->maxLength(10);
        }

        return $schema->components($fields)->columns(2)->statePath('data')->disabled(! auth('staff')->user()->can('update business settings'));
    }

    public function save(): void
    {
        $this->authorize('update business settings');
        $data = $this->form->getState();
        app(BusinessSettingService::class)->update($data['business'], $data['settings'], auth('staff')->user());
        Notification::make()->title('Business settings saved')->success()->send();
    }
}
