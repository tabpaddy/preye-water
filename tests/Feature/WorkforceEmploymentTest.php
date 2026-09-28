<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\StaffStatus;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Staff;
use App\Services\DepartmentService;
use App\Services\JobPositionService;
use App\Services\StaffEmploymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkforceEmploymentTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_departments_positions_and_manager_archival(): void
    {
        $manager = Staff::factory()->create();
        $department = app(DepartmentService::class)->create(['code' => 'PROD', 'name' => 'Production', 'manager_id' => $manager->id], $this->admin);
        $position = app(JobPositionService::class)->create(['code' => 'OP', 'name' => 'Operator', 'department_id' => $department->id], $this->admin);
        $this->assertSame($department->id, $position->department->id);
        $manager->delete();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'manager_id' => $manager->id]);
        $this->assertSame($manager->id, $department->fresh()->manager->id);
        $this->assertDatabaseHas('activity_logs', ['event' => 'Department.created']);
    }

    public function test_department_duplicate_code_and_name_rejected(): void
    {
        Department::factory()->create(['code' => 'PROD', 'name' => 'Production']);
        $this->expectException(ValidationException::class);
        app(DepartmentService::class)->create(['code' => 'PROD', 'name' => 'Production'], $this->admin);
    }

    public function test_configuration_authorization(): void
    {
        $this->expectException(AuthorizationException::class);
        app(DepartmentService::class)->create(['code' => 'X', 'name' => 'Unknown'], Staff::factory()->create());
    }

    public function test_employment_preserves_roles_and_redacts_sensitive_fields(): void
    {
        $staff = Staff::factory()->create();
        $staff->assignRole('Manager');
        $position = JobPosition::factory()->create();
        $data = ['department_id' => $position->department_id, 'job_position_id' => $position->id, 'employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01', 'basic_salary' => '150000.00', 'bank_account_number' => '1234567890'];
        $detail = app(StaffEmploymentService::class)->save($staff, $data, $this->admin);
        $this->assertSame(EmploymentType::FULL_TIME, $detail->employment_type);
        $this->assertSame('150000.00', $detail->basic_salary);
        $this->assertTrue($staff->fresh()->hasRole('Manager'));
        $viewer = Staff::factory()->create();
        $viewer->givePermissionTo('view staff employment details');
        $visible = app(StaffEmploymentService::class)->forStaff($staff->fresh(), $viewer);
        $this->assertArrayNotHasKey('basic_salary', $visible);
        $this->assertArrayNotHasKey('bank_account_number', $visible);
        $this->assertStringNotContainsString('1234567890', ActivityLog::all()->toJson());
        $this->assertStringNotContainsString('150000', ActivityLog::all()->toJson());
        $this->assertArrayNotHasKey('basic_salary', $detail->toArray());
        $this->assertSame('150000.00', app(StaffEmploymentService::class)->forStaff($staff->fresh(), $this->admin)['basic_salary']);
    }

    public function test_mismatched_department_position_rejected(): void
    {
        $position = JobPosition::factory()->create();
        $this->expectException(ValidationException::class);
        app(StaffEmploymentService::class)->save(Staff::factory()->create(), ['department_id' => Department::factory()->create()->id, 'job_position_id' => $position->id, 'employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01'], $this->admin);
    }

    public function test_inactive_position_rejected(): void
    {
        $position = JobPosition::factory()->create(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(StaffEmploymentService::class)->save(Staff::factory()->create(), ['department_id' => $position->department_id, 'job_position_id' => $position->id, 'employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01'], $this->admin);
    }

    public function test_salary_mutation_requires_separate_permission(): void
    {
        $actor = Staff::factory()->create();
        $actor->givePermissionTo('update staff employment details');
        $this->expectException(AuthorizationException::class);
        app(StaffEmploymentService::class)->save(Staff::factory()->create(), ['employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01', 'basic_salary' => '100'], $actor);
    }

    public function test_termination_disables_account_transactionally(): void
    {
        $staff = Staff::factory()->create();
        app(StaffEmploymentService::class)->save($staff, ['employment_type' => 'CONTRACT', 'employment_date' => '2026-01-01', 'termination_date' => '2026-09-01'], $this->admin);
        $this->assertSame(StaffStatus::TERMINATED, $staff->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.status_changed']);
    }

    public function test_repeated_profile_save_keeps_one_record(): void
    {
        $staff = Staff::factory()->create();
        $data = ['employment_type' => 'CASUAL', 'employment_date' => '2026-01-01'];
        app(StaffEmploymentService::class)->save($staff, $data, $this->admin);
        app(StaffEmploymentService::class)->save($staff,$data,$this->admin);
        $this->assertDatabaseCount('staff_employment_details',1);
    }
}
