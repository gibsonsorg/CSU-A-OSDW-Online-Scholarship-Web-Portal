<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Scholarship | CSU Grants</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    @include('admin.partials.sidebar')

    <div class="main" id="adminMain">
        @include('admin.partials.sidebar-toggle')

        @php
            $studentDashboardScholarshipLogos = [
                'Athletic Scholarship' => asset('images/athletic scholarship.logo.png'),
                "Employees' Grant" => asset('images/employees grant.logo.png'),
                'Socio-Cultural Scholarship' => asset('images/csu.logo.png'),
                'Campus Student Publication' => asset('images/campus stud. publication.jpg'),
                'Differently Abled Persons' => asset('images/dap.logo.png'),
                'Student Council Federation Grant' => asset('images/campus stud.council.png'),
                'CSU Aparri Alumni Association' => asset('images/csu alumni assoc.logo.jpg'),
                'Agkaykaysa' => asset('images/agkaykaysa.logo.png'),
                'Tulong Dunong Program' => asset('images/ched.logo.png'),
                'Open Heart Scholarship' => asset('images/open heart.logo.png'),
                'Tertiary Education Subsidy' => asset('images/tes logo.png'),
                'Cagayan Valley Assoc. of Hawaii' => asset('images/cagayan valley assoc. hawaii logo.png'),
                'DOST SEI Project Strand' => asset('images/dost.logo.png'),
                'LGU Aparri' => asset('images/lgu.logo.jpg'),
                'Natalged A Lasam' => asset('images/lgu lasam.logo.png'),
                'Andres Tamayo Scholarship' => asset('images/andres tamayo.logo.jpg'),
                'Gokongwei Brothers Foundation' => asset('images/gokongwei logo.png'),
                'University Based Scholarship' => asset('images/csu.logo.png'),
                'ACEF – GIAHEP' => asset('images/acef-giahep scholarship logo.png'),
            ];
        @endphp

        <div class="topbar">
            <div class="topbar-left">
                <h1>Add Scholarship</h1>
                <p>Create a scholarship that students can apply for.</p>
            </div>
        </div>

        <div class="content scholarship-create-page">
            <div class="scholarship-category-grid">
                <button type="button" class="scholarship-category-card" data-scholarship-category="academic">
                    <span>Academic Grants</span><strong aria-hidden="true">+</strong>
                </button>
                <button type="button" class="scholarship-category-card" data-scholarship-category="non-academic">
                    <span>Non-Academic Grants</span><strong aria-hidden="true">+</strong>
                </button>
            </div>

            <section class="scholarship-management" aria-labelledby="scholarshipManagementTitle">
                <div class="scholarship-management-header">
                    <div>
                        <h2 id="scholarshipManagementTitle"> Scholarship Management</h2>
                        <p>View, edit and manage all scholarships.</p>
                    </div>
                    <form method="GET" action="{{ route('admin.scholarships.create') }}" class="scholarship-management-filters">
                        <input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search scholarship..." aria-label="Search scholarships">
                        <select name="type" aria-label="Filter scholarships by type" onchange="this.form.submit()">
                            <option value="all" @selected(($type ?? 'all') === 'all')>All Types</option>
                            <option value="academic" @selected(($type ?? '') === 'academic')>Academic Grants</option>
                            <option value="non-academic" @selected(($type ?? '') === 'non-academic')>Non-Academic Grants</option>
                        </select>
                        <button type="submit" class="btn">Search</button>
                    </form>
                </div>
                <div class="scholarship-management-table-wrap">
                    <table class="scholarship-management-table">
                        <thead><tr><th>Scholarship</th><th>Type</th><th>Category</th><th>Status</th><th>Date Added</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($scholarships ?? [] as $scholarship)
                                <tr>
                                    <td class="scholarship-management-name">
                                        <div class="scholarship-management-identity">
                                            @php
                                                $scholarshipLogo = $scholarship->logo_path
                                                    ? route('scholarships.logo', ['scholarship' => $scholarship, 'v' => $scholarship->updated_at?->timestamp], false)
                                                    : ($studentDashboardScholarshipLogos[$scholarship->name] ?? asset('images/csu.logo.png'));
                                            @endphp
                                            <img src="{{ $scholarshipLogo }}" alt="{{ $scholarship->name }} logo" onerror="this.onerror=null;this.src='{{ asset('images/csu.logo.png') }}'">
                                            <div><strong>{{ $scholarship->name }}</strong><span>{{ Str::limit($scholarship->short_information ?: $scholarship->description, 95) }}</span></div>
                                        </div>
                                    </td>
                                    <td><span class="scholarship-type-badge">{{ $scholarship->type_label ?: 'Scholarship' }}</span></td>
                                    <td>{{ $scholarship->type === 'academic' ? 'Academic Grants' : 'Non-Academic Grants' }}</td>
                                    <td>
                                        <select class="scholarship-status-select {{ $scholarship->status === 'active' ? 'is-active' : 'is-inactive' }}" data-status-url="{{ route('admin.scholarships.status', $scholarship) }}" data-original-status="{{ $scholarship->status ?: 'active' }}" aria-label="Status for {{ $scholarship->name }}">
                                            <option value="active" @selected(($scholarship->status ?: 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected($scholarship->status === 'inactive')>Inactive</option>
                                        </select>
                                    </td>
                                    <td>{{ optional($scholarship->created_at)->format('M d, Y') }}</td>
                                    <td><div class="scholarship-management-actions"><a class="btn small" href="{{ route('admin.scholarships.edit', $scholarship) }}">Edit</a><form method="POST" action="{{ route('admin.scholarships.destroy', $scholarship) }}" data-scholarship-delete-form>@csrf @method('DELETE')<button type="submit" class="btn small scholarship-delete-button">Delete</button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="scholarship-management-empty">No scholarships found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($scholarships) && $scholarships->hasPages())
                    {{ $scholarships->links('vendor.pagination.admin') }}
                @endif
            </section>

            <div class="scholarship-create-modal" id="scholarshipCreateModal" aria-hidden="true">
                <div class="scholarship-create-dialog" role="dialog" aria-modal="true" aria-labelledby="scholarshipCreateTitle">
                    <div class="scholarship-create-header">
                        <div>
                            <p class="scholarship-create-eyebrow">New scholarship</p>
                            <h2 id="scholarshipCreateTitle">Create Scholarship</h2>
                        </div>
                        <button type="button" class="scholarship-create-close" data-close-scholarship-modal aria-label="Close">×</button>
                    </div>

                    <div class="scholarship-create-progress"><span id="scholarshipCreateStepLabel">1 / 2</span></div>
                    <form method="POST" action="{{ route('admin.scholarships.store') }}" enctype="multipart/form-data" id="scholarshipCreateForm">
                        @csrf
                        <input type="hidden" name="type" id="scholarshipCategory">

                        <section class="scholarship-create-step is-active" data-scholarship-step="1">
                            <h3>Basic information</h3>
                            <div class="scholarship-create-fields">
                                <label>Scholarship logo <input type="file" name="logo" accept="image/jpeg,image/png"></label>
                                <label>Scholarship name <input type="text" name="name" required maxlength="255" value="{{ old('name') }}"></label>
                                <label>Funding Type
                                    <select name="funding_type" required id="fundingTypeSelect">
                                        <option value="">Select funding type</option>
                                    </select>
                                </label>
                                <label>Scholarship Classification
                                    <select name="scholarship_classification" required id="scholarshipClassificationSelect">
                                        <option value="">Select classification</option>
                                    </select>
                                </label>
                                <label>Types of scholarship <input type="text" name="type_label" required maxlength="255" value="{{ old('type_label') }}"></label>
                                <label class="scholarship-create-full">Short scholarship information <textarea name="short_information" required maxlength="1000">{{ old('short_information') }}</textarea></label>
                            </div>
                        </section>

                        <section class="scholarship-create-step" data-scholarship-step="2">
                            <h3>Scholarship details</h3>
                            <label>About this scholarship <textarea name="description" required maxlength="10000">{{ old('description') }}</textarea></label>
                            <div class="scholarship-repeat-section">
                                <div class="scholarship-repeat-heading"><h4>Eligibility requirements</h4><button type="button" class="scholarship-add-row" data-add-row="requirements">+ Add requirement</button></div>
                                <div id="requirementsList" class="scholarship-repeat-list"><input type="text" name="requirements[]" required placeholder="Example: Must be officially enrolled."></div>
                            </div>
                            <div class="scholarship-repeat-section">
                                <div class="scholarship-repeat-heading"><h4>Required documents</h4><button type="button" class="scholarship-add-row" data-add-row="documents">+ Add document</button></div>
                                <div id="documentsList" class="scholarship-repeat-list"><input type="text" name="required_documents[]" placeholder="Example: Certificate of Enrollment"></div>
                            </div>
                        </section>

                        <div class="scholarship-create-actions">
                            
                            <button type="button" class="btn" id="scholarshipCreatePrevious">Previous</button>
                            <span class="scholarship-create-spacer"></span>
                            <button type="button" class="btn btn-primary" id="scholarshipCreateNext">Next </button>
                            <button type="submit" class="btn btn-primary" id="scholarshipCreateSubmit">Add Scholarship</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'success', title: 'Scholarship added', text: @json(session('success')), confirmButtonColor: '#6b0f1a' }));</script>
    @endif
    @if($errors->any())
        <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'error', title: 'Check the form', text: @json($errors->first()), confirmButtonColor: '#6b0f1a' }));</script>
    @endif
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (!sidebar || !toggle) return;
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                toggle.classList.toggle('active');
            });
        });
    </script>

    <script>
        document.querySelectorAll('[data-scholarship-delete-form]').forEach((form) => form.addEventListener('submit', (event) => {
            event.preventDefault();
            Swal.fire({ title: 'Delete scholarship?', text: 'This scholarship will be permanently deleted. This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, Delete', cancelButtonText: 'No, Cancel', confirmButtonColor: '#6b0f1a' }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }));

        document.querySelectorAll('.scholarship-status-select').forEach((select) => select.addEventListener('change', async () => {
            const originalStatus = select.dataset.originalStatus;
            const nextStatus = select.value;
            const isActivating = nextStatus === 'active';
            const result = await Swal.fire({
                title: isActivating ? 'Are you sure you want to make this scholarship active?' : 'Are you sure?',
                text: isActivating ? 'This scholarship will become available to students again.' : 'This scholarship will become unavailable to students.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#6b0f1a',
            });

            if (!result.isConfirmed) {
                select.value = originalStatus;
                return;
            }

            select.disabled = true;
            try {
                const response = await fetch(select.dataset.statusUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ status: nextStatus }),
                });

                if (!response.ok) throw new Error('Status update failed');

                select.dataset.originalStatus = nextStatus;
                select.classList.toggle('is-active', nextStatus === 'active');
                select.classList.toggle('is-inactive', nextStatus === 'inactive');
            } catch (error) {
                select.value = originalStatus;
                await Swal.fire({ icon: 'error', title: 'Unable to update scholarship status.', text: 'Please try again.', confirmButtonColor: '#6b0f1a' });
            } finally {
                select.disabled = false;
            }
        }));

        const scholarshipModal = document.getElementById('scholarshipCreateModal');
        const scholarshipForm = document.getElementById('scholarshipCreateForm');
        const scholarshipSteps = [...document.querySelectorAll('[data-scholarship-step]')];
        const scholarshipPrevious = document.getElementById('scholarshipCreatePrevious');
        const scholarshipNext = document.getElementById('scholarshipCreateNext');
        const scholarshipSubmit = document.getElementById('scholarshipCreateSubmit');
        let scholarshipStep = 1;

        function updateScholarshipStep() {
            scholarshipSteps.forEach((step) => step.classList.toggle('is-active', Number(step.dataset.scholarshipStep) === scholarshipStep));
            document.getElementById('scholarshipCreateStepLabel').textContent = `${scholarshipStep} / 2`;
            scholarshipPrevious.hidden = scholarshipStep === 1;
            scholarshipNext.hidden = scholarshipStep === 2;
            scholarshipSubmit.hidden = scholarshipStep === 1;
        }

        function renderSelectOptions(select, label, options, selectedValue = '') {
            select.innerHTML = `<option value="">${label}</option>` + options.map((option) => {
                const isSelected = option === selectedValue ? 'selected' : '';
                return `<option value="${option}" ${isSelected}>${option}</option>`;
            }).join('');
        }

        function openScholarshipModal(category) {
            document.getElementById('scholarshipCategory').value = category;
            const fundingSelect = document.getElementById('fundingTypeSelect');
            const classificationSelect = document.getElementById('scholarshipClassificationSelect');
            const isAcademic = category === 'academic';

            const academicFunding = ['Internationally Funded', 'In-house', 'Government-based', 'Private based'];
            const nonAcademicFunding = ['In house', 'Government based', 'locally funded', 'Private'];
            const academicClassification = ['Academic', 'Merit-based', 'Locally funded', "Local gov't", 'Academic-Merit'];
            const nonAcademicClassification = ['Sport-based', 'Employee benefit', 'Cultural Arts', 'Publication', 'Inclusive/PWD', 'Leadership', 'Alumni funded', 'Locally funded', 'Private'];

            renderSelectOptions(fundingSelect, 'Select funding type', isAcademic ? academicFunding : nonAcademicFunding);
            renderSelectOptions(classificationSelect, 'Select classification', isAcademic ? academicClassification : nonAcademicClassification);

            scholarshipStep = 1;
            updateScholarshipStep();
            scholarshipModal.classList.add('is-open');
            scholarshipModal.setAttribute('aria-hidden', 'false');
        }

        function closeScholarshipModal() {
            scholarshipModal.classList.remove('is-open');
            scholarshipModal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('[data-scholarship-category]').forEach((button) => button.addEventListener('click', () => openScholarshipModal(button.dataset.scholarshipCategory)));
        document.querySelectorAll('[data-close-scholarship-modal]').forEach((button) => button.addEventListener('click', closeScholarshipModal));
        scholarshipNext.addEventListener('click', () => {
            const firstStepFields = scholarshipSteps[0].querySelectorAll('input[required], select[required], textarea[required]');
            if ([...firstStepFields].every((field) => field.reportValidity())) { scholarshipStep = 2; updateScholarshipStep(); }
        });
        scholarshipPrevious.addEventListener('click', () => { scholarshipStep = 1; updateScholarshipStep(); });
        document.querySelectorAll('[data-add-row]').forEach((button) => button.addEventListener('click', () => {
            const list = document.getElementById(button.dataset.addRow === 'requirements' ? 'requirementsList' : 'documentsList');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = button.dataset.addRow === 'requirements' ? 'requirements[]' : 'required_documents[]';
            input.placeholder = button.dataset.addRow === 'requirements' ? 'Add another eligibility requirement' : 'Add another required document';
            input.required = button.dataset.addRow === 'requirements';
            list.appendChild(input);
        }));
        scholarshipForm.addEventListener('submit', (event) => {
            if (scholarshipStep !== 2) { event.preventDefault(); scholarshipStep = 2; updateScholarshipStep(); }
        });
        updateScholarshipStep();
    </script>
</body>
</html>
