<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ScholarshipTestDataSeeder extends Seeder
{
    private const EMAIL_PREFIX = 'test-scholarship.';
    private const APPLICATIONS_PER_SCHOLARSHIP = 8;
    private const MAX_APPLICATIONS_PER_STUDENT = 2;
    private const TEST_PASSWORD = 'TestStudent2026!';

    public function run(): void
    {
        $this->ensureLocalDatabase();
        $this->ensureRequiredSchema();

        $scholarships = Scholarship::query()
            ->where('status', 'active')
            ->whereIn('type', ['academic', 'non-academic'])
            ->orderBy('type')
            ->orderBy('id')
            ->get();
        $scholarshipsByType = $scholarships->groupBy('type');

        foreach (['academic', 'non-academic'] as $type) {
            if ($scholarshipsByType->get($type, collect())->isEmpty()) {
                throw new RuntimeException("No active {$type} scholarships were found. No test data was created.");
            }
        }

        $studentPlans = $this->buildStudentPlans($scholarshipsByType);
        $scholarshipCountBefore = Scholarship::query()->count();
        $createdUsers = 0;
        $reusedUsers = 0;
        $createdApplications = 0;
        $reusedApplications = 0;
        $createdProfiles = 0;

        DB::transaction(function () use (
            $studentPlans,
            &$createdUsers,
            &$reusedUsers,
            &$createdApplications,
            &$reusedApplications,
            &$createdProfiles
        ): void {
            foreach ($studentPlans as $studentIndex => $applications) {
                $person = $this->testStudents()[$studentIndex % count($this->testStudents())];
                $email = $this->testEmail($person, $studentIndex + 1);
                $user = User::withTrashed()->where('email', $email)->first();

                if ($user?->trashed()) {
                    throw new RuntimeException("A soft-deleted test account already uses {$email}; no records were modified.");
                }

                if ($user) {
                    if ((int) $user->role === 0 || $user->account_status !== 'approved' || !$user->email_verified_at) {
                        throw new RuntimeException("Existing test account {$email} is not in the expected test state; no records were modified.");
                    }
                    $reusedUsers++;
                } else {
                    $studentId = $this->availableStudentId($studentIndex + 1);
                    $fullName = trim(implode(' ', array_filter([
                        $person['first_name'],
                        $person['middle_name'],
                        $person['last_name'],
                    ])));

                    $user = User::create([
                        'name' => $fullName,
                        'student_id' => $studentId,
                        'email' => $email,
                        'email_verified_at' => now(),
                        'password' => self::TEST_PASSWORD,
                        'role' => 1,
                        'account_status' => 'approved',
                        'registered_attempt' => 1,
                    ]);
                    $createdUsers++;
                }

                if (count($applications) > self::MAX_APPLICATIONS_PER_STUDENT) {
                    throw new RuntimeException('The planned test data exceeds the current two-total application limit.');
                }

                foreach ($applications as $plannedApplication) {
                    $scholarship = $plannedApplication['scholarship'];
                    $status = $plannedApplication['status'];
                    $application = Application::query()->firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'scholarship_id' => $scholarship->id,
                        ],
                        ['status' => $status]
                    );

                    if ($application->wasRecentlyCreated) {
                        $createdApplications++;
                    } else {
                        $reusedApplications++;
                    }

                    $profile = StudentProfile::withTrashed()
                        ->where('application_id', $application->id)
                        ->first();

                    if ($profile?->trashed()) {
                        throw new RuntimeException('A test application has a soft-deleted profile; no records were modified.');
                    }

                    if (!$profile) {
                        $attributes = $this->profileAttributes($user, $person, $scholarship, $status);
                        $attributes['application_id'] = $application->id;
                        StudentProfile::create($attributes);
                        $createdProfiles++;
                    }
                }
            }
        });

        if (Scholarship::query()->count() !== $scholarshipCountBefore) {
            throw new RuntimeException('Scholarship definitions unexpectedly changed while seeding test data.');
        }

        $this->command?->info(sprintf(
            'Scholarship test data complete: %d users created, %d reused; %d applications created, %d reused; %d profiles created; %d active scholarships, %d applications each.',
            $createdUsers,
            $reusedUsers,
            $createdApplications,
            $reusedApplications,
            $createdProfiles,
            $scholarships->count(),
            self::APPLICATIONS_PER_SCHOLARSHIP
        ));
    }

    private function buildStudentPlans($scholarshipsByType): array
    {
        $statusCycle = ['pending', 'approved', 'rejected', 'pending', 'approved', 'rejected', 'pending', 'approved'];
        $assignments = [];

        for ($slot = 0; $slot < self::APPLICATIONS_PER_SCHOLARSHIP; $slot++) {
            foreach (['academic', 'non-academic'] as $type) {
                foreach ($scholarshipsByType->get($type, collect()) as $scholarship) {
                    $assignments[] = [
                        'scholarship' => $scholarship,
                        'status' => $statusCycle[$slot],
                    ];
                }
            }
        }

        $studentPlans = [];

        foreach ($assignments as $assignment) {
            $studentIndex = null;

            foreach ($studentPlans as $candidateIndex => $plannedApplications) {
                $alreadyHasScholarship = collect($plannedApplications)->contains(
                    fn (array $planned) => $planned['scholarship']->id === $assignment['scholarship']->id
                );

                if (count($plannedApplications) < self::MAX_APPLICATIONS_PER_STUDENT && !$alreadyHasScholarship) {
                    $studentIndex = $candidateIndex;
                    break;
                }
            }

            if ($studentIndex === null) {
                $studentIndex = count($studentPlans);
                $studentPlans[$studentIndex] = [];
            }

            $studentPlans[$studentIndex][] = $assignment;
        }

        return $studentPlans;
    }

    private function profileAttributes(User $user, array $person, Scholarship $scholarship, string $status): array
    {
        $isAcademic = $scholarship->type === 'academic';
        $approved = $status === 'approved';
        $rejected = $status === 'rejected';
        $rejectionReason = $rejected ? 'Test data: sample section rejection.' : null;

        return [
            'user_id' => $user->id,
            'source' => 'application',
            'student_id_number' => $user->student_id,
            'first_name' => $person['first_name'],
            'middle_name' => $person['middle_name'],
            'last_name' => $person['last_name'],
            'sex' => $person['sex'],
            'status' => 'Single',
            'email' => $user->email,
            'home_address' => $isAcademic ? null : 'TEST ADDRESS',
            'contact_number' => $isAcademic ? null : '+63 9000000000',
            'course' => 'TEST COURSE',
            'college_department' => null,
            'campus' => 'Aparri',
            'year_level' => '1st Year',
            'program' => $isAcademic ? 'TEST PROGRAM' : null,
            'religion' => $isAcademic ? 'TEST DATA' : null,
            'birthdate' => '2000-01-01',
            'birthplace' => 'TEST CAMPUS',
            'scholarship_name' => $scholarship->name,
            'scholarship_type' => trim((string) $scholarship->type_label) !== ''
                ? $scholarship->type_label
                : ($isAcademic ? 'Academic Scholarship' : 'Non-Academic Scholarship'),
            'grant_type' => $scholarship->type,
            'uploads' => null,
            'document_reviews' => [],
            'application_status' => $status,
            'rejection_reason' => $rejectionReason,
            'student_information_status' => $rejected ? 'rejected' : ($approved ? 'approved' : 'pending'),
            'student_information_reason' => $rejectionReason,
            'personal_information_status' => $approved ? 'approved' : 'pending',
            'personal_information_reason' => null,
            'scholarship_requirements_status' => $approved ? 'approved' : 'pending',
            'scholarship_requirements_reason' => null,
            'family_background_status' => !$isAcademic && $approved ? 'approved' : 'pending',
            'family_background_reason' => null,
            'reapply_count' => 0,
            'locked_at' => null,
        ];
    }

    private function availableStudentId(int $sequence): string
    {
        $number = 90000 + $sequence;

        do {
            $studentId = '99-' . str_pad((string) $number++, 5, '0', STR_PAD_LEFT);
        } while (User::withTrashed()->where('student_id', $studentId)->exists());

        return $studentId;
    }

    private function testEmail(array $person, int $sequence): string
    {
        $slug = Str::slug($person['first_name'] . ' ' . $person['last_name']);

        return sprintf('%s%03d.%s@example.com', self::EMAIL_PREFIX, $sequence, $slug);
    }

    private function ensureRequiredSchema(): void
    {
        $requiredColumns = [
            'users' => ['name', 'email', 'password', 'student_id', 'role', 'account_status', 'email_verified_at'],
            'applications' => ['user_id', 'scholarship_id', 'status'],
            'student_profiles' => [
                'user_id', 'application_id', 'first_name', 'middle_name', 'last_name', 'sex', 'status', 'email',
                'student_id_number', 'course', 'scholarship_name', 'scholarship_type', 'grant_type', 'uploads',
                'application_status', 'student_information_status', 'personal_information_status',
                'scholarship_requirements_status', 'document_reviews', 'family_background_status', 'reapply_count', 'locked_at',
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is missing. No test data was created.");
            }

            $missingColumns = array_diff($columns, Schema::getColumnListing($table));
            if ($missingColumns !== []) {
                throw new RuntimeException("Required columns are missing from {$table}: " . implode(', ', $missingColumns) . '. No test data was created.');
            }
        }
    }

    private function ensureLocalDatabase(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Scholarship test data may only be seeded in local or testing environments.');
        }

        $connection = DB::connection();
        $config = $connection->getConfig();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $host = strtolower((string) ($config['host'] ?? ''));
        $urlHost = strtolower((string) parse_url((string) ($config['url'] ?? ''), PHP_URL_HOST));
        $effectiveHost = $urlHost !== '' ? $urlHost : $host;

        if (!in_array($effectiveHost, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('Scholarship test data requires a local database host; no records were changed.');
        }
    }

    private function testStudents(): array
    {
        return [
            ['last_name' => 'MORES', 'first_name' => 'ROSELYN', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'NUÑEZ', 'first_name' => 'MANILYN', 'middle_name' => 'M', 'sex' => 'Female'],
            ['last_name' => 'SOCORRO', 'first_name' => 'SHEENA', 'middle_name' => 'V', 'sex' => 'Female'],
            ['last_name' => 'URMENETA', 'first_name' => 'VINCENT', 'middle_name' => 'Y', 'sex' => 'Female'],
            ['last_name' => 'ABON', 'first_name' => 'STEPHANIE JANE', 'middle_name' => 'V', 'sex' => 'Female'],
            ['last_name' => 'ACASILI', 'first_name' => 'JYASMIN JOY', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'CALATA', 'first_name' => 'MA. VICTORIA', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'ELAURZA', 'first_name' => 'SHAINA MARIE', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'GANGANI', 'first_name' => 'KRISTINE JOY', 'middle_name' => 'R', 'sex' => 'Female'],
            ['last_name' => 'MACUTAY', 'first_name' => 'MYCA', 'middle_name' => 'M', 'sex' => 'Female'],
            ['last_name' => 'SINON', 'first_name' => 'JOWEENA', 'middle_name' => 'E', 'sex' => 'Female'],
            ['last_name' => 'SIRIBAN', 'first_name' => 'ROWIE ANN', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'TEPAN', 'first_name' => 'ERICKA', 'middle_name' => 'P', 'sex' => 'Female'],
            ['last_name' => 'TORRES', 'first_name' => 'LYNDEN', 'middle_name' => 'G', 'sex' => 'Female'],
            ['last_name' => 'ULEP', 'first_name' => 'KENLEY', 'middle_name' => 'M', 'sex' => 'Female'],
            ['last_name' => 'YUNSON', 'first_name' => 'GLADYS', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'LADDARAN', 'first_name' => 'PERN', 'middle_name' => 'P', 'sex' => 'Female'],
            ['last_name' => 'AGUILLO', 'first_name' => 'ACE', 'middle_name' => 'S', 'sex' => 'Male'],
            ['last_name' => 'ALMATIA', 'first_name' => 'JESSICA MAE', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'BAJE', 'first_name' => 'KRISTEL JOY', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'BELTRAN', 'first_name' => 'ROWIN RICH', 'middle_name' => 'A', 'sex' => 'Male'],
            ['last_name' => 'BUGARIN', 'first_name' => 'IVEE JOY', 'middle_name' => 'T', 'sex' => 'Female'],
            ['last_name' => 'BUGARIN', 'first_name' => 'ALEXTER', 'middle_name' => 'R', 'sex' => 'Male'],
            ['last_name' => 'CABALTERA', 'first_name' => 'LILIBETH', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'CALUCAG', 'first_name' => 'REGINE', 'middle_name' => 'G', 'sex' => 'Female'],
            ['last_name' => 'CANSEJO', 'first_name' => 'NIELVIN', 'middle_name' => 'R', 'sex' => 'Male'],
            ['last_name' => 'CASTILLO', 'first_name' => 'AIRENE', 'middle_name' => 'R', 'sex' => 'Female'],
            ['last_name' => 'COBICO', 'first_name' => 'JOHN RAYMOND', 'middle_name' => 'A', 'sex' => 'Male'],
            ['last_name' => 'CRISOSTOMO', 'first_name' => 'LAILANIE', 'middle_name' => 'E', 'sex' => 'Female'],
            ['last_name' => 'CRISTOBAL', 'first_name' => 'ALEAH', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'ESTRADA', 'first_name' => 'REGIE', 'middle_name' => 'A', 'sex' => 'Male'],
            ['last_name' => 'GABILO', 'first_name' => 'JOED', 'middle_name' => null, 'sex' => 'Male'],
            ['last_name' => 'GUIMMAYEN', 'first_name' => 'HANNAH ERIKA', 'middle_name' => 'F', 'sex' => 'Female'],
            ['last_name' => 'MANZANO', 'first_name' => 'SOPHIA LEI', 'middle_name' => '2', 'sex' => 'Female'],
            ['last_name' => 'NANUD', 'first_name' => 'PRINCESS JOANNAH LIE', 'middle_name' => 'P', 'sex' => 'Male'],
            ['last_name' => 'PONCE', 'first_name' => 'JOHN CLYDE', 'middle_name' => null, 'sex' => 'Male'],
            ['last_name' => 'IRECE', 'first_name' => 'GENE ROEHL', 'middle_name' => 'M', 'sex' => 'Female'],
            ['last_name' => 'LANSANG', 'first_name' => 'DANA MAE', 'middle_name' => 'B', 'sex' => 'Female'],
            ['last_name' => 'LASAM', 'first_name' => 'ANJO', 'middle_name' => 'D', 'sex' => 'Male'],
            ['last_name' => 'LUCERO', 'first_name' => 'ROY', 'middle_name' => 'D', 'sex' => 'Male'],
            ['last_name' => 'MELICANA', 'first_name' => 'LESEL ANN', 'middle_name' => 'B', 'sex' => 'Female'],
            ['last_name' => 'NARIO', 'first_name' => 'JEMA', 'middle_name' => 'N', 'sex' => 'Female'],
            ['last_name' => 'NAVARRO', 'first_name' => 'ROCEL', 'middle_name' => 'P', 'sex' => 'Female'],
            ['last_name' => 'PALO', 'first_name' => 'LOISA', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'PARAUNDA', 'first_name' => 'GLENMAR', 'middle_name' => 'B', 'sex' => 'Male'],
            ['last_name' => 'PAYAS', 'first_name' => 'EDNALYN', 'middle_name' => 'R', 'sex' => 'Female'],
            ['last_name' => 'PAYAS', 'first_name' => 'MARVIN', 'middle_name' => 'R', 'sex' => 'Male'],
            ['last_name' => 'PAYAS', 'first_name' => 'JONALD', 'middle_name' => 'M', 'sex' => 'Male'],
            ['last_name' => 'PEDRO', 'first_name' => 'RUBY ANN', 'middle_name' => 'M', 'sex' => 'Female'],
            ['last_name' => 'PEDRONAN', 'first_name' => 'REYMARK', 'middle_name' => 'A', 'sex' => 'Male'],
            ['last_name' => 'FELIPE', 'first_name' => 'MICHELLE', 'middle_name' => 'D', 'sex' => 'Female'],
            ['last_name' => 'GUINSATAO', 'first_name' => 'JESRAEL', 'middle_name' => 'D', 'sex' => 'Male'],
            ['last_name' => 'LABTIS', 'first_name' => 'JOYLYN', 'middle_name' => 'S', 'sex' => 'Female'],
            ['last_name' => 'BUGARIN', 'first_name' => 'NARLYN', 'middle_name' => 'R', 'sex' => 'Female'],
            ['last_name' => 'NUEZ', 'first_name' => 'CYNTHIA MAE', 'middle_name' => 'B', 'sex' => 'Female'],
            ['last_name' => 'TAMANU', 'first_name' => 'JESUS', 'middle_name' => 'R', 'sex' => 'Male'],
            ['last_name' => 'ABAJO', 'first_name' => 'CRISTY ANN', 'middle_name' => 'T', 'sex' => 'Female'],
            ['last_name' => 'BELEN', 'first_name' => 'ELIZA', 'middle_name' => 'P', 'sex' => 'Female'],
            ['last_name' => 'RODRIGO', 'first_name' => 'PRECY', 'middle_name' => 'I', 'sex' => 'Female'],
            ['last_name' => 'MEDRAZO', 'first_name' => 'DONALYN', 'middle_name' => 'J', 'sex' => 'Female'],
            ['last_name' => 'PANOTAN', 'first_name' => 'JOHN MARK', 'middle_name' => 'A', 'sex' => 'Male'],
            ['last_name' => 'ULANIMO', 'first_name' => 'JARA KAYE', 'middle_name' => 'O', 'sex' => 'Female'],
            ['last_name' => 'ESTRADA', 'first_name' => 'JHOAN', 'middle_name' => 'B', 'sex' => 'Female'],
            ['last_name' => 'VISARIO', 'first_name' => 'ANGELICA KAE', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'QUITOG', 'first_name' => 'MARY ANN', 'middle_name' => 'A', 'sex' => 'Female'],
            ['last_name' => 'URBIS', 'first_name' => 'JESSA MAE', 'middle_name' => 'C', 'sex' => 'Female'],
            ['last_name' => 'MOLINA', 'first_name' => 'C-JAYNARISCO JR.', 'middle_name' => 'C', 'sex' => 'Male'],
            ['last_name' => 'BELANDRES', 'first_name' => 'JERELL', 'middle_name' => 'B', 'sex' => 'Male'],
            ['last_name' => 'CABILISA', 'first_name' => 'JANETH', 'middle_name' => 'I', 'sex' => 'Female'],
            ['last_name' => 'FERNANDEZ', 'first_name' => 'ALVIN', 'middle_name' => 'B', 'sex' => 'Male'],
        ];
    }
}