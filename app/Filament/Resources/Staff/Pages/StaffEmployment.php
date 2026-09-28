<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Enums\EmploymentType;
use App\Enums\PayFrequency;
use App\Filament\Resources\Staff\StaffResource;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\StaffEmploymentDetail;
use App\Services\StaffEmploymentService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class StaffEmployment extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StaffResource::class;

    protected string $view = 'filament.pages.staff-employment';

    protected static ?string $title = 'Employment details';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);
        $this->authorize('view staff employment details');
        $data = app(StaffEmploymentService::class)->forStaff($this->record, auth('staff')->user());
        foreach (['employment_date', 'confirmation_date', 'termination_date'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = substr($data[$field], 0, 10);
            }
        }
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $fields = [
            Select::make('department_id')->options(fn () => Department::pluck('name', 'id'))->searchable(),
            Select::make('job_position_id')->options(fn () => JobPosition::with('department')->get()->mapWithKeys(fn ($p) => [$p->id => $p->department->name.' - '.$p->name]))->searchable(),
            Select::make('employment_type')->options(array_column(EmploymentType::cases(), 'value', 'value'))->required(),
            DatePicker::make('employment_date')->required(), DatePicker::make('confirmation_date'), DatePicker::make('termination_date')->maxDate(today())->helperText('Recording termination immediately disables staff access.'),
            TextInput::make('emergency_contact_name'), TextInput::make('emergency_contact_phone'), TextInput::make('emergency_contact_relationship'),
        ];
        foreach (auth('staff')->user()->can('view staff salaries') ? StaffEmploymentDetail::SENSITIVE : [] as $field) {
            $component = $field === 'pay_frequency' ? Select::make($field)->options(array_column(PayFrequency::cases(), 'value', 'value')) : TextInput::make($field);
            $fields[] = $component->visible(fn () => auth('staff')->user()->can('view staff salaries'))->disabled(fn () => ! auth('staff')->user()->can('update staff salaries'))->dehydrated(fn () => auth('staff')->user()->can('view staff salaries') && auth('staff')->user()->can('update staff salaries'));
        }

        return $schema->components($fields)->columns(2)->statePath('data')->disabled(! auth('staff')->user()->can('update staff employment details'));
    }

    public function save(): void
    {
        $this->authorize('view', $this->record);
        $this->authorize('view staff employment details');
        app(StaffEmploymentService::class)->save($this->record, $this->form->getState(), auth('staff')->user());
        Notification::make()->title('Employment details saved')->success()->send();
    }
}
