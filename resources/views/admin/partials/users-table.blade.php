<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Student ID</th>
            <th>Role</th>
            <th>Registered</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->student_id ?? '—' }}</td>
                <td>
                    @if((int) $user->role === 0)
                        <span class="badge approved">Admin</span>
                    @else
                        <span class="badge pending">Student</span>
                    @endif
                </td>
                <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-gray">No users yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
