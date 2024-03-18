<?php

namespace App\Services;

use App\Enums\AccountTypes;
use App\Models\Account;
use App\Models\UniversityMember;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SisApiService
{
    const SIS_UPN_FIELD = 'asUporabnik';

    const ACCOUNT_UPN_FIELD = 'username';

    public function getAllEmployees(UniversityMember $universityMember): Collection
    {
        $response = $this->getEndpointResponse($universityMember->sis_base_url, 'zaposleni', $universityMember->sis_current_year, $universityMember->code);
        if (! $response->ok()) {
            Log::error("{$universityMember->name} (code: {$universityMember->code}) SIS endpoint does not return employees data.");

            return collect();
        }

        return $response->collect();
    }

    public function getAllStudents(UniversityMember $universityMember): Collection
    {
        $data = collect();
        $years = $this->getStudentSchoolYears($universityMember->sis_current_year, $universityMember->sis_student_years);

        foreach ($years as $year) {
            $response = $this->getEndpointResponse($universityMember->sis_base_url, 'student', $year, $universityMember->code);
            if (! $response->ok()) {
                Log::error("{$universityMember->name} (code: {$universityMember->code}, year: {$year}) SIS endpoint does not return students data.");

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

    private function getEndpointResponse(string $baseSisApiUrl, string $endpoint, string $schoolYear, string $universityMemberCode): PromiseInterface|Response
    {
        Log::warning("{$baseSisApiUrl}/{$endpoint}}/{$schoolYear}/{$universityMemberCode}");

        return Http::withHeader(config('custom.sis_api_key_name'), config('custom.sis_api_key_value'))
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
}
