<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>School ID</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->student_id }}</td>
            <td>
                @if($user->id_document)
                    <button class="view-id-btn" onclick="viewSchoolID('{{ asset('storage/' . $user->id_document) }}')" style="background:none;border:none;color:#0066cc;cursor:pointer;text-decoration:underline;padding:0;">View</button>
                @else
                    <span style="color:#999;">-</span>
                @endif
            </td>
            <td>
                @php
                    $status = $user->account_status ?? 'pending';
                    $badgeClass = '';
                    if($status === 'approved') $badgeClass = 'approved';
                    elseif($status === 'rejected') $badgeClass = 'rejected';
                    else $badgeClass = 'pending';
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
            </td>
            <td>
                @if($user->account_status === 'pending')
                    <button class="btn small" onclick="approveUser({{ $user->id }})" style="background:#27ae60;color:white;padding:6px 10px;border:none;border-radius:4px;cursor:pointer;margin-right:3px;font-size:12px;">Approve</button>
                    <button class="btn small" onclick="rejectUser({{ $user->id }})" style="background:#e74c3c;color:white;padding:6px 10px;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Reject</button>
                @else
                    <button class="btn small" style="background:#ccc;color:#666;padding:6px 10px;border:none;border-radius:4px;cursor:default;font-size:12px;" disabled>{{ ucfirst($status) }}</button>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" style="text-align:center;color:#999;padding:20px;">No users found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
