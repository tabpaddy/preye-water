<?php

namespace App\Filament\Resources\StaffAttendances\Tables;

use App\Enums\AttendanceStatus;
use App\Filament\Support\WorkforceActions;
use App\Filament\Support\WorkforceFields;
use App\Models\Department;
use App\Services\BusinessSettingService;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffAttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('attendance_date'), TextColumn::make('staff.staff_number')->searchable(), TextColumn::make('staff.person.full_name'), TextColumn::make('staff.employmentDetail.department.name'), TextColumn::make('workShift.name'), TextColumn::make('clock_in_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone), TextColumn::make('clock_out_at')->dateTime(fn () => app(BusinessSettingService::class)->dateTimeFormat(), fn () => app(BusinessSettingService::class)->get()->timezone), TextColumn::make('status')->badge(), TextColumn::make('late_minutes'), TextColumn::make('early_departure_minutes'), TextColumn::make('worked_minutes'), TextColumn::make('overtime_minutes')])->filters([SelectFilter::make('status')->options(array_column(AttendanceStatus::cases(), 'value', 'value')), SelectFilter::make('work_shift_id')->relationship('workShift', 'name'), Filter::make('workforce')->schema([DatePicker::make('date'), WorkforceFields::staff(), Select::make('department_id')->options(fn () => Department::pluck('name', 'id'))])->query(fn ($query, $data) => $query->when($data['date'] ?? null, fn ($q, $date) => $q->whereDate('attendance_date', $date))->when($data['staff_id'] ?? null, fn ($q, $id) => $q->where('staff_id', $id))->when($data['department_id'] ?? null, fn ($q, $id) => $q->whereHas('staff.employmentDetail', fn ($e) => $e->where('department_id', $id))))])->recordActions([ViewAction::make(), ...WorkforceActions::attendance()])->defaultSort('id','desc');
    }
}
