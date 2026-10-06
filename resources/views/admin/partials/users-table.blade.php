<table class="registered-users-table">
    <thead>
        <tr>
            <th>Email</th>
            <th>Status</th>
            <th>Registered</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->email ?? '—' }}</td>
                <td>
                    @if(($user->account_status ?? 'pending') === 'approved')
                        <span class="badge approved">Approved</span>
                    @elseif(($user->account_status ?? 'pending') === 'rejected')
                        <span class="badge rejected">Rejected</span>
                    @else
                        <span class="badge pending">Pending</span>
                    @endif
                </td>
                <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-gray">No users yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<style>
    .registered-users-table {
        width: min(100%, 760px);
        max-width: 100%;
        table-layout: fixed;
        margin-right: auto;
    }

    .registered-users-table th:nth-child(1),
    .registered-users-table td:nth-child(1) {
        width: 48%;
    }

    .registered-users-table th:nth-child(2),
    .registered-users-table td:nth-child(2) {
        width: 22%;
        text-align: center;
    }

    .registered-users-table th:nth-child(3),
    .registered-users-table td:nth-child(3) {
        width: 30%;
        white-space: nowrap;
    }

    @media (max-width: 600px) {
        .registered-users-table {
            width: 100%;
            table-layout: auto;
        }

        .registered-users-table th:nth-child(1),
        .registered-users-table td:nth-child(1) {
            width: auto;
        }

        .registered-users-table th:nth-child(2),
        .registered-users-table td:nth-child(2) {
            width: auto;
            white-space: nowrap;
        }

        .registered-users-table th:nth-child(3),
        .registered-users-table td:nth-child(3) {
            width: auto;
            white-space: nowrap;
        }
    }
</style>
