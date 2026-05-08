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

    public function test_constructor_fetches_access_token_when_only_jwt_is_cached(): void
    {
        Cache::forget('access_token');
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fresh-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        new SisApiService();

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://login.microsoftonline.com/tenant-id/oauth2/v2.0/token')
            && $request['grant_type'] === 'client_credentials'
            && $request['tenant'] === 'tenant-id'
            && $request['client_id'] === 'client-id'
            && $request['client_assertion'] === 'jwt-token');

        $this->assertSame('fresh-access-token', Cache::get('access_token'));
    }

    public function test_constructor_throws_exception_when_token_endpoint_fails(): void
    {
        Cache::forget('access_token');
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response('not found', 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error getting access token from Azure');

        new SisApiService();
    }

    public function test_get_all_students_stops_after_first_failed_year(): void
    {
        Http::fake([
            'https://sis.example.test/student/2024-2025/*' => Http::response([
                ['asUporabnik' => 'one@example.test'],
            ], 200),
            'https://sis.example.test/student/2023-2024/*' => Http::response([], 500),
        ]);

        $universityMember = UniversityMember::factory()->create([
            'sis_base_url' => 'https://sis.example.test',
            'sis_current_year' => '2024-2025',
            'sis_student_years' => 2,
        ]);

        $students = (new SisApiService())->getAllStudents($universityMember);

        $this->assertSame(['one@example.test'], $students->pluck('asUporabnik')->all());
    }

    public function test_update_account_membership_logs_warning_for_empty_collection(): void
    {
        Log::spy();

        $universityMember = UniversityMember::factory()->create();

        (new SisApiService())->updateAccountMembershipAndType(
            collect(),
            $universityMember,
            AccountTypes::Student->value
        );

        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    public function test_update_account_membership_skips_provisioning_when_university_member_has_no_instance(): void
    {
        Bus::fake();
        Log::spy();

        $universityMember = UniversityMember::factory()->create();
        $account = Account::factory()->create(['username' => 'lonely@example.test']);

        (new SisApiService())->updateAccountMembershipAndType(
            collect([['asUporabnik' => 'lonely@example.test']]),
            $universityMember,
            AccountTypes::Student->value
        );

        Bus::assertNotDispatched(ProvisionAzureJob::class);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'type' => AccountTypes::Student->value]);
    }
}
