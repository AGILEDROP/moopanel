<?php

namespace Database\Seeders;

use App\Models\UniversityMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class UniversityMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universityMembers = [
            [
                'code' => '3000071',
                'acronym' => 'BF',
                'name' => 'Biotehniška fakulteta',
            ],
            [
                'code' => '3000004',
                'acronym' => 'FU',
                'name' => 'Fakulteta za upravo',
            ],
            [
                'code' => '3000011',
                'acronym' => 'ZF',
                'name' => 'Zdravstvena fakulteta',
            ],
            [
                'code' => '3000006',
                'acronym' => 'FSD',
                'name' => 'Fakulteta za socialno delo',
            ],
            [
                'code' => '3000037',
                'acronym' => 'VF',
                'name' => 'Veterinarska fakulteta',
            ],
            [
                'code' => '3000050',
                'acronym' => 'TEOF',
                'name' => 'Teološka fakulteta',
            ],
            [
                'code' => '3000020',
                'acronym' => 'PF',
                'name' => 'Pravna fakulteta',
            ],
            [
                'code' => '3000001',
                'acronym' => 'PEF',
                'name' => 'Pedagoška fakulteta',
            ],
            [
                'code' => '3000029',
                'acronym' => 'NTF',
                'name' => 'Naravoslovnotehniška fakulteta',
            ],
            [
                'code' => '3000041',
                'acronym' => 'MF',
                'name' => 'Medicinska fakulteta',
            ],
            [
                'code' => '3000018',
                'acronym' => 'FF',
                'name' => 'Filozofska fakulteta',
            ],
            [
                'code' => '3000022',
                'acronym' => 'FSP',
                'name' => 'Fakulteta za šport',
            ],
            [
                'code' => '3000023',
                'acronym' => 'FS',
                'name' => 'Fakulteta za strojništvo',
            ],
            [
                'code' => '3000063',
                'acronym' => 'FRI',
                'name' => 'Fakulteta za računalništvo in informatiko',
            ],
            [
                'code' => '3000009',
                'acronym' => 'FPP',
                'name' => 'Fakulteta za pomorstvo in promet',
            ],
            [
                'code' => '3000027',
                'acronym' => 'FMF',
                'name' => 'Fakulteta za matematiko in fiziko',
            ],
            [
                'code' => '3000030',
                'acronym' => 'FKKT',
                'name' => 'Fakulteta za kemijo in kemijsko tehnologijo',
            ],
            [
                'code' => '3000026',
                'acronym' => 'FGG',
                'name' => 'Fakulteta za gradbeništvo in geodezijo',
            ],
            [
                'code' => '3000031',
                'acronym' => 'FFA',
                'name' => 'Fakulteta za farmacijo',
            ],
            [
                'code' => '3000064',
                'acronym' => 'FE',
                'name' => 'Fakulteta za elektrotehniko',
            ],
            [
                'code' => '3000021',
                'acronym' => 'FDV',
                'name' => 'Fakulteta za družbene vede',
            ],
            [
                'code' => '3000025',
                'acronym' => 'FA',
                'name' => 'Fakulteta za arhitekturo',
            ],
            [
                'code' => '3000019',
                'acronym' => 'EF',
                'name' => 'Ekonomska fakulteta',
            ],
            [
                'code' => '3000042',
                'acronym' => 'ALUO',
                'name' => 'Akademija za likovno umetnost in oblikovanje',
            ],
            [
                'code' => '3000044',
                'acronym' => 'AGRFT',
                'name' => 'Akademija za gledališče, radio, film in televizijo',
            ],
            [
                'code' => '3000043',
                'acronym' => 'AG',
                'name' => 'Akademija za glasbo',
            ],
            //   [
            //      'code' => '3000074',
            //      'acronym' => 'UL',
            //      'name' => '',
            //   ],
        ];

        foreach ($universityMembers as $universityMember) {
            // For now all accounts (with students) should be fetched from current year.
            // In the future this number will tell SisApiService how many years back
            // it should look to update user accounts type & member.
            UniversityMember::factory()->create([
                'code' => $universityMember['code'],
                'acronym' => $universityMember['acronym'],
                'name' => $universityMember['name'],
                'sis_base_url' => 'https://visapi.uni-lj.si/UL_MOODLE2/RESTAdapter/v1',
                'sis_current_year' => '2023-2024',
                'sis_student_years' => 1,
            ]);
        }
    }
}
