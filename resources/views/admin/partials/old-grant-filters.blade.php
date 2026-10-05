<form class="old-grant-filters" method="GET" action="{{ url()->current() }}">
    <div class="old-grant-filter-field old-grant-search-field">
        <label for="old-grant-search">Search</label>
        <input
            id="old-grant-search"
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search student name"
            aria-label="Search student name"
        >
    </div>

    <div class="old-grant-filter-field old-grant-scholarship-field">
        <label for="old-grant-scholarship-name">Name of Scholarship</label>
        <select id="old-grant-scholarship-name" name="scholarship_name" aria-label="Filter by scholarship name">
            <option value="" @selected(request('scholarship_name', '') === '')>All Scholarships</option>
            @foreach($scholarshipNames as $scholarshipName)
                <option value="{{ $scholarshipName }}" @selected(request('scholarship_name') === $scholarshipName)>{{ $scholarshipName }}</option>
            @endforeach
        </select>
    </div>

    <div class="old-grant-filter-field old-grant-year-field">
        <label for="old-grant-year-level">Year Level</label>
        <select id="old-grant-year-level" name="year_level" aria-label="Filter by year level">
            <option value="" @selected(request('year_level', '') === '')>All Year Levels</option>
            @foreach($yearLevels as $yearLevel)
                <option value="{{ $yearLevel }}" @selected(request('year_level') === $yearLevel)>{{ $yearLevel }}</option>
            @endforeach
        </select>
    </div>

    <div class="old-grant-filter-field old-grant-course-field">
        <label for="old-grant-course">Course</label>
        <select id="old-grant-course" name="course" aria-label="Filter by course">
            <option value="" @selected(request('course', '') === '')>All Courses</option>
            @foreach($courses as $course)
                <option value="{{ $course }}" @selected(request('course') === $course)>{{ $course }}</option>
            @endforeach
        </select>
    </div>

    <button class="old-grant-filter-button" type="submit">Filter</button>
</form>

<style>
    .old-grant-filters {
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

    .old-grant-filter-field {
        display: flex;
        flex: 1 1 0;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .old-grant-search-field {
        flex: 1.7 1 220px;
    }

    .old-grant-scholarship-field {
        flex: 1.5 1 180px;
    }

    .old-grant-year-field,
    .old-grant-course-field {
        flex: 1.2 1 150px;
    }

    .old-grant-filter-field label {
        color: #687386;
        font-size: 0.68rem;
        font-weight: 600;
    }

    .old-grant-filter-field input,
    .old-grant-filter-field select {
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

    .old-grant-filter-button {
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

    .old-grant-filter-button:hover {
        background: #6b0f1a;
    }

    @media (max-width: 900px) {
        .old-grant-filters {
            flex-wrap: wrap;
        }

        .old-grant-filter-field {
            flex-basis: calc(50% - 5px);
        }
    }

    @media (max-width: 600px) {
        .old-grant-filters {
            order: 3;
        }

        .old-grant-filter-field {
            flex-basis: 100%;
        }

        .old-grant-filter-button {
            flex-basis: 100%;
        }
    }
</style>
