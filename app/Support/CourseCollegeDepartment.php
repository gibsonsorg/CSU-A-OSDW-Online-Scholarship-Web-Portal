<?php

namespace App\Support;

final class CourseCollegeDepartment
{
    private const MAP = [
        'BACHELOR OF SCIENCE IN CRIMINOLOGY' => [
            'college_department' => 'College of Criminal Justice Education',
            'program' => 'BSCRIM',
        ],
        'BACHELOR OF SCIENCE IN FISHERIES' => [
            'college_department' => 'College of Fisheries and Aquatic Sciences',
            'program' => 'BSIF',
        ],
        'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY' => [
            'college_department' => 'College of Information and Computing Science',
            'program' => 'BSIT',
        ],
        'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT' => [
            'college_department' => 'College of Hospitality Management',
            'program' => 'BSHM',
        ],
        'BACHELOR OF SECONDARY EDUCATION' => [
            'college_department' => 'College of Teacher Education',
            'program' => 'BSED',
        ],
        'BACHELOR OF SCIENCE IN ACCOUNTING INFORMATION SYSTEM' => [
            'college_department' => 'College of Business, Entrepreneurship and Accountancy',
            'program' => 'BSAIS',
        ],
        'BACHELOR OF ELEMENTARY EDUCATION' => [
            'college_department' => 'College of Teacher Education',
            'program' => 'BEED',
        ],
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY' => [
            'college_department' => 'College of Industrial Technology',
            'program' => 'BSIndTech',
        ],
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Electronics' => [
            'college_department' => 'College of Industrial Technology',
            'program' => 'BSIndTech',
        ],
        'BACHELOR OF SCIENCE IN INDUSTRIAL TECHNOLOGY- Major in Automotive' => [
            'college_department' => 'College of Industrial Technology',
            'program' => 'BSIndTech',
        ],
    ];

    public static function forCourse(?string $course): ?string
    {
        return self::courseInformation($course)['college_department'] ?? null;
    }

    public static function programForCourse(?string $course): ?string
    {
        return self::courseInformation($course)['program'] ?? null;
    }

    public static function informationMap(): array
    {
        $map = [];
        foreach (self::MAP as $course => $information) {
            $map[self::normalizeCourse($course)] = $information;
        }

        return $map;
    }

    public static function courses(): array
    {
        return array_keys(self::MAP);
    }

    public static function map(): array
    {
        $map = [];
        foreach (self::MAP as $course => $information) {
            $map[$course] = $information['college_department'];
        }

        return $map;
    }

    private static function courseInformation(?string $course): ?array
    {
        $normalizedCourse = self::normalizeCourse($course);
        foreach (self::MAP as $knownCourse => $information) {
            if (self::normalizeCourse($knownCourse) === $normalizedCourse) {
                return $information;
            }
        }

        return null;
    }

    private static function normalizeCourse(?string $course): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim((string) $course)) ?? '';

        return mb_strtoupper($collapsed);
    }
}