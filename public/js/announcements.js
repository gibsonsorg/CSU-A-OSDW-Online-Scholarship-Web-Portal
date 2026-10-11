document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const escapeNewlines = value => String(value ?? '');

    if (window.matchMedia('(max-width: 768px)').matches) {
        const shell = document.getElementById('studentAppShell');
        const sidebar = document.getElementById('studentSidebar');
        const toggle = document.getElementById('studentSidebarToggle');
        if (shell && sidebar && toggle && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
            shell.classList.add('sidebar-collapsed');
            toggle.classList.add('active');
        }
    }

    const showError = (card, message) => {
        const error = card.querySelector('[data-comment-error]');
        error.textContent = message;
        error.hidden = false;
    };

    const buildComment = (comment, card) => {
        const row = document.createElement('div');
        row.className = 'announcement-comment';
        row.dataset.commentId = comment.id;
        const bubble = document.createElement('div');
        bubble.className = 'announcement-comment-bubble';
        const author = document.createElement('strong');
        author.textContent = comment.name;
        const body = document.createElement('p');
        body.textContent = escapeNewlines(comment.body);
        bubble.append(author, body);
        row.append(bubble);

        if (comment.can_delete || comment.is_own) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'announcement-comment-delete';
            remove.textContent = 'Delete';
            remove.dataset.deleteComment = `${card.dataset.commentDeleteBase}/${comment.id}`;
            row.append(remove);
        }
        return row;
    };

    document.querySelectorAll('[data-announcement-card]').forEach(card => {
        const copy = card.querySelector('[data-announcement-copy]');
        const more = card.querySelector('[data-see-more]');
        if (copy && more) {
            requestAnimationFrame(() => {
                if (copy.scrollHeight > copy.clientHeight + 1) more.hidden = false;
            });
            more.addEventListener('click', () => {
                const expanded = copy.classList.toggle('is-truncated') === false;
                more.textContent = expanded ? 'See less' : 'See more';
            });
        }

        const loadButton = card.querySelector('[data-load-comments]');
        loadButton?.addEventListener('click', async () => {
            if (loadButton.disabled) return;
            loadButton.disabled = true;
            try {
                const page = Number(loadButton.dataset.page || 1);
                const response = await fetch(`${card.dataset.commentUrl}?page=${page}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Comments could not be loaded.');
                const container = card.querySelector('[data-comments]');
                const fragment = document.createDocumentFragment();
                const existingIds = new Set(Array.from(container.querySelectorAll('[data-comment-id]'), item => String(item.dataset.commentId)));
                data.comments.forEach(comment => {
                    if (existingIds.has(String(comment.id))) return;
                    existingIds.add(String(comment.id));
                    fragment.append(buildComment(comment, card));
                });
                container.prepend(fragment);
                if (data.has_more) {
                    loadButton.dataset.page = String(page + 1);
                    loadButton.disabled = false;
                } else {
                    loadButton.remove();
                }
            } catch (error) {
                showError(card, error.message || 'Comments could not be loaded.');
                loadButton.disabled = false;
            }
        });

        const form = card.querySelector('[data-comment-form]');
        form?.addEventListener('submit', async event => {
            event.preventDefault();
            const input = form.elements.body;
            const body = input.value.trim();
            const button = form.querySelector('button[type="submit"]');
            if (!body) return showError(card, 'Write a comment before posting.');
            if (body.length > 500) return showError(card, 'Comments cannot exceed 500 characters.');

            button.disabled = true;
            card.querySelector('[data-comment-error]').hidden = true;
            try {
                const response = await fetch(card.dataset.commentPostUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ body })
                });
                const data = await response.json();
                if (!response.ok) {
                    const firstError = Object.values(data.errors || {})[0]?.[0];
                    throw new Error(firstError || data.message || 'Your comment could not be posted.');
                }
                data.comment.is_own = true;
                card.querySelector('[data-comments]').append(buildComment(data.comment, card));
                input.value = '';
                const count = card.querySelector('.announcement-comment-summary > span');
                const nextCount = Number((count.textContent.match(/\d+/) || [0])[0]) + 1;
                count.textContent = `${nextCount} ${nextCount === 1 ? 'comment' : 'comments'}`;
                const loadControl = card.querySelector('[data-load-comments]');
                if (loadControl) loadControl.hidden = false;
            } catch (error) {
                showError(card, error.message || 'Your comment could not be posted.');
            } finally {
                button.disabled = false;
            }
        });
    });

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-delete-comment]');
        if (!button) return;
        const card = button.closest('[data-announcement-card]');
        const confirmed = window.Swal
            ? (await Swal.fire({ title: 'Delete your comment?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#7a1f1f' })).isConfirmed
            : window.confirm('Delete your comment?');
        if (!confirmed) return;

        button.disabled = true;
        try {
            const response = await fetch(button.dataset.deleteComment, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Your comment could not be deleted.');
            button.closest('[data-comment-id]').remove();
            const count = card.querySelector('.announcement-comment-summary > span');
            const nextCount = Math.max(0, Number((count.textContent.match(/\d+/) || [0])[0]) - 1);
            count.textContent = `${nextCount} ${nextCount === 1 ? 'comment' : 'comments'}`;
        } catch (error) {
            showError(card, error.message || 'Your comment could not be deleted.');
            button.disabled = false;
        }
    });
});