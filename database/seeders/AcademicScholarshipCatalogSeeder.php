<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class AcademicScholarshipCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $scholarships = [
            [
                'name' => 'University Based Scholarship',
                'type' => 'academic',
                'type_label' => 'Academic Excellence – In-House',
                'funding_type' => 'In-House',
                'scholarship_classification' => 'Merit-Based',
                'description' => 'The University Based Scholarship recognizes students with outstanding academic performance throughout the school year and provides financial assistance to help them continue their studies.',
                'requirements' => [],
                'required_documents' => [],
                'logo_path' => null,
                'status' => 'active',
            ],
            [
                'name' => 'ACEF – GIAHEP',
                'type' => 'academic',
                'type_label' => 'Agricultural Credit & Education Fund',
                'funding_type' => 'Government Based',
                'scholarship_classification' => 'Locally Funded',
                'description' => 'The  ACEF-GIAHEP (Agricultural Competitiveness Enhancement Fund Grants-in-Aid for Higher Education Program) is a joint financial assistance program by the Commission on Higher Education and the Department of Agriculture designed to support undergraduate students pursuing degrees in agriculture, forestry, fisheries, and veterinary medicine\n        More info visits:https://acef.da.gov.ph/\n    \n        Qualifications\n\n        *Citizenship: Must be a Filipino citizen\n\n        *Academic Status: Graduating high school students, high school graduates, college unit earners, or Alternative Learning System (ALS/PEPT) passers.\n\n        *Income Limit: Combined annual gross income of parents or guardians must not exceed ₱300,000.\n\n        *Documents: Requires a PSA birth certificate, proof of admission/enrollment in a CHED-recognized higher education institution, and proof of family income (such as an ITR or BIR certificate of tax exemption).',
                'requirements' => [
                    'Citizenship: Must be a Filipino citizen',
                    'Academic Status: Graduating high school students, high school graduates, college unit earners, or Alternative Learning System (ALS/PEPT) passers.',
                    'Income Limit: Combined annual gross income of parents or guardians must not exceed ₱300,000.',
                    'Documents: Requires a PSA birth certificate, proof of admission/enrollment in a CHED-recognized higher education institution, and proof of family income (such as an ITR or BIR certificate of tax exemption).',
                ],
                'required_documents' => [],
                'logo_path' => null,
                'status' => 'active',
            ],
        ];

        foreach ($scholarships as $scholarship) {
            if (!Scholarship::where('name', $scholarship['name'])->exists()) {
                Scholarship::create($scholarship);
            }
        }
    }
}
