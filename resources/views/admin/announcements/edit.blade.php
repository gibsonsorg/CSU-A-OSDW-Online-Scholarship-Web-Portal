<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Announcement | CSU Grants</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/announcements.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="announcement-admin-body">
    @include('admin.partials.sidebar')
    <div class="main" id="adminMain">
        @include('admin.partials.sidebar-toggle')
        <div class="topbar">
            <div class="topbar-left">
                <h1>Edit announcement</h1>
                <p>Update the post text and attached images.</p>
            </div>
        </div>
        <main class="content announcement-admin-page">
            @if ($errors->any())
                <div class="announcement-flash announcement-flash-error" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <section class="announcement-composer">
                <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" enctype="multipart/form-data" data-image-form>
                    @csrf
                    @method('PUT')
                    <label class="announcement-field-label" for="announcementBody">Announcement text</label>
                    <textarea id="announcementBody" name="body" maxlength="5000" required rows="7">{{ old('body', $announcement->body) }}</textarea>
                    <h2 class="announcement-existing-images-title">Existing images</h2>
                    @if ($announcement->images->isEmpty())
                        <p class="announcement-muted">This post has no images.</p>
                    @else
                        <div class="announcement-edit-images">
                            @foreach ($announcement->images as $image)
                                <label class="announcement-edit-image">
                                    <img src="{{ route('announcements.images.show', $image, absolute: false) }}" alt="Announcement image">
                                    <span><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" data-remove-image> Remove image</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <div class="announcement-compose-tools">
                        <label class="announcement-file-picker" for="announcementImages">Add images</label>
                        <input id="announcementImages" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple data-image-input data-max-total="4" data-existing-count="{{ $announcement->images->count() }}">
                        <span class="announcement-image-count" data-image-count></span>
                    </div>
                    <div class="announcement-image-previews" data-image-previews></div>
                    <div class="announcement-form-footer">
                        <span>JPG, PNG, or WebP. Up to 4 images total, 2 MB each.</span>
                        <div class="announcement-admin-actions">
                            <a class="announcement-secondary-button" href="{{ route('admin.announcements.index') }}">Cancel</a>
                            <button class="announcement-primary-button" type="submit">Save changes</button>
                        </div>
                    </div>
                </form>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/admin-sidebar.js') }}"></script>
    <script src="{{ asset('js/announcements-admin.js') }}" defer></script>
</body>
</html>