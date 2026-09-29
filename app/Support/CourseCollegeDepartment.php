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
    ];

    public static function forCourse(?string $course): ?string
    {
        return self::MAP[trim((string) $course)] ?? null;
    }

    public static function courses(): array
    {
        return array_keys(self::MAP);
    }
}