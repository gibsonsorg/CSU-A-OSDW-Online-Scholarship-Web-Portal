<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;

class AdminDashboardStats
{
    public function get(): array
    {
        $now = now();

        $totalGrants = StudentProfile::query()
            ->whereNull('deleted_at')
            ->where('application_status', 'approved')
            ->count();

        $totalApplications = StudentProfile::query()
            ->whereNull('deleted_at')
            ->count();
        $pendingApplications = StudentProfile::query()
            ->whereNull('deleted_at')
            ->where('application_status', 'pending')
            ->count();

        $registeredUsers = User::query()
            ->where('role', '!=', 0)
            ->whereNull('deleted_at')
            ->count();
        $newRegisteredThisMonth = User::query()
            ->where('role', '!=', 0)
            ->whereNull('deleted_at')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        return [
            'total_grants' => $totalGrants,
            'total_applications' => $totalApplications,
            'pending_applications' => $pendingApplications,
            'registered_users' => $registeredUsers,
            'new_registered_this_month' => $newRegisteredThisMonth,
        ];
    }
}