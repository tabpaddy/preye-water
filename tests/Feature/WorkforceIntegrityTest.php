<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffEmploymentDetail;
use App\Models\StaffLeaveRequest;
use App\Models\WorkShift;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkforceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function uniqueRules(): array
    {
        return [['departments', 'code'], ['departments', 'name'], ['job_positions', 'code'], ['work_shifts', 'code'], ['leave_types', 'code'], ['staff_employment_details', 'staff_id'], ['staff_attendance', 'staff_id'], ['staff_leave_requests', 'request_number']];
    }

    #[DataProvider('uniqueRules')]
    public function test_database_uniqueness(string $table, string $field): void
    {
        $models = ['departments' => Department::class, 'job_positions' => JobPosition::class, 'work_shifts' => WorkShift::class, 'leave_types' => LeaveType::class, 'staff_employment_details' => StaffEmploymentDetail::class, 'staff_attendance' => StaffAttendance::class, 'staff_leave_requests' => StaffLeaveRequest::class];
        $model = $models[$table]::factory()->create();
        $data = $model->getAttributes();
        unset($data['id']);
        if (isset($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }
        if ($table === 'departments') {
            $data[$field === 'code' ? 'name' : 'code'] = 'Different';
        }
        $this->expectException(QueryException::class);
        DB::table($table)->insert($data);
    }

    public function test_historical_attendance_prevents_hard_deletion_of_staff(): void
    {
        $record = StaffAttendance::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('staff')->where('id', $record->staff_id)->delete();
    }

    public function test_manager_fk_nulls_without_deleting_department(): void
    {
        $manager = Staff::factory()->create();
        $department = Department::factory()->create(['manager_id' => $manager->id]);
        $manager->forceDelete();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'manager_id' => null]);
    }

    public function test_role_seeding_keeps_custom_permissions_and_avoids_salary_grants(): void
    {
        $this->seed();
        $manager = Role::findByName('Manager', 'staff');
        $manager->givePermissionTo('record attendance');
        $this->seed();
        $this->assertTrue($manager->fresh()->hasPermissionTo('record attendance'));
        $this->assertFalse($manager->fresh()->hasPermissionTo('view staff salaries'));
        $this->assertDatabaseCount('number_sequences', 8);
        $this->assertDatabaseCount('permissions', 89);
    }
}
