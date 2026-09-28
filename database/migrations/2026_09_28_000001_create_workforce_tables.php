<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('code')->unique();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->foreignId('manager_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('job_positions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('department_id')->constrained()->restrictOnDelete();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['department_id', 'is_active']);
        });
        Schema::create('staff_employment_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->unique()->constrained('staff')->restrictOnDelete();
            $t->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('job_position_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('employment_type');
            $t->date('employment_date');
            $t->date('confirmation_date')->nullable();
            $t->date('termination_date')->nullable();
            $t->decimal('basic_salary', 15, 2)->nullable();
            $t->string('pay_frequency')->nullable();
            foreach (['bank_name', 'bank_account_name', 'bank_account_number', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'] as $field) {
                $t->string($field)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('work_shifts', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->string('code')->unique();
            $t->time('start_time');
            $t->time('end_time');
            $t->unsignedInteger('grace_period_minutes')->default(0);
            $t->unsignedInteger('break_minutes')->default(0);
            $t->boolean('is_overnight')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('staff_shift_assignments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $t->foreignId('work_shift_id')->constrained()->restrictOnDelete();
            $t->date('effective_from');
            $t->date('effective_until')->nullable();
            $t->foreignId('assigned_by')->constrained('staff')->restrictOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['staff_id', 'effective_from', 'effective_until'], 'shift_assignment_dates');
        });
        Schema::create('staff_attendance', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $t->foreignId('work_shift_id')->nullable()->constrained()->restrictOnDelete();
            $t->date('attendance_date');
            $t->string('status');
            $t->dateTime('clock_in_at')->nullable();
            $t->dateTime('clock_out_at')->nullable();
            foreach (['late_minutes', 'early_departure_minutes', 'overtime_minutes'] as $field) {
                $t->unsignedInteger($field)->default(0);
            }
            $t->unsignedInteger('worked_minutes')->nullable();
            $t->string('clock_in_method')->nullable();
            $t->string('clock_out_method')->nullable();
            $t->text('notes')->nullable();
            // Preserve the calculation basis when shift configuration changes later.
            $t->json('shift_snapshot')->nullable();
            $t->timestamps();
            $t->unique(['staff_id', 'attendance_date']);
            $t->index(['attendance_date', 'status']);
        });
        Schema::create('attendance_adjustments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('staff_attendance_id')->constrained('staff_attendance')->restrictOnDelete();
            $t->string('adjustment_type');
            $t->text('old_value')->nullable();
            $t->text('new_value')->nullable();
            $t->text('reason');
            $t->foreignId('requested_by')->constrained('staff')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->string('status')->default('PENDING');
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->string('code')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_paid')->default(false);
            $t->boolean('requires_approval')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('staff_leave_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('request_number')->unique();
            $t->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $t->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('total_days', 8, 2);
            $t->text('reason')->nullable();
            $t->string('status')->default('PENDING');
            $t->foreignId('approved_by')->nullable()->constrained('staff')->restrictOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
            $t->index(['staff_id', 'status', 'start_date', 'end_date'], 'staff_leave_dates');
        });
    }

    public function down(): void
    {
        foreach (['staff_leave_requests', 'leave_types', 'attendance_adjustments', 'staff_attendance', 'staff_shift_assignments', 'work_shifts', 'staff_employment_details', 'job_positions', 'departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
