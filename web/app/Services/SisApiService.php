<?php

namespace App\Services;

use App\Enums\AccountTypes;
use App\Models\Account;
use App\Models\UniversityMember;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SisApiService
{
    const SIS_UPN_FIELD = 'asUporabnik';

    const ACCOUNT_UPN_FIELD = 'username';

    public function getAllEmployees(string $code, string $universityMemberName = 'University member'): Collection
    {
        $response = Http::withHeader(config('custom.sis_api_key_name'), config('custom.sis_api_key_value'))
            ->get("https://visapi.uni-lj.si/UL_MOODLE2/RESTAdapter/v1/zaposleni/{$this->getSchoolYear()}/{$code}");
        if (! $response->ok()) {
            Log::error("{$universityMemberName} ({$code}) endpoint does not return employees data.");

            return collect();
        }

        return $response->collect();
    }

    public function getAllStudents(string $numYears, string $code, string $universityMemberName = 'University member'): Collection
    {
        $data = collect();
        $years = $this->getSchoolYears($numYears);

        foreach ($years as $year) {
            $response = Http::withHeader(config('custom.sis_api_key_name'), config('custom.sis_api_key_value'))
                ->get("https://visapi.uni-lj.si/UL_MOODLE2/RESTAdapter/v1/student/{$year}/{$code}");
            if (! $response->ok()) {
                Log::error("{$universityMemberName} (code: {$code}, year: {$year}) endpoint does not return employees data.");

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
                Log::warning('Not all '.$typeName.' data returned from the SIS endpoint have a defined Account model!');
                Log::debug('Missing '.$typeName.' data: '.print_r($sisUsersWithoutAccount, true));
            }

            // Assign university member to existing account & update type value!
            $sisUsersWithAccount = $sisUsers->whereIn(self::SIS_UPN_FIELD, $accountsUpns);
            $accounts = Account::where(self::ACCOUNT_UPN_FIELD, $sisUsersWithAccount->pluck(self::SIS_UPN_FIELD)->toArray())
                ->pluck('id')
                ->toArray();
            $universityMember->accounts()->syncWithoutDetaching($accounts);
            Account::whereIn('id', $accounts)
                ->update([
                    'type' => $type,
                ]);
        } else {
            Log::warning("{$universityMember->name} endpoint returned an empty {$typeName} collection.");
        }
    }

    public function getSchoolYears(int $numYears = 1): array
    {
        $years = [];
        for ($numYear = 0; $numYear < $numYears; $numYear++) {
            $years[] = $this->getSchoolYear($numYear);
        }

        return $years;
    }

    private function getSchoolYear(int $numYear = 0): string
    {
        $baseYear = Carbon::create('Y', 'm')->subYears($numYear);

        if ($baseYear->format('Y-10') > $baseYear->format('Y-m')) {
            $year = Carbon::create($baseYear->format('Y'))->subYear()->format('Y').'-'.$baseYear->format('Y');
        } else {
            $year = $baseYear->format('Y').'-'.Carbon::create($baseYear->format('Y'))->addYear()->format('Y');
        }

        return $year;
    }
}
