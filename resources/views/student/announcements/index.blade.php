<link rel="stylesheet" href="{{ asset('css/announcements.css') }}">
<x-app-layout>
    <section class="announcement-feed-page">
        <header class="announcement-feed-heading">
            <div>
                <p>CSU Grants</p>
                <h1>Announcements</h1>
            </div>
        </header>

        @forelse ($announcements as $announcement)
            <article class="announcement-feed-card" data-announcement-card data-comment-url="{{ route('student.announcements.comments.index', $announcement) }}" data-comment-post-url="{{ route('student.announcements.comments.store', $announcement) }}" data-comment-delete-base="{{ url('/announcement-comments') }}">
                <header class="announcement-feed-card-header">
                    <span class="announcement-feed-avatar" aria-hidden="true">CS</span>
                    <div class="announcement-feed-author">
                        <strong>CSU Grants Management</strong>
                        <span>Admin · {{ $announcement->created_at->diffForHumans() }}</span>
                    </div>
                    @if ($announcement->pinned_at)
                        <span class="announcement-pinned-label">Pinned</span>
                    @endif
                </header>

                <div class="announcement-feed-copy-wrap">
                    <div class="announcement-feed-copy is-truncated" data-announcement-copy>{!! nl2br(e($announcement->body)) !!}</div>
                    <button class="announcement-see-more" type="button" data-see-more hidden>See more</button>
                </div>

                @if ($announcement->images->isNotEmpty())
                    <div class="announcement-feed-images announcement-feed-images-{{ min($announcement->images->count(), 4) }}">
                        @foreach ($announcement->images as $image)
                            <img src="{{ route('announcements.images.show', $image, absolute: false) }}" alt="Announcement image">
                        @endforeach
                    </div>
                @endif

                <div class="announcement-feed-divider"></div>
                <div class="announcement-comment-summary">
                    <span>{{ $announcement->comments_count }} {{ Str::plural('comment', $announcement->comments_count) }}</span>
                    @if ($announcement->comments_count > 3)
                        <button type="button" data-load-comments data-page="1">View all comments</button>
                    @endif
                </div>
                <div class="announcement-comments" data-comments>
                    @foreach (($commentRows[$announcement->id] ?? collect()) as $comment)
                        <div class="announcement-comment" data-comment-id="{{ $comment->id }}">
                            <div class="announcement-comment-bubble">
                                <strong>{{ $comment->commenter_name }}</strong>
                                <p>{!! nl2br(e($comment->body)) !!}</p>
                            </div>
                            @if ((int) $comment->user_id === (int) auth()->id())
                                <button class="announcement-comment-delete" type="button" data-delete-comment="{{ route('student.announcements.comments.destroy', $comment->id) }}">Delete</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="announcement-comment-error" data-comment-error role="alert" hidden></p>
                <form class="announcement-comment-form" data-comment-form>
                    <label class="visually-hidden" for="announcementComment{{ $announcement->id }}">Write a comment</label>
                    <input id="announcementComment{{ $announcement->id }}" name="body" type="text" maxlength="500" placeholder="Write a comment..." required>
                    <button type="submit">Post</button>
                </form>
            </article>
        @empty
            <div class="announcement-feed-empty">There are no announcements yet.</div>
        @endforelse

        <nav class="announcement-feed-pagination" aria-label="Announcements pages">
            {{ $announcements->links() }}
        </nav>
    </section>
    <script src="{{ asset('js/announcements.js') }}" defer></script>
</x-app-layout>