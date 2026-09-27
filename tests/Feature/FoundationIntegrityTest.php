<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Staff;
use App\Models\SystemSetting;
use App\Services\ActivityLogService;
use App\Services\StaffService;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FoundationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function uniqueFields(): array
    {
        return [['people', 'uuid'], ['staff', 'uuid'], ['staff', 'person_id'], ['staff', 'staff_number'], ['business_settings', 'business_id'], ['system_settings', 'group'], ['number_sequences', 'key']];
    }

    #[DataProvider('uniqueFields')]
    public function test_database_enforces_unique_constraints(string $table, string $field): void
    {
        $this->seed();
        $staff = Staff::factory()->create();
        SystemSetting::create(['group' => 'general', 'key' => 'flag', 'type' => 'BOOLEAN', 'value' => '1']);
        $row = (array) DB::table($table)->first();
        unset($row['id']);
        if (isset($row['uuid']) && $field !== 'uuid') {
            $row['uuid'] = (string) Str::uuid();
        }
        if ($table === 'staff' && $field !== 'person_id') {
            $row['person_id'] = Person::factory()->create()->id;
        }
        if ($table === 'staff' && $field !== 'staff_number') {
            $row['staff_number'] = 'UNIQUE-SECOND';
        }
        $this->expectException(QueryException::class);
        DB::table($table)->insert($row);
    }

    public function test_people_can_share_contact_details(): void
    {
        Person::factory()->count(2)->create(['email' => 'shared@example.com', 'phone' => '08012345678']);
        $this->assertDatabaseCount('people', 2);
    }

    public function test_audit_view_is_read_only_and_secrets_are_removed(): void
    {
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('staff'));
        $admin = Staff::factory()->create();
        $admin->assignRole('Super Admin');
        $log = app(ActivityLogService::class)->record('test', $admin, $admin, 'Audit test', ['password' => 'hidden'], ['nested' => ['api_key' => 'hidden', 'safe' => 'visible']]);
        $this->assertStringNotContainsString('hidden', $log->toJson());
        $this->assertStringContainsString('visible', $log->toJson());
        $this->assertFalse(Gate::forUser($admin)->allows('update', $log));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $log));
        $this->actingAs($admin, 'staff')->get('/staff/activity-logs')->assertOk();
        $this->get('/staff/activity-logs/'.$log->uuid)->assertOk();
        $this->get('/staff/activity-logs/'.$log->uuid.'/edit')->assertNotFound();
        $this->flushSession();
        $this->actingAs(Staff::factory()->create(), 'staff')->get('/staff/activity-logs')->assertForbidden();
    }

    public function test_staff_delete_preserves_person_and_audit(): void
    {
        $this->seed();
        $admin = Staff::factory()->create();
        $admin->assignRole('Super Admin');
        $staff = Staff::factory()->create();
        app(StaffService::class)->delete($staff, $admin);
        $this->assertSoftDeleted('staff', ['id' => $staff->id]);
        $this->assertDatabaseHas('people', ['id' => $staff->person_id, 'deleted_at' => null]);
        $this->assertDatabaseHas('activity_logs', ['event' => 'staff.deleted', 'subject_id' => $staff->id]);
    }
}
