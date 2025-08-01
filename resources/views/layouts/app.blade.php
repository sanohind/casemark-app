<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Case Mark System')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('sanoh-favicon.png') }}?v=1">
    <link rel="shortcut icon" type="image/png" href="{{ asset('sanoh-favicon.png') }}?v=1">
    
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
    
    /* Fixed header and sidebar styles */
    .fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        background: white;
    }
    
    .fixed-sidebar {
        position: fixed;
        top: 80px; /* Height of header */
        left: 0;
        bottom: 0;
        width: 256px; /* w-64 = 16rem = 256px */
        z-index: 40;
        overflow-y: auto;
    }
    
    .content-with-fixed-layout {
        margin-top: 80px; /* Height of header */
        margin-left: 256px; /* Width of sidebar */
        min-height: calc(100vh - 80px);
    }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Fixed Header -->
    <header class="fixed-header bg-white shadow-sm border-b">
        <div class="px-4 sm:px-6 lg:px-8">
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

    <!-- Fixed Sidebar -->
    <div class="fixed-sidebar">
        <div class="bg-white shadow h-full">
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
    <div class="content-with-fixed-layout">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

    <!-- Global Toast Notifications -->
    <!-- Success Notification Toast -->
    <div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50" style="display: none;">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <h4 class="font-semibold" id="toastTitle">Success!</h4>
                <p class="text-sm opacity-90" id="toastMessage">Operation completed successfully.</p>
            </div>
        </div>
    </div>

    <!-- Error Notification Toast -->
    <div id="errorToast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50" style="display: none;">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <div>
                <h4 class="font-semibold">Error!</h4>
                <p class="text-sm opacity-90" id="errorToastMessage">An error occurred.</p>
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

    // Global Toast Notification Functions
    function showSuccessToast(title, message) {
        const toast = document.getElementById('successToast');
        if (toast) {
            const titleEl = toast.querySelector('#toastTitle');
            const messageEl = toast.querySelector('#toastMessage');
            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            
            // Show and animate
            toast.style.display = 'block';
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);

            // Auto hide after 3 seconds
            setTimeout(() => {
                hideSuccessToast();
            }, 3000);
        }
    }

    function hideSuccessToast() {
        const toast = document.getElementById('successToast');
        if (toast) {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            
            // Hide completely after animation
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }
    }

    function showErrorToast(title, message) {
        const toast = document.getElementById('errorToast');
        if (toast) {
            const messageEl = toast.querySelector('#errorToastMessage');
            if (messageEl) messageEl.textContent = message;
            
            // Show and animate
            toast.style.display = 'block';
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
            }, 10);

            // Auto hide after 3 seconds
            setTimeout(() => {
                hideErrorToast();
            }, 3000);
        }
    }

    function hideErrorToast() {
        const toast = document.getElementById('errorToast');
        if (toast) {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            
            // Hide completely after animation
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }
    }

    // Test functions (optional - hapus jika tidak diperlukan)
    function testSuccessToast() {
        showSuccessToast('Test Success', 'This is a test success message!');
    }

    function testErrorToast() {
        showErrorToast('Test Error', 'This is a test error message!');
    }
    </script>

    @yield('scripts')
</body>
</html>