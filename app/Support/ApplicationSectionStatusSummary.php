<?php

namespace App\Support;

use App\Models\StudentProfile;

class ApplicationSectionStatusSummary
{
    public static function for(StudentProfile $application): array
    {
        $sections = [
            [
                'label' => 'Student Information',
                'status' => $application->student_information_status,
                'reason' => $application->student_information_reason,
            ],
            [
                'label' => 'Personal Information',
                'status' => $application->personal_information_status,
                'reason' => $application->personal_information_reason,
            ],
        ];

        if ($application->grant_type === 'non-academic') {
            $sections[] = [
                'label' => 'Family Background',
                'status' => $application->family_background_status,
                'reason' => $application->family_background_reason,
            ];
        }

        $sections[] = [
            'label' => 'Scholarship & Requirements',
            'status' => $application->scholarship_requirements_status,
            'reason' => $application->scholarship_requirements_reason,
        ];

        $sections = array_map(static function (array $section): array {
            $status = in_array($section['status'], ['approved', 'rejected'], true)
                ? ucfirst($section['status'])
                : 'Pending';

            return [
                'label' => $section['label'],
                'status' => $status,
                'reason' => $status === 'Rejected' ? $section['reason'] : null,
            ];
        }, $sections);

        $counts = array_count_values(array_column($sections, 'status'));
        $approvedCount = $counts['Approved'] ?? 0;
        $rejectedCount = $counts['Rejected'] ?? 0;
        $pendingCount = $counts['Pending'] ?? 0;

        return [
            'sections' => $sections,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'pending_count' => $pendingCount,
            'reviewed_count' => $approvedCount + $rejectedCount,
            'total_count' => count($sections),
            'overall_status' => $pendingCount > 0 ? 'Pending' : ($rejectedCount > 0 ? 'Rejected' : 'Approved'),
        ];
    }
}