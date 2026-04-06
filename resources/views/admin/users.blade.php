<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Users</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .container { padding: 24px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:10px; border-bottom:1px solid #eee; text-align:left }
        img { border-radius:6px }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registered Users</h1>
        <p><a href="{{ route('admin.dashboard') }}">← Back to Dashboard</a></p>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>School ID</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->student_id }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->id_document)
                            @php $ext = pathinfo($user->id_document, PATHINFO_EXTENSION); @endphp
                            @if(in_array(strtolower($ext), ['jpg','jpeg','png']))
                                <a href="{{ asset('storage/' . $user->id_document) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $user->id_document) }}" alt="ID" style="max-width:120px;max-height:120px;" />
                                </a>
                            @else
                                <a href="{{ asset('storage/' . $user->id_document) }}" target="_blank">Download ID</a>
                            @endif
                        @else
                            <span class="text-muted">No ID uploaded</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No users found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
