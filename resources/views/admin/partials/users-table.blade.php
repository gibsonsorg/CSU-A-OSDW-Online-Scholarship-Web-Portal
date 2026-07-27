<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>Status</th>
            <th>Uploaded ID</th>
            <th>Registered</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->student_id ?? '—' }}</td>
                <td>
                    @if(($user->account_status ?? 'pending') === 'approved')
                        <span class="badge approved">Approved</span>
                    @elseif(($user->account_status ?? 'pending') === 'rejected')
                        <span class="badge rejected">Rejected</span>
                    @else
                        <span class="badge pending">Pending</span>
                    @endif
                </td>
                <td>
                    @if(!empty($user->id_document))
                        <a href="{{ route('admin.users.id_document', ['id' => $user->id]) }}" target="_blank" rel="noopener noreferrer" class="btn small" style="display:inline-block;">View</a>
                    @else
                        <span class="text-gray">No file</span>
                    @endif
                </td>
                <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                <td>
                    @if((int) $user->role !== 0)
                        @if(($user->account_status ?? 'pending') === 'approved')
                            <span class="text-gray">Approved</span>
                        @else
                            <button type="button" class="btn small" onclick="confirmApproveUser({{ $user->id }})">Approve</button>
                            <button type="button" class="btn small" style="background:#e74c3c;color:white;" onclick="rejectUser({{ $user->id }})">Reject</button>
                        @endif
                    @else
                        <span class="text-gray">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-gray">No users yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
