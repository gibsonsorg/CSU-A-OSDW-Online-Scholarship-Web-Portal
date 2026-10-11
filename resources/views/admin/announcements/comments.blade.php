<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Moderate Comments | CSU Grants</title>
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
                <h1>Moderate comments</h1>
                <p>Review comments on this announcement.</p>
            </div>
        </div>
        <main class="content announcement-admin-page">
            <section class="announcement-composer">
                <div class="announcement-admin-copy">{!! nl2br(e($announcement->body)) !!}</div>
            </section>
            <section class="announcement-admin-list">
                @forelse ($comments as $comment)
                    <article class="announcement-moderation-comment">
                        <div>
                            <strong>{{ $comment->user->name }}</strong>
                            <time>{{ $comment->created_at->diffForHumans() }}</time>
                            <p>{!! nl2br(e($comment->body)) !!}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.announcements.comments.destroy', $comment) }}" data-confirm-delete="Delete this comment?">
                            @csrf
                            @method('DELETE')
                            <button class="announcement-danger-button" type="submit">Delete</button>
                        </form>
                    </article>
                @empty
                    <p class="announcement-empty-state">No comments on this announcement.</p>
                @endforelse
                <div class="announcement-pagination">{{ $comments->links() }}</div>
                <a class="announcement-secondary-button" href="{{ route('admin.announcements.index') }}">Back to announcements</a>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/admin-sidebar.js') }}"></script>
    <script src="{{ asset('js/announcements-admin.js') }}" defer></script>
</body>
</html>