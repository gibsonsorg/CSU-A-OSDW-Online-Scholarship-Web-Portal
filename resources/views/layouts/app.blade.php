<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="student-app-shell" id="studentAppShell">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="student-main-content">
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const shell = document.getElementById('studentAppShell');
                const sidebar = document.getElementById('studentSidebar');
                const toggle = document.getElementById('studentSidebarToggle');

                if (!shell || !sidebar || !toggle) {
                    return;
                }

                const applySidebarState = function (collapsed) {
                    sidebar.classList.toggle('collapsed', collapsed);
                    shell.classList.toggle('sidebar-collapsed', collapsed);
                    toggle.classList.toggle('active', collapsed);
                    localStorage.setItem('studentSidebarCollapsed', collapsed ? 'true' : 'false');
                };

                const storedState = localStorage.getItem('studentSidebarCollapsed') === 'true';
                applySidebarState(storedState);

                toggle.addEventListener('click', function () {
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    applySidebarState(!isCollapsed);
                });
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            function confirmDelete(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to DELETE this application?',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'YES',
                    cancelButtonText: 'NO',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var f = document.getElementById('deleteForm-' + id);
                        if (f) f.submit();
                        else console.warn('deleteForm-' + id + ' not found');
                    }
                });
            }

            function confirmApprove(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to APPROVE this application?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'YES',
                    cancelButtonText: 'NO',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var f = document.getElementById('approveForm-' + id);
                        if (f) f.submit();
                        else console.warn('approveForm-' + id + ' not found');
                    }
                });
            }

            function confirmReject(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to REJECT this application?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'YES',
                    cancelButtonText: 'NO',
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var f = document.getElementById('rejectForm-' + id);
                        if (f) f.submit();
                        else console.warn('rejectForm-' + id + ' not found');
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                @if(session('success'))
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')), showConfirmButton: false, timer: 3000, timerProgressBar: true });
                @endif
                @if(session('error'))
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: @json(session('error')), showConfirmButton: false, timer: 3000, timerProgressBar: true });
                @endif
                @if(session('warning'))
                    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: @json(session('warning')), showConfirmButton: false, timer: 3000, timerProgressBar: true });
                @endif
            });
        </script>
    </body>
</html>
