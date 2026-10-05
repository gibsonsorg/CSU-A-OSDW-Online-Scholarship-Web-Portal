<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'type_label',
        'funding_type',
        'scholarship_classification',
        'short_information',
        'description',
        'requirements',
        'required_documents',
        'logo_path',
        'status',
        'is_admin_created',
        'max_slots',
        'accepting_applications',
        'time_restricted',
        'application_start_time',
        'application_end_time',
        'application_start_date',
        'application_end_date',
    ];

    protected $casts = [
        'requirements' => 'array',
        'required_documents' => 'array',
        'max_slots' => 'integer',
        'accepting_applications' => 'boolean',
        'time_restricted' => 'boolean',
        'application_start_date' => 'date',
        'application_end_date' => 'date',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function activeApplicationsCount(): int
    {
        return $this->applications()
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'approved'])
                    ->orWhere(function ($query) {
                        $query->where('status', 'rejected')
                            ->whereHas('studentProfile', fn ($profileQuery) => $profileQuery
                                ->whereIn('rejection_reason', ['Wrong Uploaded Documents', 'Wrong Form'])
                                ->whereNotNull('resubmission_deadline')
                                ->where('resubmission_deadline', '>', now()));
                    });
            })
            ->whereHas('studentProfile', fn ($query) => $query->whereNull('locked_at'))
            ->where('created_at', '>', Carbon::parse(config('scholarships.slot_counting_starts_at'))
                ->setTimezone(config('app.timezone')))
            ->count();
    }

    public function availableSlotsCount(?int $activeApplications = null): ?int
    {
        if ($this->max_slots === null) {
            return null;
        }

        $activeApplications ??= $this->activeApplicationsCount();

        return max(0, (int) $this->max_slots - $activeApplications);
    }

    public function isWithinApplicationWindow(): bool
    {
        if (!$this->time_restricted) {
            return true;
        }

        if (blank($this->application_start_time) || blank($this->application_end_time)) {
            return false;
        }

        $now = Carbon::now();
        $start = Carbon::parse($this->application_start_time)->setDate($now->year, $now->month, $now->day);
        $end = Carbon::parse($this->application_end_time)->setDate($now->year, $now->month, $now->day);

        if ($start->lte($end)) {
            return $now->betweenIncluded($start, $end);
        }

        return $now->gte($start) || $now->lte($end);
    }

    public function isWithinApplicationDateRange(): bool
    {
        if (!$this->application_start_date && !$this->application_end_date) {
            return true;
        }

        $today = Carbon::today(config('app.timezone'))->toDateString();
        $startDate = $this->application_start_date
            ? Carbon::parse($this->application_start_date)->toDateString()
            : null;
        $endDate = $this->application_end_date
            ? Carbon::parse($this->application_end_date)->toDateString()
            : null;

        return ($startDate === null || $today >= $startDate)
            && ($endDate === null || $today <= $endDate);
    }

    public function applicationDateMessage(): ?string
    {
        $startDate = $this->application_start_date
            ? Carbon::parse($this->application_start_date)->format('F j, Y')
            : null;
        $endDate = $this->application_end_date
            ? Carbon::parse($this->application_end_date)->format('F j, Y')
            : null;

        if ($startDate && $endDate) {
            return "Applications accepted from {$startDate} to {$endDate}.";
        }

        if ($startDate) {
            return "Applications accepted starting {$startDate}.";
        }

        if ($endDate) {
            return "Applications accepted until {$endDate}.";
        }

        return null;
    }

    private function applicationDateContext(): ?string
    {
        $startDate = $this->application_start_date
            ? Carbon::parse($this->application_start_date)->format('F j, Y')
            : null;
        $endDate = $this->application_end_date
            ? Carbon::parse($this->application_end_date)->format('F j, Y')
            : null;

        if ($startDate && $endDate) {
            return "between {$startDate} and {$endDate}";
        }

        if ($startDate) {
            return "starting {$startDate}";
        }

        if ($endDate) {
            return "until {$endDate}";
        }

        return null;
    }

    public function availabilityMessage(?int $activeApplications = null): ?string
    {
        if (!$this->accepting_applications) {
            return 'This scholarship is currently not accepting applications.';
        }

        $activeApplications ??= $this->activeApplicationsCount();
        if ($this->max_slots !== null && $activeApplications >= (int) $this->max_slots) {
            return 'No slots available';
        }

        if (!$this->isWithinApplicationDateRange()) {
            return $this->applicationDateMessage();
        }

        if ($this->time_restricted && !$this->isWithinApplicationWindow()) {
            $start = blank($this->application_start_time)
                ? 'the configured start time'
                : Carbon::parse($this->application_start_time)->format('h:i A');
            $end = blank($this->application_end_time)
                ? 'the configured end time'
                : Carbon::parse($this->application_end_time)->format('h:i A');
            $dateContext = $this->applicationDateContext();

            $message = "Applications are only accepted from {$start} to {$end}";

            return $dateContext
                ? "{$message}, {$dateContext}."
                : "{$message}.";
        }

        return null;
    }
}
