<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AzureApi\ProvisionAzureJob;
use App\Models\Account;
use App\Models\Instance;
use App\Models\UniversityMember;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProvisionAzureJobTest extends TestCase
{
    private const PRINCIPAL_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('custom.azure_tenant_id', 'tenant-id');
        Config::set('custom.azure_client_id', 'client-id');
        Config::set('custom.azure_client_secret', 'client-secret');
    }

    public function test_job_logs_and_exits_when_university_member_has_no_instance(): void
    {
        Log::spy();

        $job = new ProvisionAzureJob(UniversityMember::factory()->create(), Account::factory()->create(), 'student');
        $job->handle();

        Log::shouldHaveReceived('error')->once();
    }

    public function test_job_updates_existing_pivot_with_assignment_id(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response(['id' => 'assignment-id'], 201),
        ]);

        $universityMember = UniversityMember::factory()->create();
        $instance = Instance::factory()->for($universityMember)->withAppInfo('service-principal-id', 'role-id')->create();
        $account = Account::factory()->create(['azure_id' => self::PRINCIPAL_ID]);
        $universityMember->accounts()->attach($account->id, ['app_role_assignment_id' => null]);

        (new ProvisionAzureJob($universityMember, $account, 'student'))->handle();

        $this->assertSame(
            'assignment-id',
            $universityMember->accounts()->first()->pivot->app_role_assignment_id
        );
    }

    public function test_job_attaches_account_when_membership_is_missing(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response(['id' => 'assignment-id'], 201),
        ]);

        $universityMember = UniversityMember::factory()->create();
        Instance::factory()->for($universityMember)->withAppInfo('service-principal-id', 'role-id')->create();
        $account = Account::factory()->create(['azure_id' => self::PRINCIPAL_ID]);

        (new ProvisionAzureJob($universityMember, $account, 'student'))->handle();

        $this->assertDatabaseHas('university_memberables', [
            'university_member_id' => $universityMember->id,
            'memberable_id' => $account->id,
            'memberable_type' => Account::class,
            'app_role_assignment_id' => 'assignment-id',
        ]);
    }
}
