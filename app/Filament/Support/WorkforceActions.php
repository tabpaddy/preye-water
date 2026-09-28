<?php

namespace App\Filament\Support;

use App\Enums\AdjustmentType;
use App\Enums\AttendanceAdjustmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceAdjustment;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffLeaveRequest;
use App\Models\WorkShift;
use App\Services\AttendanceAdjustmentService;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class WorkforceActions
{
    public static function attendance(): array
    {
        return [
            Action::make('clockOut')->label('Clock out now')->requiresConfirmation()->authorize(fn () => auth('staff')->user()->can('record attendance'))
                ->visible(fn (StaffAttendance $record) => $record->clock_in_at && ! $record->clock_out_at)
                ->action(fn (StaffAttendance $record) => app(AttendanceService::class)->clockOut($record, auth('staff')->user())),
            Action::make('requestAdjustment')->label('Request correction')->authorize(fn () => auth('staff')->user()->can('adjust attendance'))
                ->schema([
                    Select::make('adjustment_type')->options(array_column(AdjustmentType::cases(), 'value', 'value'))->required()->live(),
                    TextInput::make('clock_value')->label('Corrected time')->type('datetime-local')->helperText('Business local time. Leave blank to clear this clock time.')->visible(fn (Get $get) => in_array($get('adjustment_type'), ['CLOCK_IN', 'CLOCK_OUT'])),
                    Select::make('status_value')->label('Corrected status')->options(array_column(AttendanceStatus::cases(), 'value', 'value'))->required()->visible(fn (Get $get) => $get('adjustment_type') === 'STATUS'),
                    Select::make('shift_value')->label('Corrected shift')->options(fn () => WorkShift::pluck('name', 'id'))->searchable()->helperText('Leave blank for no shift.')->visible(fn (Get $get) => $get('adjustment_type') === 'SHIFT'),
                    Textarea::make('reason')->required(),
                ])
                ->action(function (StaffAttendance $record, array $data) {
                    $data['new_value'] = match ($data['adjustment_type']) {
                        'CLOCK_IN','CLOCK_OUT' => $data['clock_value'] ?? null,'STATUS' => $data['status_value'] ?? null,'SHIFT' => isset($data['shift_value']) ? (string) $data['shift_value'] : null
                    };
                    app(AttendanceAdjustmentService::class)->request($record, $data, auth('staff')->user());
                }),
        ];
    }

    public static function adjustments(): array
    {
        return [
            Action::make('approve')->requiresConfirmation()->authorize(fn () => auth('staff')->user()->can('approve attendance adjustments'))->visible(fn (AttendanceAdjustment $record) => $record->status === AttendanceAdjustmentStatus::PENDING)
                ->action(fn (AttendanceAdjustment $record) => app(AttendanceAdjustmentService::class)->approve($record, auth('staff')->user())),
            Action::make('reject')->color('danger')->requiresConfirmation()->authorize(fn () => auth('staff')->user()->can('approve attendance adjustments'))->visible(fn (AttendanceAdjustment $record) => $record->status === AttendanceAdjustmentStatus::PENDING)
                ->action(fn (AttendanceAdjustment $record) => app(AttendanceAdjustmentService::class)->reject($record, auth('staff')->user())),
        ];
    }

    public static function leave(): array
    {
        return [
            Action::make('approve')->requiresConfirmation()->authorize(fn () => auth('staff')->user()->can('approve leave requests'))->visible(fn (StaffLeaveRequest $record) => $record->status === LeaveRequestStatus::PENDING)
                ->action(fn (StaffLeaveRequest $record) => app(LeaveService::class)->approve($record, auth('staff')->user())),
            Action::make('reject')->color('danger')->authorize(fn () => auth('staff')->user()->can('approve leave requests'))->visible(fn (StaffLeaveRequest $record) => $record->status === LeaveRequestStatus::PENDING)
                ->schema([Textarea::make('rejection_reason')->required()])
                ->action(fn (StaffLeaveRequest $record, array $data) => app(LeaveService::class)->reject($record, $data['rejection_reason'], auth('staff')->user())),
            Action::make('cancel')->color('danger')->requiresConfirmation()->authorize(fn () => auth('staff')->user()->can('cancel leave requests'))->visible(fn (StaffLeaveRequest $record) => in_array($record->status, [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED]))
                ->action(fn (StaffLeaveRequest $record) => app(LeaveService::class)->cancel($record, auth('staff')->user())),
        ];
    }

    public static function clockIn(): Action
    {
        return Action::make('clockIn')->label('Clock in now')->authorize(fn () => auth('staff')->user()->can('record attendance'))->schema([WorkforceFields::staff()->required()])
            ->action(fn (array $data) => app(AttendanceService::class)->clockIn(Staff::findOrFail($data['staff_id']), auth('staff')->user()));
    }
}
