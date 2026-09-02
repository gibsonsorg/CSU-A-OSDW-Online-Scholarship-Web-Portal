<form class="grant-filters" method="GET" action="{{ route('admin.dashboard') }}">
    <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search"
        aria-label="Search applications"
    >
    <select name="status" aria-label="Filter applications by status">
        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
    </select>
</form>

<style>
    .grant-filters {
        display: flex;
        gap: 12px;
        margin: 0;
    }

    .grant-filters input,
    .grant-filters select {
        height: 32px;
        border: 1px solid #dfe4ec;
        border-radius: 10px;
        background: #fff;
        color: #273142;
        font: inherit;
        font-size: 0.78rem;
        padding: 0 12px;
        box-sizing: border-box;
    }

    .grant-filters input::placeholder {
        font-size: 0.78rem;
        color: #687386;
        opacity: 1;
    }

    .grant-filters select option {
        font-size: 0.78rem;
    }

    .grant-filters input {
        width: min(100%, 200px);
    }

    .grant-filters select {
        width: 104px;
        cursor: pointer;
        border-width: 2px;
        border-color: #273142;
        padding: 0 16px;
    }

    .grant-filters select:hover {
        background-color: #f8f9fb;
    }

    @media (max-width: 600px) {
        .grant-filters {
            order: 3;
            width: 100%;
            margin-top: 12px;
        }

        .grant-filters input,
        .grant-filters select {
            flex: 1;
            width: auto;
        }
    }
</style>

<script>
    (() => {
        const filterForm = document.currentScript.previousElementSibling.previousElementSibling.previousElementSibling;
        if (!filterForm) return;

        let timer;
        filterForm.querySelector('input[name="search"]').addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => filterForm.requestSubmit(), 300);
        });
        filterForm.querySelector('select[name="status"]').addEventListener('change', () => filterForm.requestSubmit());
    })();
</script>
