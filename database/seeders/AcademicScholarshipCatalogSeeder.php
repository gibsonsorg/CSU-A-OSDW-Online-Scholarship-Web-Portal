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
                'name' => 'Cagayan Valley Assoc. of Hawaii',
                'type' => 'academic',
                'type_label' => 'International Educational Grant',
                'funding_type' => 'International',
                'scholarship_classification' => 'Academic',
                'description' => 'The Cagayan Valley Association of Hawaii (CVAH) Internationally Funded Scholarship is an academic grant program established through a partnership with Cagayan State University (CSU). It provides financial assistance to deserving students in Cagayan Valley, Philippines.\n\nLaunched in 2017 through an agreement between CVAH President Engr. Roger A. Urbi and CSU, the program initially supported students at CSU-Carig Campus and later expanded to other campuses such as CSU Tuguegarao. Funded by the Filipino diaspora community in Hawaii, USA, it aims to help undergraduate students meet their educational expenses.',
                'requirements' => [
                    'Must be an officially enrolled undergraduate student at an eligible Cagayan State University campus.',
                    'Must demonstrate clear financial need.',
                    'Must maintain the required General Weighted Average (GWA) set by the institutional guidelines for student welfare grants.',
                    'Must not have any record of severe disciplinary action or academic misconduct within the university.',
                ],
                'required_documents' => [],
                'logo_path' => null,
                'status' => 'active',
            ],
            [
                'name' => 'DOST SEI Project Strand',
                'type' => 'academic',
                'type_label' => 'Graduate School Scholarship',
                'funding_type' => 'Local-Government',
                'scholarship_classification' => 'Academic',
                'status' => 'active',
            ],
            [
                'name' => 'Tulong Dunong Program',
                'type' => 'academic',
                'type_label' => 'Kuya Win Scholarship Program',
                'funding_type' => 'Local-Government',
                'scholarship_classification' => 'Academic',
                'status' => 'active',
            ],
            [
                'name' => 'LGU Aparri',
                'type' => 'academic',
                'type_label' => 'Local Government Unit Scholarship',
                'funding_type' => 'Local-Government',
                'scholarship_classification' => "Local gov't",
                'status' => 'active',
            ],
            [
                'name' => 'Natalged A Lasam',
                'type' => 'academic',
                'type_label' => 'Educational Assistance Grant',
                'funding_type' => 'Local-Government',
                'scholarship_classification' => "Local gov't",
                'status' => 'active',
            ],
            [
                'name' => 'Andres Tamayo Scholarship',
                'type' => 'academic',
                'type_label' => 'Private Foundation Grant',
                'funding_type' => 'Local-Private',
                'scholarship_classification' => 'Academic-Merit',
                'status' => 'active',
            ],
            [
                'name' => 'Gokongwei Brothers Foundation',
                'type' => 'academic',
                'type_label' => 'Foundation Scholarship Program',
                'funding_type' => 'Local-Private',
                'scholarship_classification' => 'Academic-Merit',
                'status' => 'active',
            ],
            [
                'name' => 'University Based Scholarship',
                'type' => 'academic',
                'type_label' => 'Academic Excellence – In-House',
                'funding_type' => 'In-house',
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
                'funding_type' => 'Local-Government',
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
