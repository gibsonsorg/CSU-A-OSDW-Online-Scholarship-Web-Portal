<form id="summary-report-filters" class="grant-filters" method="GET" action="{{ route('admin.dashboard') }}">
    <div class="grant-filter-field">
        <label for="summary-date-from">Date From</label>
        <input id="summary-date-from" type="date" name="date_from" value="{{ request('date_from') }}">
    </div>
    <div class="grant-filter-field">
        <label for="summary-date-to">Date To</label>
        <input id="summary-date-to" type="date" name="date_to" value="{{ request('date_to') }}">
    </div>
    <div class="grant-filter-field">
        <label for="summary-grant-type">Type of Scholarship</label>
        <select id="summary-grant-type" name="grant_type">
            <option value="" @selected(($scholarshipType ?? '') === '')>Select Type</option>
            <option value="academic" @selected(($scholarshipType ?? '') === 'academic')>Academic</option>
            <option value="non-academic" @selected(($scholarshipType ?? '') === 'non-academic')>Non-Academic</option>
        </select>
    </div>
    <div class="grant-filter-field">
        <label for="summary-scholarship-name">Name of Scholarship</label>
        <select id="summary-scholarship-name" name="scholarship_name">
            <option value="">All Scholarships</option>
            @foreach($scholarshipNames as $scholarship)
                <option value="{{ $scholarship->name }}" data-grant-type="{{ $scholarship->type }}" @selected(request('scholarship_name') === $scholarship->name)>{{ $scholarship->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="grant-filter-field">
        <label for="summary-status">Status</label>
        <select id="summary-status" aria-label="Status" disabled>
            <option value="approved" selected>Approved</option>
        </select>
    </div>
    <button class="grant-filter-button" type="submit">Filter</button>
    <button class="grant-filter-button" type="submit" formaction="{{ route('admin.dashboard.export') }}">Download</button>
    <button class="grant-filter-button add-amount-button" type="button" data-action="add-amount" data-grant-type="all">Add Amount</button>
    <p class="grant-filter-error" id="summary-filter-error" role="alert" hidden>Date From must be before or equal to Date To.</p>
</form>

<style>
    #summary-report-filters {
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

    #summary-report-filters .grant-filter-field {
        display: flex;
        flex: 1 1 140px;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    #summary-report-filters .grant-filter-field label {
        color: #687386;
        font-size: 0.68rem;
        font-weight: 600;
    }

    #summary-report-filters .grant-filter-field input,
    #summary-report-filters .grant-filter-field select {
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

    #summary-report-filters .grant-filter-field select:disabled {
        color: #273142;
        opacity: 1;
    }

    #summary-report-filters .grant-filter-button {
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

    #summary-report-filters .grant-filter-button:hover {
        background: #6b0f1a;
    }

    #summary-report-filters .grant-filter-error {
        flex-basis: 100%;
        margin: 0;
        color: #c0392b;
        font-size: 0.68rem;
    }

    @media (max-width: 900px) {
        #summary-report-filters {
            flex-wrap: wrap;
        }

        #summary-report-filters .grant-filter-field {
            flex-basis: calc(50% - 5px);
        }
    }

    @media (max-width: 600px) {
        #summary-report-filters {
            order: 3;
        }

        #summary-report-filters .grant-filter-field,
        #summary-report-filters .grant-filter-button {
            flex-basis: 100%;
        }
    }
</style>

<script>
    (() => {
        const filterForm = document.getElementById('summary-report-filters');
        if (!filterForm) return;

        const typeSelect = filterForm.querySelector('[name="grant_type"]');
        const nameSelect = filterForm.querySelector('[name="scholarship_name"]');
        const allOptions = Array.from(nameSelect.options).slice(1).map(option => ({
            value: option.value,
            label: option.textContent,
            type: option.dataset.grantType,
        }));
        let selectedName = nameSelect.value;

        const updateScholarshipOptions = () => {
            const selectedType = typeSelect.value;
            const available = allOptions.filter(option => !selectedType || option.type === selectedType);
            nameSelect.replaceChildren(new Option('All Scholarships', ''));
            available.forEach(option => nameSelect.add(new Option(option.label, option.value)));
            nameSelect.value = available.some(option => option.value === selectedName) ? selectedName : '';
        };

        typeSelect.addEventListener('change', () => {
            selectedName = '';
            updateScholarshipOptions();
        });
        updateScholarshipOptions();
        filterForm.addEventListener('submit', event => {
            const dateFrom = filterForm.querySelector('[name="date_from"]').value;
            const dateTo = filterForm.querySelector('[name="date_to"]').value;
            const error = filterForm.querySelector('#summary-filter-error');
            const invalidRange = dateFrom && dateTo && dateFrom > dateTo;
            error.hidden = !invalidRange;
            if (invalidRange) event.preventDefault();
        });
    })();
</script>
