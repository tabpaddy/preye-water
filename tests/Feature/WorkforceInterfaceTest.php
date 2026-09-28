<?php

namespace Tests\Feature;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Staff\Pages\StaffEmployment;
use App\Filament\Resources\StaffAttendances\Pages\CreateStaffAttendance;
use App\Models\Staff;
use App\Models\StaffEmploymentDetail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkforceInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $this->admin = Staff::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_all_workforce_lists_render_for_authorized_staff(): void
    {
        $this->actingAs($this->admin, 'staff');
        foreach (['departments', 'job-positions', 'work-shifts', 'staff-shift-assignments', 'staff-attendances', 'leave-types', 'staff-leave-requests', 'attendance-adjustments'] as $slug) {
            $this->get('/staff/'.$slug)->assertOk();
        }
    }

    public function test_department_create_and_permissions(): void
    {
        $this->actingAs($this->admin, 'staff');
        Livewire::test(CreateDepartment::class)->fillForm(['code' => 'PROD', 'name' => 'Production', 'is_active' => true])->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('departments', ['code' => 'PROD']);
        $this->flushSession();
        $this->actingAs(Staff::factory()->create(), 'staff')->get('/staff/departments')->assertForbidden();
    }

    public function test_employment_page_does_not_hydrate_salary_without_permission(): void
    {
        $staff = Staff::factory()->create();
        StaffEmploymentDetail::factory()->create(['staff_id' => $staff->id, 'basic_salary' => '123456.78', 'bank_account_number' => '9876543210']);
        $viewer = Staff::factory()->create();
        $viewer->givePermissionTo(['view staff', 'view staff employment details', 'update staff employment details']);
        $this->actingAs($viewer, 'staff');
        $page = Livewire::test(StaffEmployment::class, ['record' => $staff->uuid]);
        $page->assertDontSee('123456.78')->assertDontSee('9876543210');
        $this->assertArrayNotHasKey('basic_salary', $page->get('data'));
        $page->fillForm(['employment_type' => 'PART_TIME', 'employment_date' => '2026-01-01'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('123456.78', $staff->fresh()->employmentDetail->basic_salary);
    }

    public function test_authorized_employment_page_updates_sensitive_fields(): void
    {
        $staff = Staff::factory()->create();
        $this->actingAs($this->admin, 'staff');
        Livewire::test(StaffEmployment::class, ['record' => $staff->uuid])->fillForm(['employment_type' => 'FULL_TIME', 'employment_date' => '2026-01-01', 'basic_salary' => '200000.00', 'bank_account_number' => '1234567890'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('200000.00', $staff->fresh()->employmentDetail->basic_salary);
    }

    public function test_manager_with_granted_permission_can_record_attendance(): void
    {
        $manager = Staff::factory()->create();
        $manager->assignRole('Manager');
        $manager->givePermissionTo('record attendance');
        $this->actingAs($manager, 'staff');
        Livewire::test(CreateStaffAttendance::class)->fillForm(['staff_id' => $manager->id, 'attendance_date' => '2026-09-28', 'status' => 'PRESENT', 'clock_in_at' => '2026-09-28T08:00', 'clock_out_at' => '2026-09-28T16:00'])->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('staff_attendance', ['staff_id' => $manager->id, 'worked_minutes' => 480]);
    }
}
