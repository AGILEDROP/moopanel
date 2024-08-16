<?php

namespace App\Services;

use App\Enums\LicenseType;
use App\Enums\Role;
use App\Models\Account;
use App\Models\License;
use App\Models\Scopes\InstanceScope;
use App\Models\UniversityMember;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureApiService
{
    protected const DOMAIN = 'https://graph.microsoft.com';

    private ?string $accessToken = null;

    public function __construct()
    {
        /* $accessToken = Cache::get('access_token');
        if (! $accessToken) {
            $response = Http::asForm()->post('https://login.microsoftonline.com/'.config('custom.azure_tenant_id').'/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('custom.azure_client_id'),
                'scope' => 'https://graph.microsoft.com/.default',
                'client_secret' => config('custom.azure_client_secret'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Cache::put('access_token', $data['access_token'], ((int) $data['expires_in'] - 10));
                $accessToken = $data['access_token'];
            } else {
                Log::error('Error getting access token from Azure, Status Code: '.$response->status());
                if ($response->status() === 404) {
                    Log::error('Extra info for 404 code: '.'https://login.microsoftonline.com/'.config('custom.azure_tenant_id').'/oauth2/v2.0/token');
                }
            }
        }

        $this->accessToken = $accessToken; */
        $this->accessToken = 'Bearer dummy_token';
    }

    public function assignUserToUniversityMemberApp(UniversityMember $universityMember, Account $account): string|bool
    {
        $instance = $universityMember->instances()->withoutGlobalScope(InstanceScope::class)->get()->first();

        if (! $instance) {
            Log::error("Error calling assignUserToUniversityMemberApp - missing instance for university member {$universityMember->code}.");

            return false;
        }

        $appID = $instance->azure_app_id;
        $appRoleId = null;

        Http::fake([
            'github.com/*' => Http::response([
                'id' => 'LOLO-frewf-ewf-ewf-ewf',
                'error' => [
                    'message' => 'Backup restore request successfuly accepted!',
                ],
            ], 200),
        ]);

        // Then, make an actual request, which will be intercepted by the fake.
        // For demonstration, let's assume you're making a GET request to "https://github.com/api/data"
        $response = Http::get('https://github.com/api/data');

        return $response->json()['id'];

        // TBD: check this
        /* if ($license->type === LicenseType::Bookwidget) {
            $appRoleId = '00000000-0000-0000-0000-000000000000';
        } else {
            foreach ($license->app_info['appRoles'] as $roles) {
                if ($roles['displayName'] === 'User') {
                    $appRoleId = $roles['id'];
                    break;
                }
            }
        } */

        /* $response = Http::withToken($this->accessToken)
            ->post(self::DOMAIN . "/v1.0/servicePrincipals/$appID/appRoleAssignedTo", [
                'principalId' => $user->azure_id,
                'resourceId' => $appID,
                'appRoleId' => $appRoleId
            ]);

        $json = $response->json();
        if (!$response->successful()) {
            if (
                is_array($json)
                && !empty($json['error']['message'])
                && $json['error']['message'] === 'Permission being assigned already exists on the object'
            ) {
                return true;
            }

            Log::error('Error posting addUserToLicense from Azure, Status Code: ' . $response->status()
                . ' ' . self::DOMAIN . "/v1.0/servicePrincipals/$appID/appRoleAssignedTo"
                . $appRoleId);

            return false;
        }

        return $json['id']; */
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

    public function removeUserFromLicense(UniversityMember $universityMember, Account $account): bool
    {
        /* $instances = $universityMember->instances;

        foreach ($instances as $instance) {
            $appID = $instance->azure_app_id;
            $appRoleId = null;
        }

        $appID = $license->app_info['id'];
        $appRoleAssignmentId = $user->licenses->where('pivot.license_id', '=', $license->id)->first()->pivot->app_role_assignment_id;

        if (empty($appRoleAssignmentId)) {
            Log::error('Error calling removeUserFromLicense - missing app_role_assignment_id.');

            return false;
        }

        $response = Http::withToken($this->accessToken)
            ->delete(self::DOMAIN . "/v1.0/servicePrincipals/$appID/appRoleAssignedTo/$appRoleAssignmentId");

        if (! $response->successful()) {
            Log::error('Error calling removeUserFromLicense from Azure, Status Code: ' . $response->status());

            return false;
        } */

        return true;
    }

    public function lastAccess(User $user): string|false
    {
        /* $upn = $user->username;
        $licenseDisplayName = $license->name;

        $response = Http::withToken($this->accessToken)
            ->get(self::DOMAIN . '/v1.0/auditLogs/signIns', [
                '$top' => 1,
                '$filter' => "userPrincipalName eq '$upn' and appDisplayName eq '$licenseDisplayName'",
            ]);

        if (! $response->successful()) {
            Log::error('Error getting lastAccess from Azure, Status Code: ' . $response->status());

            return false;
        }

        $values = $response->json('value');
        if (empty($values)) {
            return false;
        }

        $createdDateTime = $values[0]['createdDateTime'];
        $date = Carbon::parse($createdDateTime, 'UTC');
        $date->setTimezone(config('app.timezone'));

        // "2024-01-17 11:58:23"
        return $date->toDateTime()->format('Y-m-d H:i:s'); */

        return true;
    }

    public function listOfUsers(?string $token = null): array|false
    {
        /* $appID = $license->app_info['id'];
        $response = Http::withToken($this->accessToken)
            ->get(self::DOMAIN . "/v1.0/servicePrincipals/$appID/appRoleAssignedTo", [
                '$top' => 500,
                '$skiptoken' => $token,
            ]);

        if (! $response->successful()) {
            Log::error('Error getting listOfUsers from Azure, Status Code: ' . $response->status());

            return false;
        }

        return $response->json(); */
        return false;
    }
}
