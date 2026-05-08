<?php

namespace Tests\Unit\Services;

use App\Enums\AccountTypes;
use App\Jobs\AzureApi\DeprovisionAzureJob;
use App\Jobs\AzureApi\ProvisionAzureJob;
use App\Models\Account;
use App\Models\Instance;
use App\Models\UniversityMember;
use App\Services\SisApiService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SisApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('custom.azure_tenant_id', 'tenant-id');
        Config::set('custom.azure_oauth2_scope', 'scope');
        Config::set('custom.azure_sis_client_id', 'client-id');
        Config::set('custom.azure_client_assertion_type', 'assertion-type');
        Cache::flush();
        Cache::put('jwt_token', 'jwt-token', 3600);
        Cache::put('access_token', 'access-token', 3600);
    }

    public function test_get_all_employees_returns_collection_on_success(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://sis.example.test/*' => Http::response([
                ['asUporabnik' => 'one@example.test'],
            ], 200),
        ]);

        $universityMember = UniversityMember::factory()->create([
            'sis_base_url' => 'https://sis.example.test',
        ]);

        $employees = (new SisApiService())->getAllEmployees($universityMember);

        $this->assertCount(1, $employees);
    }

    public function test_get_all_employees_returns_empty_collection_on_failure(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://sis.example.test/*' => Http::response([], 500),
        ]);

        $universityMember = UniversityMember::factory()->create([
            'sis_base_url' => 'https://sis.example.test',
        ]);

        $this->assertTrue((new SisApiService())->getAllEmployees($universityMember)->isEmpty());
    }

    public function test_get_all_students_merges_multiple_years_without_duplicates(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://sis.example.test/student/2024-2025/*' => Http::response([
                ['asUporabnik' => 'one@example.test'],
                ['asUporabnik' => 'two@example.test'],
            ], 200),
            'https://sis.example.test/student/2023-2024/*' => Http::response([
                ['asUporabnik' => 'two@example.test'],
                ['asUporabnik' => 'three@example.test'],
            ], 200),
        ]);

        $universityMember = UniversityMember::factory()->create([
            'sis_base_url' => 'https://sis.example.test',
            'sis_current_year' => '2024-2025',
            'sis_student_years' => 2,
        ]);

        $students = (new SisApiService())->getAllStudents($universityMember);

        $this->assertSame(
            ['one@example.test', 'two@example.test', 'three@example.test'],
            $students->pluck('asUporabnik')->values()->all()
        );
    }

    public function test_update_account_membership_updates_types_and_schedules_jobs(): void
    {
        Bus::fake();
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $universityMember = UniversityMember::factory()->create();
        Instance::factory()->for($universityMember)->create();

        $keep = Account::factory()->create(['username' => 'keep@example.test']);
        $attach = Account::factory()->create(['username' => 'attach@example.test']);
        $detach = Account::factory()->create(['username' => 'detach@example.test', 'type' => AccountTypes::Student->value]);

        $universityMember->accounts()->attach($keep->id, ['app_role_assignment_id' => null]);
        $universityMember->accounts()->attach($detach->id, ['app_role_assignment_id' => 'existing-assignment']);

        $sisUsers = collect([
            ['asUporabnik' => 'keep@example.test'],
            ['asUporabnik' => 'attach@example.test'],
            ['asUporabnik' => 'missing@example.test'],
        ]);

        (new SisApiService())->updateAccountMembershipAndType($sisUsers, $universityMember, AccountTypes::Student->value);

        $this->assertDatabaseHas('accounts', ['id' => $keep->id, 'type' => AccountTypes::Student->value]);
        $this->assertDatabaseHas('accounts', ['id' => $attach->id, 'type' => AccountTypes::Student->value]);

        Bus::assertDispatched(ProvisionAzureJob::class, 2);
        Bus::assertDispatched(DeprovisionAzureJob::class, 1);
        Log::shouldHaveReceived('info')->atLeast()->once();
    }
}
