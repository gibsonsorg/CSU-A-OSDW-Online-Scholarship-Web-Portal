<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class NonAcademicScholarshipCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $scholarships = [
            ['name' => 'Athletic Scholarship', 'type_label' => 'Athletic Grant', 'scholarship_classification' => 'Sport-based'],
            ['name' => "Employees' Grant", 'type_label' => 'Employee Grant', 'scholarship_classification' => 'Employee benefit'],
            ['name' => 'Socio-Cultural Scholarship', 'type_label' => 'Socio-Cultural Grant', 'scholarship_classification' => 'Cultural Arts'],
            ['name' => 'Campus Student Publication', 'type_label' => 'Publication Grant', 'scholarship_classification' => 'Publication'],
            ['name' => 'Differently Abled Persons', 'type_label' => 'Inclusive Grant', 'scholarship_classification' => 'Inclusive/PWD'],
            ['name' => 'Student Council Federation Grant', 'type_label' => 'Student Leadership Grant', 'scholarship_classification' => 'Leadership'],
            ['name' => 'CSU Aparri Alumni Association', 'type_label' => 'Alumni Grant', 'scholarship_classification' => 'Alumni funded'],
            ['name' => 'Tertiary Education Subsidy', 'type_label' => 'Education Subsidy', 'scholarship_classification' => 'Locally funded'],
            ['name' => 'Agkaykaysa', 'type_label' => 'Community Grant', 'scholarship_classification' => 'Locally funded'],
            ['name' => 'Tulong Dunong Program', 'type_label' => 'Education Assistance Grant', 'scholarship_classification' => 'Locally funded'],
            ['name' => 'Open Heart Scholarship', 'type_label' => 'Private Grant', 'scholarship_classification' => 'Private'],
            ['name' => 'Talamayan Scholarship', 'type_label' => 'Community Scholarship', 'scholarship_classification' => 'Locally funded'],
        ];

        foreach ($scholarships as $scholarship) {
            Scholarship::firstOrCreate(
                ['name' => $scholarship['name'], 'type' => 'non-academic'],
                array_merge($scholarship, [
                    'type' => 'non-academic',
                    'funding_type' => 'locally funded',
                    'status' => 'active',
                ])
            );
        }
    }
}
