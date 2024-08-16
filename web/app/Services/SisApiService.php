<?php

namespace App\Services;

use App\Enums\AccountTypes;
use App\Jobs\AzureApi\ProvisionAzureJob;
use App\Models\Account;
use App\Models\UniversityMember;
use Firebase\JWT\JWT;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SisApiService
{
    const SIS_UPN_FIELD = 'asUporabnik';

    const ACCOUNT_UPN_FIELD = 'username';

    private string $accessToken;

    private string $jwtToken;

    public function __construct()
    {
        $jwtToken = Cache::get('jwt_token');

        if (! $jwtToken) {
            $jwtToken = $this->getSisJwtToken();
            Cache::put('jwt_token', $jwtToken, (int) config('custom.sis_api_jwt_expiration'));
        }

        $this->jwtToken = $jwtToken;

        $accessToken = Cache::get('access_token');

        if (! $accessToken) {
            $accessToken = $this->getAccessToken($this->jwtToken);
        }

        $this->accessToken = $accessToken;
    }

    public function getAllEmployees(UniversityMember $universityMember): Collection
    {
        $response = $this->getEndpointResponse($universityMember->sis_base_url, 'zaposleni', $universityMember->sis_current_year, $universityMember->code);
        if (! $response->ok()) {
            Log::error("{$universityMember->name} (code: {$universityMember->code}) SIS endpoint does not return employees data with response code {$response->status()} and body {$response->body()}");

            return collect();
        }

        $employees = $response->collect();

        Log::info("{$universityMember->name} (code: {$universityMember->code}) SIS endpoint returned ".count($employees).' employees.');

        return $employees;
    }

    public function getAllStudents(UniversityMember $universityMember): Collection
    {
        $data = collect();
        $years = $this->getStudentSchoolYears($universityMember->sis_current_year, $universityMember->sis_student_years);

        foreach ($years as $year) {
            $response = $this->getEndpointResponse($universityMember->sis_base_url, 'student', $year, $universityMember->code);
            if (! $response->ok()) {
                Log::error("{$universityMember->name} (code: {$universityMember->code}, year: {$year}) SIS endpoint does not return students data with response code {$response->status()} and body {$response->body()}");

                return $data;
            }

            if (isset($previousYear) && isset($data)) {
                $newData = $response->collect();
                $newData = $newData->whereNotIn(self::SIS_UPN_FIELD, $data->pluck(self::SIS_UPN_FIELD));
                $data = $data->merge($newData);
            } else {
                $data = $response->collect();
            }

            $previousYear = $year;
        }

        Log::info("{$universityMember->name} (code: {$universityMember->code}) SIS endpoint returned ".count($data).' students.');

        return $data;
    }

    public function updateAccountMembershipAndType(Collection $sisUsers, UniversityMember $universityMember, string $type): void
    {
        $typeName = Str::plural(toLower(AccountTypes::tryFrom($type)->name)) ?? '';
        if ($sisUsers->count() > 0) {
            // Get all account usernames.
            $accountsUpns = Account::pluck(self::ACCOUNT_UPN_FIELD);

            // Log sisUsers that don't have defined Account model.
            $sisUsersWithoutAccount = $sisUsers->whereNotIn(self::SIS_UPN_FIELD, $accountsUpns);
            if ($sisUsersWithoutAccount->count() > 0) {
                Log::info("{$universityMember->name} endpoint returned ".$sisUsersWithoutAccount->count()." {$typeName} data that don't have a defined Account model.");

                //Log::warning('Not all ' . $typeName . ' data returned from the SIS endpoint have a defined Account model!');
                //Log::debug('Missing ' . $typeName . ' data: ' . print_r($sisUsersWithoutAccount, true));
            }

            // Assign university member to existing account & update type value!
            $sisUsersWithAccount = $sisUsers->whereIn(self::SIS_UPN_FIELD, $accountsUpns);

            $accountItems = Account::whereIn(self::ACCOUNT_UPN_FIELD, $sisUsersWithAccount->pluck(self::SIS_UPN_FIELD)->toArray())
                ->get();

            // Account IDs of users that arrived from SIS and are already in the database.
            $accounts = $accountItems
                ->pluck('id')
                ->toArray();

            // TODO: uncomment to allow account assignment to Azure AD app and mooPanel via jobs
            //$this->scheduleToAssign($accountItems, $universityMember, $type);

            // Assign adittional account to current university member
            // Syncwithoutdetaching is used because we want to keep existing accounts linked
            // not needed - form v1.0.0 - this is now done in job
            $universityMember->accounts()->syncWithoutDetaching($accounts);

            Account::whereIn('id', $accounts)
                ->update([
                    'type' => $type,
                ]);
            // Handle also detaching -> Sync can't be used because this function is used on two different endpoints
            // (one for students and one for employees).
            $accountsThatShouldBeDetached = $universityMember
                ->accounts()
                ->where('type', $type)
                ->whereNotIn('id', $accounts)
                ->pluck('id');
            if (count($accountsThatShouldBeDetached) > 0) {
                // TODO:
                // first remove users from Azure AD app via job an then detach, so that we know which app_role_assignment_id to submit on deletion request to Azure
                // maybe also detach if inside job
                // Remove user from Azure AD app via job?
                $universityMember->accounts()->detach($accountsThatShouldBeDetached);
            }
        } else {
            Log::warning("{$universityMember->name} endpoint returned an empty {$typeName} collection.");
        }
    }

    private function getEndpointResponse(string $baseSisApiUrl, string $endpoint, string $schoolYear, string $universityMemberCode): PromiseInterface|Response
    {
        Log::warning("{$baseSisApiUrl}/{$endpoint}}/{$schoolYear}/{$universityMemberCode}");

        return Http::withToken($this->accessToken)
            ->get("{$baseSisApiUrl}/{$endpoint}/{$schoolYear}/{$universityMemberCode}");
    }

    private function getStudentSchoolYears(?string $currentYear = null, int $numYears = 1): array
    {
        $years = [];
        for ($numYear = 0; $numYear < $numYears; $numYear++) {
            if (! isset($year)) {
                $year = $this->getSchoolCurrentSchoolYear($currentYear);
            } else {
                $explodedYear = explode('-', $year);
                $year = Carbon::create($explodedYear[0])->subYear()->format('Y').'-'.Carbon::create($explodedYear[1])->subYear()->format('Y');
            }

            $years[] = $year;
        }

        return $years;
    }

    private function getSchoolCurrentSchoolYear(?string $currentYear = null): string
    {
        if ($currentYear) {
            $year = $currentYear;
        } else {
            $baseYear = Carbon::create('Y', 'm');
            if ($baseYear->format('Y-10') > $baseYear->format('Y-m')) {
                $year = Carbon::create($baseYear->format('Y'))->subYear()->format('Y').'-'.$baseYear->format('Y');
            } else {
                $year = $baseYear->format('Y').'-'.Carbon::create($baseYear->format('Y'))->addYear()->format('Y');
            }
        }

        return $year;
    }

    private function getSisJwtToken(): string
    {
        $privateKey = config('custom.sis_api_jwt_private_key');

        $headers = [
            'typ' => 'JWT',
            'alg' => config('custom.sis_api_jwt_alg'),
            'x5t' => config('custom.sis_api_jwt_x5t'),
        ];

        $payload = [
            'iss' => config('custom.sis_api_jwt_iss'),
            'sub' => config('custom.sis_api_jwt_sub'),
            'aud' => config('custom.sis_api_jwt_aud'),
            'iat' => time(), // Time when JWT was issued.
            'nbf' => time(), // Time when JWT was issued.
            'exp' => time() + ((int) config('custom.sis_api_jwt_expiration')), // Expiration time (1 hour from now)
            'jti' => uniqid(), // Unique identifier for the token
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256', null, $headers);

        return $jwt;
    }

    private function getAccessToken(string $jwtToken): string
    {
        $response = Http::asForm()->post('https://login.microsoftonline.com/'.config('custom.azure_tenant_id').'/oauth2/v2.0/token', [
            'grant_type' => 'client_credentials',
            'tenant' => config('custom.azure_tenant_id'),
            'scope' => config('custom.azure_oauth2_scope'),
            'client_id' => config('custom.azure_sis_client_id'),
            'client_assertion_type' => config('custom.azure_client_assertion_type'),
            'client_assertion' => $jwtToken,
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

            throw new \Exception('Error getting access token from Azure');
        }

        return $accessToken;
    }

    /**
     * Trigger account assignment to Azure AD app and mooPanel for each account that is not already assigned
     * to Azure AD app(nullable app_role_assignment_id)
     *
     * Trigger assignment also if account is assigned to university-member in moopanel but not in Azure AD app
     */
    private function scheduleToAssign(Collection $accounts, UniversityMember $universityMember, string $type): void
    {
        foreach ($accounts as $account) {
            $universityMemberAccount = $universityMember->accounts->where('id', $account->id)->first();

            // Find if current account is already assigned to university member
            if ($universityMemberAccount) {
                $appRoleAssignmentId = $universityMemberAccount->pivot->app_role_assignment_id;

                // If account is already assigned to university member, then skip it
                if ($appRoleAssignmentId) {
                    Log::info("Account {$account->id} is already assigned to university member {$universityMember->code} with app_role_assignment_id {$appRoleAssignmentId}.");

                    continue;
                }

            }

            // Put account into process of assignint it into Azure AD app and inside mooPanel
            ProvisionAzureJob::dispatch($universityMember, $account);
        }

        Log::info("Accounts of type {$type} scheduled for assignment to Azure AD app and mooPanel via SIS api service.");
    }
}
