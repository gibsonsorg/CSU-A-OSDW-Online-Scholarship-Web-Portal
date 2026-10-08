<?php

namespace App\Support;

final class CourseCollegeDepartment
{
    private const MAP = [
        'BACHELOR OF SCIENCE IN CRIMINOLOGY' => 'College of Criminal Justice Education',
        'BACHELOR OF SCIENCE IN FISHERIES' => 'College of Fisheries and Aquatic Sciences',
        'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY' => 'College of Information and Computing Science',
        'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT' => 'College of Hospitality Management',
        'BACHELOR OF SECONDARY EDUCATION' => 'College of Teacher Education',
        'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM' => 'College of Business, Entrepreneurship and Accountancy',
        'BACHELOR OF ELEMENTARY EDUCATION' => 'College of Teacher Education',
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY' => 'College of Industrial Technology',
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics' => 'College of Industrial Technology',
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive' => 'College of Industrial Technology',
    ];

    public static function forCourse(?string $course): ?string
    {
        $trimmed = trim((string) $course);
        return self::MAP[$trimmed] ?? null;
    }

    public static function courses(): array
    {
        return array_keys(self::MAP);
    }

    public static function map(): array
    {
        return self::MAP;
    }
}