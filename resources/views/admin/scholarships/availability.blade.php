<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Availability | CSU Grants</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main" id="adminMain">
        @include('admin.partials.sidebar-toggle')

        <div class="topbar">
            <div class="topbar-left">
                <h1>Manage Scholarship Availability</h1>
                <p>Set application capacity, hours, and acceptance status.</p>
            </div>
        </div>

        <div class="content availability-page">
            @if (session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully!',
                            text: 'Scholarship availability has been updated successfully.',
                            confirmButtonColor: '#6b0f1a',
                        });
                    });
                </script>
            @elseif (session('error') || $errors->any())
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Unable to Save Changes',
                            text: @json(session('error') ?: $errors->first()),
                            confirmButtonColor: '#6b0f1a',
                        });
                    });
                </script>
            @endif

            <form method="GET" action="{{ route('admin.scholarships.availability') }}" class="availability-filters">
                <label>
                    <span>Type of Scholarship</span>
                    <select name="type" onchange="this.form.elements.name.value = ''; this.form.submit()">
                        <option value="">All Types</option>
                        <option value="academic" @selected($selectedType === 'academic')>Academic</option>
                        <option value="non-academic" @selected($selectedType === 'non-academic')>Non-Academic</option>
                    </select>
                </label>
                <label>
                    <span>Name of Scholarship</span>
                    <select name="name">
                        <option value="">All Scholarships</option>
                        @foreach ($nameOptions as $name)
                            <option value="{{ $name }}" @selected($selectedName === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn">Filter</button>
            </form>

            <div class="availability-table-wrap">
                <table class="availability-table">
                    <thead>
                        <tr>
                            <th>Name of Scholarship</th>
                            <th>Slots</th>
                            <th>Deadline</th>
                            <th>Time Restriction</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scholarships as $scholarship)
                            @php($formId = 'availability-form-' . $scholarship->id)
                            <tr>
                                <td>
                                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.scholarships.availability.update', $scholarship) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <strong>{{ $scholarship->name }}</strong>
                                    <small class="availability-type">{{ $scholarship->type === 'academic' ? 'Academic' : 'Non-Academic' }}</small>
                                </td>
                                <td>
                                    <input form="{{ $formId }}" type="number" name="max_slots" min="0" value="{{ old('max_slots', $scholarship->max_slots) }}" placeholder="No limit" aria-label="Slots for {{ $scholarship->name }}">
                                </td>
                                <td>
                                    <div class="time-controls">
                                        <label class="time-field">
                                            <span>Start Date</span>
                                            <input form="{{ $formId }}" type="date" name="application_start_date" value="{{ old('application_start_date', $scholarship->application_start_date?->format('Y-m-d')) }}" aria-label="Application start date for {{ $scholarship->name }}">
                                        </label>
                                        <label class="time-field">
                                            <span>End Date</span>
                                            <input form="{{ $formId }}" type="date" name="application_end_date" value="{{ old('application_end_date', $scholarship->application_end_date?->format('Y-m-d')) }}" aria-label="Application end date for {{ $scholarship->name }}">
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="time-controls">
                                        <label class="toggle-control" aria-label="Enable time restriction for {{ $scholarship->name }}">
                                            <input form="{{ $formId }}" type="checkbox" name="time_restricted" value="1" data-time-toggle @checked(old('time_restricted', $scholarship->time_restricted))>
                                            <span class="toggle-track"></span>
                                        </label>
                                        <label class="time-field">
                                            <span>Start Time</span>
                                            <input form="{{ $formId }}" type="time" name="application_start_time" value="{{ old('application_start_time', $scholarship->application_start_time) }}" data-start-time @disabled(!old('time_restricted', $scholarship->time_restricted))>
                                        </label>
                                        <label class="time-field">
                                            <span>End Time</span>
                                            <input form="{{ $formId }}" type="time" name="application_end_time" value="{{ old('application_end_time', $scholarship->application_end_time) }}" data-end-time @disabled(!old('time_restricted', $scholarship->time_restricted))>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-control">
                                        <label class="toggle-control" aria-label="Accept applications for {{ $scholarship->name }}">
                                            <input form="{{ $formId }}" type="checkbox" name="accepting_applications" value="1" data-status-toggle @checked(old('accepting_applications', $scholarship->accepting_applications))>
                                            <span class="toggle-track"></span>
                                        </label>
                                        <span data-status-label>{{ old('accepting_applications', $scholarship->accepting_applications) ? 'Enabled' : 'Disabled' }}</span>
                                    </div>
                                    @unless (old('accepting_applications', $scholarship->accepting_applications))
                                        <small class="status-caption" data-status-caption>Apply Now button will be disabled for students.</small>
                                    @endunless
                                </td>
                                <td><button form="{{ $formId }}" type="submit" class="btn save-btn">Save</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No scholarships match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            let confirmationOpen = false;

            document.querySelectorAll('.availability-table .save-btn').forEach(function (button) {
                button.addEventListener('click', async function (event) {
                    event.preventDefault();

                    if (confirmationOpen || button.disabled) return;

                    const form = document.getElementById(button.getAttribute('form'));
                    if (!form) return;

                    confirmationOpen = true;
                    let result;

                    try {
                        result = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Do you want to save these scholarship availability changes?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Save Changes',
                            cancelButtonText: 'No, Cancel',
                            confirmButtonColor: '#6b0f1a',
                            cancelButtonColor: '#6b7280',
                            reverseButtons: true,
                            focusCancel: true,
                        });
                    } catch (error) {
                        confirmationOpen = false;
                        await Swal.fire({
                            icon: 'error',
                            title: 'Unable to Open Confirmation',
                            text: 'Please try saving again.',
                            confirmButtonColor: '#6b0f1a',
                        });
                        return;
                    }

                    confirmationOpen = false;
                    if (!result.isConfirmed || !form.reportValidity()) return;

                    button.disabled = true;
                    button.textContent = 'Saving...';
                    try {
                        form.requestSubmit();
                    } catch (error) {
                        button.disabled = false;
                        button.textContent = 'Save';
                        await Swal.fire({
                            icon: 'error',
                            title: 'Unable to Save Changes',
                            text: 'Please try again.',
                            confirmButtonColor: '#6b0f1a',
                        });
                    }
                });
            });
        })();

        document.querySelectorAll('[data-time-toggle]').forEach(function (toggle) {
            const row = toggle.closest('tr');
            const fields = row.querySelectorAll('[data-start-time], [data-end-time]');
            const syncTimeFields = function () {
                fields.forEach(function (field) {
                    field.disabled = !toggle.checked;
                });
            };
            toggle.addEventListener('change', syncTimeFields);
            syncTimeFields();
        });

        document.querySelectorAll('[data-status-toggle]').forEach(function (toggle) {
            const row = toggle.closest('tr');
            const label = row.querySelector('[data-status-label]');
            let caption = row.querySelector('[data-status-caption]');
            toggle.addEventListener('change', function () {
                label.textContent = toggle.checked ? 'Enabled' : 'Disabled';
                if (!caption && !toggle.checked) {
                    const newCaption = document.createElement('small');
                    newCaption.className = 'status-caption';
                    newCaption.dataset.statusCaption = '';
                    newCaption.textContent = 'Apply Now button will be disabled for students.';
                    row.querySelector('.status-control').after(newCaption);
                    caption = newCaption;
                }
                if (caption) {
                    caption.hidden = toggle.checked;
                }
            });
        });
    </script>

    <style>
        .availability-page { padding-bottom: 32px; }
        .availability-filters { display: flex; align-items: end; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; padding: 18px; background: #fff; border: 1px solid #e2e5e9; border-radius: 6px; }
        .availability-filters label, .time-field { display: grid; gap: 6px; min-width: 220px; }
        .availability-filters label span, .time-field span { color: #515b66; font-size: 13px; font-weight: 600; }
        .availability-filters select, .availability-table input[type="number"], .availability-table input[type="time"] { min-height: 38px; padding: 7px 10px; border: 1px solid #cbd1d8; border-radius: 4px; background: #fff; }
        .availability-table-wrap { overflow-x: auto; background: #fff; border: 1px solid #e2e5e9; border-radius: 6px; }
        .availability-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .availability-table th, .availability-table td { padding: 14px 12px; border-bottom: 1px solid #e8eaed; text-align: left; vertical-align: middle; }
        .availability-table th { background: #f7f8fa; color: #343b43; font-size: 13px; }
        .availability-table td small { display: block; margin-top: 6px; color: #68727d; font-size: 12px; }
        .availability-type { color: #68727d; }
        .availability-table input[type="number"] { width: 100px; }
        .time-controls, .status-control { display: flex; align-items: center; gap: 10px; }
        .time-field { min-width: 130px; }
        .time-field input:disabled { background: #eceff2; color: #7e8791; cursor: not-allowed; }
        .toggle-control { display: inline-flex; flex: 0 0 auto; cursor: pointer; }
        .toggle-control input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .toggle-track { position: relative; width: 40px; height: 22px; border-radius: 12px; background: #9aa2aa; transition: background .15s ease; }
        .toggle-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: transform .15s ease; }
        .toggle-control input:checked + .toggle-track { background: #28644b; }
        .toggle-control input:checked + .toggle-track::after { transform: translateX(18px); }
        .toggle-control input:focus-visible + .toggle-track { outline: 2px solid #174f83; outline-offset: 2px; }
        .save-btn { white-space: nowrap; }
        .empty-state { padding: 30px !important; color: #68727d; text-align: center !important; }
        @media (max-width: 700px) { .availability-filters { align-items: stretch; flex-direction: column; } .availability-filters label { min-width: 0; } }
    </style>
    <script src="{{ asset('js/admin-sidebar.js') }}"></script>
</body>
</html>