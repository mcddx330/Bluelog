<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bluelog')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100">
    <x-header />
    <div class="container mx-auto p-4">
        @if(session('error_message'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error_message') }}</span>
            </div>
        @endif

        @if ($notifications?->count() > 0)
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                @foreach ($notifications as $notification)
                    <span class="block sm:inline">
                        @if (isset($notification->created_at))
                            [{{ $notification->created_at->format('Y/m/d H:i') }}]
                        @endif
                        {{ $notification->data['error_message'] ?? '不明な通知' }}
                    </span>
                @endforeach
                <form action="{{ route('notifications.markAsRead') }}" method="POST" class="inline ml-4">
                    @csrf
                    <button type="submit" class="text-sm text-yellow-800 hover:underline">全て既読にする</button>
                </form>
            </div>
        @endif

        @if(isset($breadcrumbs) && is_array($breadcrumbs))
            <x-breadcrumbs :breadcrumbs="$breadcrumbs" />
        @endif

        @yield('content')
    </div>
    @stack('scripts')
    <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-600 dark:text-gray-400">
        &copy; {{ date('Y') }} Bluelog. All rights reserved.
    </footer>
</body>
</html>
