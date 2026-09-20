<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Scholarship | CSU Grants</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    @include('admin.partials.sidebar')
    <div class="main" id="adminMain">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Edit Scholarship</h1>
                <p>Manage the scholarship information shown to students.</p>
            </div>
        </div>
        <div class="content scholarship-create-page">
            <div class="scholarship-edit-overlay" aria-hidden="false">
                <div class="scholarship-edit-modal" role="dialog" aria-modal="true" aria-labelledby="editScholarshipTitle">
                    <div class="scholarship-create-header scholarship-edit-header">
                        <div>
                            <p class="scholarship-create-eyebrow">Scholarship Management</p>
                            <h2 id="editScholarshipTitle">{{ $scholarship->name }}</h2>
                        </div>
                        <button type="button" class="scholarship-create-close scholarship-edit-close" data-close-edit-scholarship aria-label="Close">×</button>
                    </div>

                    <form method="POST" action="{{ route('admin.scholarships.update', $scholarship) }}" enctype="multipart/form-data" id="scholarshipEditForm">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="type" value="{{ $scholarship->type }}">
                        <input type="hidden" name="status" value="{{ $scholarship->status }}">

                        <div class="scholarship-edit-grid">
                            <div class="scholarship-edit-column">
                                <label>Scholarship logo <input type="file" name="logo" accept="image/jpeg,image/png"></label>
                                <label>Funding Type
                                    <select name="funding_type" required>
                                        <option value="">Select funding type</option>
                                        @if($scholarship->type === 'academic')
                                            <option value="Internationally Funded" @selected(old('funding_type', $scholarship->funding_type) === 'Internationally Funded')>Internationally Funded</option>
                                            <option value="In-house" @selected(old('funding_type', $scholarship->funding_type) === 'In-house')>In-house</option>
                                            <option value="Government-based" @selected(old('funding_type', $scholarship->funding_type) === 'Government-based')>Government-based</option>
                                            <option value="Private based" @selected(old('funding_type', $scholarship->funding_type) === 'Private based')>Private based</option>
                                        @else
                                            <option value="In house" @selected(old('funding_type', $scholarship->funding_type) === 'In house')>In house</option>
                                            <option value="Government based" @selected(old('funding_type', $scholarship->funding_type) === 'Government based')>Government based</option>
                                            <option value="locally funded" @selected(old('funding_type', $scholarship->funding_type) === 'locally funded')>locally funded</option>
                                            <option value="Private" @selected(old('funding_type', $scholarship->funding_type) === 'Private')>Private</option>
                                        @endif
                                    </select>
                                </label>
                            </div>

                            <div class="scholarship-edit-column">
                                <label>Scholarship name <input type="text" name="name" required maxlength="255" value="{{ old('name', $scholarship->name) }}"></label>
                                <label>Scholarship Classification
                                    <select name="scholarship_classification" required>
                                        <option value="">Select classification</option>
                                        @if($scholarship->type === 'academic')
                                            <option value="Academic" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Academic')>Academic</option>
                                            <option value="Merit-based" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Merit-based')>Merit-based</option>
                                            <option value="Locally funded" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Locally funded')>Locally funded</option>
                                            <option value="Local gov't" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === "Local gov't")>Local gov't</option>
                                            <option value="Academic-Merit" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Academic-Merit')>Academic-Merit</option>
                                        @else
                                            <option value="Sport-based" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Sport-based')>Sport-based</option>
                                            <option value="Employee benefit" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Employee benefit')>Employee benefit</option>
                                            <option value="Cultural Arts" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Cultural Arts')>Cultural Arts</option>
                                            <option value="Publication" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Publication')>Publication</option>
                                            <option value="Inclusive/PWD" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Inclusive/PWD')>Inclusive/PWD</option>
                                            <option value="Leadership" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Leadership')>Leadership</option>
                                            <option value="Alumni funded" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Alumni funded')>Alumni funded</option>
                                            <option value="Locally funded" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Locally funded')>Locally funded</option>
                                            <option value="Private" @selected(old('scholarship_classification', $scholarship->scholarship_classification) === 'Private')>Private</option>
                                        @endif
                                    </select>
                                </label>
                            </div>
                        </div>

                        <label class="scholarship-create-full">Types of scholarship <input type="text" name="type_label" required maxlength="255" value="{{ old('type_label', $scholarship->type_label) }}"></label>

                        <label class="scholarship-create-full">Short scholarship information
                            <textarea name="short_information" required maxlength="1000">{{ old('short_information', $scholarship->short_information) }}</textarea>
                        </label>

                        <label class="scholarship-create-full">About this scholarship
                            <textarea name="description" required maxlength="10000">{{ old('description', $scholarship->description) }}</textarea>
                        </label>

                        <div class="scholarship-repeat-section">
                            <div class="scholarship-repeat-heading"><h4>Eligibility requirements</h4><button type="button" class="scholarship-add-row" data-add-row="requirements">+ Add requirement</button></div>
                            <div id="requirementsList" class="scholarship-repeat-list">
                                @foreach(old('requirements', $scholarship->requirements ?: ['']) as $requirement)
                                    <input type="text" name="requirements[]" required value="{{ $requirement }}">
                                @endforeach
                            </div>
                        </div>
                        <div class="scholarship-repeat-section">
                            <div class="scholarship-repeat-heading"><h4>Required documents</h4><button type="button" class="scholarship-add-row" data-add-row="documents">+ Add document</button></div>
                            <div id="documentsList" class="scholarship-repeat-list">
                                @foreach(old('required_documents', $scholarship->required_documents ?: ['']) as $document)
                                    <input type="text" name="required_documents[]" value="{{ $document }}">
                                @endforeach
                            </div>
                        </div>

                        <div class="scholarship-create-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('[data-close-edit-scholarship]')?.addEventListener('click', () => {
                window.location.href = '{{ route('admin.scholarships.create') }}';
            });

            document.querySelectorAll('[data-add-row]').forEach((button) => {
                button.addEventListener('click', () => {
                    const list = document.getElementById(button.dataset.addRow === 'requirements' ? 'requirementsList' : 'documentsList');
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = button.dataset.addRow === 'requirements' ? 'requirements[]' : 'required_documents[]';
                    input.required = button.dataset.addRow === 'requirements';
                    input.placeholder = input.required ? 'Add another eligibility requirement' : 'Add another required document';
                    list.appendChild(input);
                });
            });
        });
    </script>
</body>
</html>
