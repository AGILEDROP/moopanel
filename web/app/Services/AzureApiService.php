<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Instance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureApiService
{
    protected const DOMAIN = 'https://graph.microsoft.com';

    private ?string $accessToken = null;

    public function __construct(private ?Instance $instance)
    {
        $accessToken = Cache::get('azure_ad_access_token');

        if (! $accessToken) {
            $response = Http::asForm()->post('https://login.microsoftonline.com/'.config('custom.azure_tenant_id').'/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('custom.azure_client_id'),
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => config('custom.azure_client_secret'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Cache::put('azure_ad_access_token', $data['access_token'], ((int) $data['expires_in'] - 10));
                $accessToken = $data['access_token'];
            } else {
                Log::error('Error getting access token from Azure, Status Code: '.$response->status());
                if ($response->status() === 404) {
                    Log::error('Extra info for 404 code: '.'https://login.microsoftonline.com/'.config('custom.azure_tenant_id').'/oauth2/v2.0/token');
                }
            }
        }

        $this->accessToken = $accessToken;
    }

    public function assignUserToUniversityMemberApp(Instance $instance, Account $account): string|bool
    {
        if (is_null($instance->app_info)) {
            Log::error(__METHOD__." - Error assigning user to Azure AD app - missing app_info for instance {$instance->name} with id {$instance->id}.");

            return false;
        }

        $appInfo = json_decode($instance->app_info, true);
        $appID = $appInfo['id'];
        $appRoleId = null;

        foreach ($appInfo['appRoles'] as $roles) {
            if ($roles['displayName'] === 'User') {
                $appRoleId = $roles['id'];
                break;
            }
        }

        if (empty($appRoleId)) {
            Log::error(__METHOD__." - Error assigning user to Azure AD app - missing appRoleId for instance {$instance->name} with id {$instance->id}.");

            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->post(self::DOMAIN."/v1.0/servicePrincipals/$appID/appRoleAssignedTo", [
                'principalId' => $account->azure_id,
                'resourceId' => $appID,
                'appRoleId' => $appRoleId,
            ]);

        $json = $response->json();

        if (! $response->successful()) {
            if (
                is_array($json)
                && ! empty($json['error']['message'])
                && $json['error']['message'] === 'Permission being assigned already exists on the object'
            ) {
                return true;
            }

            Log::error('Error posting addUserToLicense from Azure, Status Code: '.$response->status()
                .' '.self::DOMAIN."/v1.0/servicePrincipals/$appID/appRoleAssignedTo"
                .$appRoleId
                .$response->body());

            return false;
        }

        return $json['id'];
    }

    public function unassignUserfromUniversityMemberApp(Instance $instance, Account $account): bool
    {
        if (is_null($instance->app_info)) {
            Log::error(__METHOD__." - Error assigning user to Azure AD app - missing app_info for instance {$instance->name} with id {$instance->id}.");

            return false;
        }

        $appInfo = json_decode($instance->app_info, true);
        $appID = $appInfo['id'];

        $universityMember = $instance->universityMember;

        if (is_null($universityMember)) {
            Log::error(__METHOD__." - Error assigning user to Azure AD app - missing university member for instance {$instance->name} with id {$instance->id}.");

            return false;
        }

        $appRoleAssignmentId = $universityMember->accounts()->where('id', $account->id)->first()?->pivot->app_role_assignment_id;

        if (empty($appRoleAssignmentId)) {
            Log::error('Error calling unassignUserfromUniversityMemberApp - missing app_role_assignment_id.');

            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->delete(self::DOMAIN."/v1.0/servicePrincipals/$appID/appRoleAssignedTo/$appRoleAssignmentId");

        if (! $response->successful()) {
            Log::error('Error calling removeUserFromLicense from Azure, Status Code: '.$response->status());

            return false;
        }

        return true;
    }

    public function userInfo(string $userPrincipalName): array
    {
        $response = Http::withToken($this->accessToken)
            ->get(self::DOMAIN."/beta/users/$userPrincipalName");

        if (! $response->successful()) {
            Log::error('Error getting userInfo from Azure, Status Code: '.$response->status());

            return [];
        }

        return $response->json();
    }

    public function userRoleId(string $userPrincipalName): ?string
    {
        $response = Http::withToken($this->accessToken)
            ->get(self::DOMAIN."/beta/users/$userPrincipalName/appRoleAssignments");

        if (! $response->successful()) {
            Log::error('Error getting userRole from Azure, Status Code: '.$response->status());

            return null;
        }

        $values = $response->json('value');
        if (! is_array($values)) {
            Log::error('Error getting userRole from Azure, Status Code: '.$response->status());

            return null;
        }

        $values = collect($values);
        $appKey = $values->pluck('resourceId')->search(config('custom.azure_app_resource_id'));
        $values = $values->get($appKey);

        return $values['appRoleId'] ?? Role::User->value;
    }

    public function applicationInfo(string $appUuid): ?array
    {
        $response = Http::withToken($this->accessToken)
            ->get(self::DOMAIN."/v1.0/servicePrincipals?\$filter=appId eq '$appUuid'");

        if (! $response->successful()) {
            Log::error('Error getting applicationInfo from Azure, Status Code: '.$response->status());

            return null;
        }

        $values = $response->json('value');
        if (! is_array($values)) {
            Log::error('Error getting applicationInfo from Azure, Status Code: '.$response->status());

            return null;
        }

        return $values[0] ?? $values;
    }
}
