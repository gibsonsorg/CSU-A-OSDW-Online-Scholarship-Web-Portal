<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class NonAcademicScholarshipCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $scholarships = [
            ['name' => 'Athletic Scholarship', 'type_label' => 'Athletic Grant', 'scholarship_classification' => 'Sport-based', 'funding_type' => 'In-house'],
            ['name' => "Employees' Grant", 'type_label' => 'Employee Grant', 'scholarship_classification' => 'Employee benefit', 'funding_type' => 'In-house'],
            ['name' => 'Socio-Cultural Scholarship', 'type_label' => 'Socio-Cultural Grant', 'scholarship_classification' => 'Cultural Arts', 'funding_type' => 'In-house'],
            ['name' => 'Campus Student Publication', 'type_label' => 'Publication Grant', 'scholarship_classification' => 'Publication', 'funding_type' => 'University'],
            ['name' => 'Differently Abled Persons', 'type_label' => 'Inclusive Grant', 'scholarship_classification' => 'Inclusive/PWD', 'funding_type' => 'University'],
            ['name' => 'Student Council Federation Grant', 'type_label' => 'Student Leadership Grant', 'scholarship_classification' => 'Leadership', 'funding_type' => 'University'],
            ['name' => 'CSU Aparri Alumni Association', 'type_label' => 'Alumni Grant', 'scholarship_classification' => 'Alumni funded', 'funding_type' => 'University'],
            ['name' => 'Tertiary Education Subsidy', 'type_label' => 'Education Subsidy', 'scholarship_classification' => 'Locally funded', 'funding_type' => 'Government'],
            ['name' => 'Agkaykaysa', 'type_label' => 'Community Grant', 'scholarship_classification' => 'Locally funded', 'funding_type' => 'Local-Government'],
            ['name' => 'Tulong Dunong Program', 'type_label' => 'Education Assistance Grant', 'scholarship_classification' => 'Locally funded', 'funding_type' => 'Government'],
            ['name' => 'Open Heart Scholarship', 'type_label' => 'Private Grant', 'scholarship_classification' => 'Private', 'funding_type' => 'Local-Private'],
            ['name' => 'Talamayan Scholarship', 'type_label' => 'Community Scholarship', 'scholarship_classification' => 'Locally funded', 'funding_type' => 'Local-Private'],
        ];

        foreach ($scholarships as $scholarship) {
            Scholarship::updateOrCreate(
                ['name' => $scholarship['name'], 'type' => 'non-academic'],
                array_merge($scholarship, [
                    'type' => 'non-academic',
                    'status' => 'active',
                ])
            );
        }
    }
}
