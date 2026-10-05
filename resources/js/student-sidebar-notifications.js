document.addEventListener('DOMContentLoaded', function () {
    const bellButton = document.querySelector('[data-student-bell]');
    const dialog = document.getElementById('studentNotificationDialog');
    const list = document.getElementById('studentNotificationList');
    const emptyState = document.getElementById('studentNotificationEmpty');
    const countBadge = document.getElementById('studentNotificationCount');
    const bellBadge = document.querySelector('[data-notification-badge]');
    const closeButtons = document.querySelectorAll('[data-close-notifications]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!bellButton || !dialog || !list || !countBadge || !bellBadge) {
        return;
    }

    const syncBellBadge = function (count) {
        const total = Number(count) || 0;
        bellBadge.textContent = String(total);
        bellBadge.hidden = total === 0;
        countBadge.textContent = String(total);
    };

    const formatDateTime = function (value) {
        if (!value) {
            return 'Just now';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return 'Just now';
        }

        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(date);
    };

    const renderNotifications = function (notifications) {
        list.innerHTML = '';

        if (!notifications.length) {
            emptyState.hidden = false;
            list.innerHTML = '';
            syncBellBadge(0);
            return;
        }

        emptyState.hidden = true;
        const unreadCount = notifications.filter(function (notification) {
            return !notification.read_at;
        }).length;
        syncBellBadge(unreadCount);

        notifications.forEach(function (notification) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'student-notification-item' + (notification.read_at ? ' is-read' : '');
            item.dataset.notificationId = notification.id;
            item.dataset.applicationId = notification.application_id || '';

            const status = notification.type === 'approved' ? 'approved' : 'rejected';
            const statusLabel = notification.type === 'approved' ? 'APPROVED' : 'REJECTED';
            const decisionTitle = notification.title || (notification.type === 'approved' ? 'Scholarship Application Approved' : 'Scholarship Application Rejected');
            const scholarshipName = notification.scholarship_name || 'Scholarship';
            const decisionMessage = 'You have an update regarding your application for ' + scholarshipName + '. Please visit the Application Status page for more information.';
            const icon = notification.type === 'approved' ? '✓' : '•';

            item.innerHTML = `
                <span class="student-notification-status ${status}">${icon}</span>
                <span class="student-notification-body">
                    <span class="student-notification-topline">
                        <span class="student-notification-title">${decisionTitle}</span>
                        <span class="student-notification-decision ${status}">${statusLabel}</span>
                    </span>
                    <span class="student-notification-scholarship">${scholarshipName}</span>
                    <span class="student-notification-message">${decisionMessage}</span>
                    <span class="student-notification-meta">
                        <span>${formatDateTime(notification.created_at)}</span>
                        <span>${notification.read_at ? 'Read' : 'Unread'}</span>
                    </span>
                </span>
            `;

            item.addEventListener('click', function () {
                const targetId = item.dataset.notificationId;
                const targetAppId = item.dataset.applicationId;

                if (!targetId) {
                    return;
                }

                fetch('/student/notifications/' + targetId + '/read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({}),
                }).finally(function () {
                    if (targetAppId) {
                        const statusUrl = '/application-status?application_id=' + encodeURIComponent(targetAppId);
                        window.location.href = statusUrl;
                        return;
                    }

                    loadNotifications();
                });
            });

            list.appendChild(item);
        });
    };

    const loadNotifications = function () {
        fetch('/student/notifications', {
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load notifications');
                }
                return response.json();
            })
            .then(function (payload) {
                const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
                notifications.sort(function (left, right) {
                    return new Date(right.created_at || 0) - new Date(left.created_at || 0);
                });
                renderNotifications(notifications);
            })
            .catch(function () {
                renderNotifications([]);
            });
    };

    const openDialog = function () {
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        loadNotifications();
    };

    const closeDialog = function () {
        dialog.hidden = true;
        dialog.setAttribute('aria-hidden', 'true');
    };

    bellButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openDialog();
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeDialog);
    });

    dialog.addEventListener('click', function (event) {
        if (event.target && event.target.matches('[data-close-notifications]')) {
            closeDialog();
            return;
        }

        if (event.target === dialog) {
            closeDialog();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !dialog.hidden) {
            closeDialog();
        }
    });

    loadNotifications();
});
