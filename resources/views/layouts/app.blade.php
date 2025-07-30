<!D<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Case Mark System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .sanoh-blue {
        background-color: #1e3a8a;
    }

    .sanoh-blue-light {
        background-color: #3b82f6;
    }

    .progress-bar {
        background: linear-gradient(90deg, #fbbf24 0%, #fbbf24 100%);
    }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div>
                        <img src="{{ asset('Logo-sanoh-2.png') }}" alt="Sanoh Logo" class="h-10 w-auto" onerror="this.style.display='none'">
                    </div>
                </div>
                <div class="text-sm text-gray-600" id="realtime-clock">
                    <!-- Waktu akan tampil di sini -->
                </div>
                <script>
                    function updateClock() {
                        const now = new Date();
                        const pad = n => n.toString().padStart(2, '0');
                        const formatted = 
                            pad(now.getDate()) + '/' +
                            pad(now.getMonth() + 1) + '/' +
                            now.getFullYear() + ' ' +
                            pad(now.getHours()) + ':' +
                            pad(now.getMinutes()) + ':' +
                            pad(now.getSeconds());
                        document.getElementById('realtime-clock').textContent = formatted;
                    }
                    updateClock();
                    setInterval(updateClock, 1000);
                </script>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex gap-8">
            <!-- Sidebar -->
            <div class="w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow">
                    <!-- Navigation Menu -->
                    <nav class="space-y-1 p-4">
                        <a href="{{ route('casemark.content-list') }}"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md 
                                  {{ request()->routeIs('casemark.content-list') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
                            <i class="fas fa-list mr-3"></i>
                            CASE MARK
                        </a>

                        <a href="{{ route('casemark.history') }}"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md
                                  {{ request()->routeIs('casemark.history') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
                            <i class="fas fa-history mr-3"></i>
                            HISTORY
                        </a>

                        <a href="{{ route('casemark.upload') }}"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md
                                  {{ request()->routeIs('casemark.upload') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
                            <i class="fas fa-upload mr-3"></i>
                            UPLOAD
                        </a>



                        <a href="{{ route('casemark.list') }}"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md
                                  {{ request()->routeIs('casemark.list') ? 'bg-blue-900 text-white' : 'text-gray-700 hover:bg-gray-50' }}">
                            <i class="fas fa-clipboard-list mr-3"></i>
                            LIST CASE MARK
                        </a>
                    </nav>
                </div>


            </div>

            <!-- Main Content Area -->
            <div class="flex-1">
                <!-- Alert Messages -->
                @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
                @endif

                <!-- Page Content -->
                <div class="bg-white rounded-lg shadow">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // CSRF Token setup for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    </script>

    @yield('scripts')
</body>

</html>