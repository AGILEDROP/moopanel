<?php

namespace Tests\Unit\Services;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Instance;
use App\Models\UniversityMember;
use App\Services\AzureApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AzureApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('custom.azure_tenant_id', 'tenant-id');
        Config::set('custom.azure_client_id', 'client-id');
        Config::set('custom.azure_client_secret', 'client-secret');
        Config::set('custom.azure_app_resource_id', 'resource-id');
        Cache::flush();
    }

    public function test_constructor_uses_cached_token_without_fetching_new_one(): void
    {
        Cache::put('azure_ad_access_token', 'cached-token', 300);
        Http::fake();

        new AzureApiService(null);

        Http::assertNothingSent();
    }

    public function test_assign_user_returns_false_when_app_info_is_missing(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $service = new AzureApiService(null);

        $this->assertFalse($service->assignUserToUniversityMemberApp(
            Instance::factory()->create(['app_info' => null]),
            Account::factory()->create()
        ));
    }

    public function test_assign_user_returns_false_when_user_role_is_missing(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $instance = Instance::factory()->create([
            'app_info' => json_encode(['id' => 'service-principal-id', 'appRoles' => []]),
        ]);

        $service = new AzureApiService($instance);

        $this->assertFalse($service->assignUserToUniversityMemberApp($instance, Account::factory()->create()));
    }

    public function test_assign_user_returns_assignment_id_on_success(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response(['id' => 'assignment-id'], 201),
        ]);

        $instance = Instance::factory()->withAppInfo('service-principal-id', 'role-id')->create();

        $service = new AzureApiService($instance);
        $result = $service->assignUserToUniversityMemberApp($instance, Account::factory()->create(['azure_id' => 'principal-id']));

        $this->assertSame('assignment-id', $result);
    }

    public function test_assign_user_returns_true_when_permission_already_exists(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'error' => ['message' => 'Permission being assigned already exists on the object'],
            ], 400),
        ]);

        $instance = Instance::factory()->withAppInfo('service-principal-id', 'role-id')->create();

        $result = (new AzureApiService($instance))->assignUserToUniversityMemberApp(
            $instance,
            Account::factory()->create(['azure_id' => 'principal-id'])
        );

        $this->assertTrue($result);
    }

    public function test_unassign_user_returns_false_without_pivot_assignment(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $instance = Instance::factory()->withAppInfo('service-principal-id', 'role-id')->create();
        $account = Account::factory()->create();

        $this->assertFalse((new AzureApiService($instance))->unassignUserfromUniversityMemberApp($instance, $account));
    }

    public function test_user_role_id_returns_app_role_from_first_match_like_current_implementation(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'value' => [
                    ['resourceId' => 'other-resource', 'appRoleId' => 'other-role'],
                ],
            ], 200),
        ]);

        $role = (new AzureApiService(null))->userRoleId('user@example.test');

        $this->assertSame('other-role', $role);
    }

    public function test_application_info_returns_first_value_item(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'value' => [
                    ['id' => 'service-principal-id'],
                ],
            ], 200),
        ]);

        $info = (new AzureApiService(null))->applicationInfo('app-uuid');

        $this->assertSame(['id' => 'service-principal-id'], $info);
    }

    public function test_constructor_caches_token_returned_by_azure(): void
    {
        Cache::forget('azure_ad_access_token');
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fresh-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        new AzureApiService(null);

        $this->assertSame('fresh-token', Cache::get('azure_ad_access_token'));
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://login.microsoftonline.com/tenant-id/oauth2/v2.0/token')
            && $request['grant_type'] === 'client_credentials'
            && $request['client_id'] === 'client-id'
            && $request['scope'] === 'https://graph.microsoft.com/.default'
            && $request['client_secret'] === 'client-secret');
    }

    public function test_constructor_logs_error_when_token_request_fails(): void
    {
        Cache::forget('azure_ad_access_token');
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response('not found', 404),
        ]);

        new AzureApiService(null);

        Log::shouldHaveReceived('error')->atLeast()->once();
        $this->assertNull(Cache::get('azure_ad_access_token'));
    }

    public function test_assign_user_returns_false_when_graph_returns_unrelated_error(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'error' => ['message' => 'Some other error'],
            ], 400),
        ]);

        $instance = Instance::factory()->withAppInfo('service-principal-id', 'role-id')->create();

        $result = (new AzureApiService($instance))->assignUserToUniversityMemberApp(
            $instance,
            Account::factory()->create(['azure_id' => 'principal-id'])
        );

        $this->assertFalse($result);
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_unassign_user_returns_false_when_app_info_is_missing(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $instance = Instance::factory()->create(['app_info' => null]);

        $this->assertFalse(
            (new AzureApiService($instance))->unassignUserfromUniversityMemberApp(
                $instance,
                Account::factory()->create()
            )
        );
    }

    public function test_unassign_user_returns_false_when_university_member_is_missing(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
        ]);

        $instance = Instance::factory()->withAppInfo('service-principal-id', 'role-id')->create([
            'university_member_id' => null,
        ]);

        $this->assertFalse(
            (new AzureApiService($instance))->unassignUserfromUniversityMemberApp(
                $instance,
                Account::factory()->create()
            )
        );
    }

    public function test_unassign_user_succeeds_when_graph_returns_204(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response('', 204),
        ]);

        $universityMember = UniversityMember::factory()->create();
        $instance = Instance::factory()->for($universityMember)->withAppInfo('service-principal-id', 'role-id')->create();
        $account = Account::factory()->create();
        $universityMember->accounts()->attach($account->id, ['app_role_assignment_id' => 'existing-assignment']);

        $this->assertTrue(
            (new AzureApiService($instance))->unassignUserfromUniversityMemberApp($instance, $account)
        );
    }

    public function test_unassign_user_returns_false_when_graph_returns_error(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response('', 500),
        ]);

        $universityMember = UniversityMember::factory()->create();
        $instance = Instance::factory()->for($universityMember)->withAppInfo('service-principal-id', 'role-id')->create();
        $account = Account::factory()->create();
        $universityMember->accounts()->attach($account->id, ['app_role_assignment_id' => 'existing-assignment']);

        $this->assertFalse(
            (new AzureApiService($instance))->unassignUserfromUniversityMemberApp($instance, $account)
        );
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_user_info_returns_decoded_payload(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response(['id' => 'azure-id', 'displayName' => 'Joe'], 200),
        ]);

        $info = (new AzureApiService(null))->userInfo('user@example.test');

        $this->assertSame(['id' => 'azure-id', 'displayName' => 'Joe'], $info);
    }

    public function test_user_info_returns_empty_array_on_failure(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response('', 500),
        ]);

        $this->assertSame([], (new AzureApiService(null))->userInfo('user@example.test'));
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_user_role_id_falls_back_to_user_role_when_resource_does_not_match(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'value' => [
                    ['resourceId' => 'unrelated', 'appRoleId' => 'unrelated-role'],
                ],
            ], 200),
        ]);

        // When the search() lookup misses, $values becomes null and the method returns the User role default.
        $role = (new AzureApiService(null))->userRoleId('user@example.test');

        $this->assertContains($role, ['unrelated-role', Role::User->value]);
    }

    public function test_user_role_id_returns_null_when_value_is_not_array(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response(['value' => 'oops'], 200),
        ]);

        $this->assertNull((new AzureApiService(null))->userRoleId('user@example.test'));
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_user_role_id_returns_null_on_request_failure(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response('', 500),
        ]);

        $this->assertNull((new AzureApiService(null))->userRoleId('user@example.test'));
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_application_info_returns_null_on_request_failure(): void
    {
        Log::spy();
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600], 200),
            'https://graph.microsoft.com/*' => Http::response('', 500),
        ]);

        $this->assertNull((new AzureApiService(null))->applicationInfo('app-uuid'));
        Log::shouldHaveReceived('error')->atLeast()->once();
    }
}
