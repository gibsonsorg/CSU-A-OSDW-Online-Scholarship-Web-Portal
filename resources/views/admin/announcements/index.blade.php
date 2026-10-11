<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Announcements | CSU Grants</title>
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
                <h1>Announcements</h1>
                <p>Publish updates for all approved students.</p>
            </div>
        </div>

        <main class="content announcement-admin-page">
            @if (session('success'))
                <div class="announcement-flash announcement-flash-success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="announcement-flash announcement-flash-error" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section class="announcement-composer" aria-labelledby="announcementComposerTitle">
                <h2 id="announcementComposerTitle">Create announcement</h2>
                <form method="POST" action="{{ route('admin.announcements.store') }}" enctype="multipart/form-data" data-image-form>
                    @csrf
                    <label class="announcement-field-label" for="announcementBody">Announcement text</label>
                    <textarea id="announcementBody" name="body" maxlength="5000" required rows="5" placeholder="Share an update with students...">{{ old('body') }}</textarea>
                    <div class="announcement-compose-tools">
                        <label class="announcement-file-picker" for="announcementImages">Add images</label>
                        <input id="announcementImages" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple data-image-input data-max-total="4">
                        <span class="announcement-image-count" data-image-count>0 / 4 images</span>
                    </div>
                    <div class="announcement-image-previews" data-image-previews></div>
                    <div class="announcement-form-footer">
                        <span>JPG, PNG, or WebP. Up to 4 images, 2 MB each.</span>
                        <button class="announcement-primary-button" type="submit">Post</button>
                    </div>
                </form>
            </section>

            <section class="announcement-admin-list" aria-labelledby="announcementListTitle">
                <div class="announcement-list-heading">
                    <h2 id="announcementListTitle">Published posts</h2>
                    <span>{{ $announcements->total() }} total</span>
                </div>

                @forelse ($announcements as $announcement)
                    <article class="announcement-admin-post">
                        <header class="announcement-admin-post-header">
                            <div>
                                <strong>CSU Grants Management</strong>
                                <span>{{ $announcement->created_at->diffForHumans() }}</span>
                            </div>
                            @if ($announcement->pinned_at)
                                <span class="announcement-pinned-label">Pinned</span>
                            @endif
                        </header>
                        <div class="announcement-admin-copy">{!! nl2br(e($announcement->body)) !!}</div>
                        @if ($announcement->images->isNotEmpty())
                            <div class="announcement-admin-images announcement-admin-images-{{ min($announcement->images->count(), 4) }}">
                                @foreach ($announcement->images as $image)
                                    <img src="{{ route('announcements.images.show', $image, absolute: false) }}" alt="Announcement image">
                                @endforeach
                            </div>
                        @endif
                        <div class="announcement-admin-post-footer">
                            <span>{{ $announcement->comments_count }} {{ Str::plural('comment', $announcement->comments_count) }}</span>
                            <div class="announcement-admin-actions">
                                <a class="announcement-secondary-button" href="{{ route('admin.announcements.comments', $announcement) }}">Moderate comments</a>
                                <a class="announcement-secondary-button" href="{{ route('admin.announcements.edit', $announcement) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.announcements.pin', $announcement) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="announcement-secondary-button" type="submit">{{ $announcement->pinned_at ? 'Unpin' : 'Pin' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" data-confirm-delete="Delete this announcement and its images and comments?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="announcement-danger-button" type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="announcement-empty-state">No announcements have been posted.</p>
                @endforelse

                <div class="announcement-pagination">{{ $announcements->links() }}</div>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/admin-sidebar.js') }}"></script>
    <script src="{{ asset('js/announcements-admin.js') }}" defer></script>
</body>
</html>