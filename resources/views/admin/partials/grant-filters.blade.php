<form class="grant-filters" method="GET" action="{{ url()->current() }}">
    <div class="grant-filter-field">
        <label for="grant-date-from">Date From</label>
        <input id="grant-date-from" type="date" name="date_from" value="{{ request('date_from') }}">
    </div>
    <div class="grant-filter-field">
        <label for="grant-date-to">Date To</label>
        <input id="grant-date-to" type="date" name="date_to" value="{{ request('date_to') }}">
    </div>
    <div class="grant-filter-field">
        <label for="grant-scholarship-name">Name of Scholarship</label>
        <select id="grant-scholarship-name" name="scholarship_name">
            <option value="" @selected(request('scholarship_name', '') === '')>All Scholarships</option>
            @foreach($scholarshipNames as $scholarshipName)
                <option value="{{ $scholarshipName }}" @selected(request('scholarship_name') === $scholarshipName)>{{ $scholarshipName }}</option>
            @endforeach
        </select>
    </div>
    <div class="grant-filter-field">
        <label for="grant-status">Status</label>
        <select id="grant-status" name="status">
            <option value="all" @selected(request('status', 'all') === 'all')>All</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
        </select>
    </div>
    <button class="grant-filter-button" type="submit">Filter</button>
    @if(in_array($filterType ?? null, ['academic', 'non-academic'], true))
        <button id="add-applicant-button" class="grant-filter-button" type="button" data-action="add-applicant" data-grant-type="{{ $filterType }}">Add Applicant</button>
    @endif
    <p class="grant-filter-error" id="grant-filter-error" role="alert" hidden>Date From must be before or equal to Date To.</p>
</form>

<style>
    .grant-filters {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        width: 100%;
        padding: 10px;
        margin: 14px 0 0;
        border: 1px solid #dfe4ec;
        border-radius: 10px;
        background: #fff;
    }

    .grant-filter-field {
        display: flex;
        flex: 1 1 140px;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .grant-filter-field label {
        color: #687386;
        font-size: 0.68rem;
        font-weight: 600;
    }

    .grant-filter-field input,
    .grant-filter-field select {
        width: 100%;
        height: 32px;
        border: 1px solid #9aa3ad;
        border-radius: 4px;
        background: #fff;
        color: #273142;
        font: inherit;
        font-size: 0.74rem;
        padding: 0 9px;
    }

    .grant-filter-button {
        height: 32px;
        flex: 0 0 120px;
        border: 0;
        border-radius: 4px;
        background: #4a0a12;
        color: #fff;
        cursor: pointer;
        font: inherit;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .grant-filter-button:hover {
        background: #6b0f1a;
    }

    .grant-filter-error {
        flex-basis: 100%;
        margin: 0;
        color: #c0392b;
        font-size: 0.68rem;
    }

    @media (max-width: 900px) {
        .grant-filters {
            flex-wrap: wrap;
        }

        .grant-filter-field {
            flex-basis: calc(50% - 5px);
        }
    }

    @media (max-width: 600px) {
        .grant-filters {
            order: 3;
        }

        .grant-filter-field {
            flex-basis: 100%;
        }

        .grant-filter-button {
            flex-basis: 100%;
        }
    }
</style>

<script>
    (() => {
        const filterForm = document.currentScript.previousElementSibling.previousElementSibling;
        if (!filterForm) return;

        const dateFrom = filterForm.querySelector('[name="date_from"]');
        const dateTo = filterForm.querySelector('[name="date_to"]');
        const error = filterForm.querySelector('#grant-filter-error');

        filterForm.addEventListener('submit', (event) => {
            const invalidRange = dateFrom.value && dateTo.value && dateFrom.value > dateTo.value;
            error.hidden = !invalidRange;
            if (invalidRange) event.preventDefault();
        });
    })();
</script>