@extends('layouts.app')

@section('title', 'List Case Mark - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">LIST CASE MARK</h1>

        <!-- Search -->
        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" placeholder="Search..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex space-x-4">
        <div class="relative">
            <select
                class="appearance-none bg-blue-900 text-white px-4 py-2 pr-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Status</option>
                                        <option>Unpacked</option>
                        <option>Packed</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>

        <div class="relative">
            <select
                class="appearance-none bg-blue-900 text-white px-4 py-2 pr-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Prod. Month</option>
                <option>202506</option>
                <option>202505</option>
                <option>202504</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Case No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Prod. Month
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Packing Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cases as $index => $case)
                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cases->firstItem() + $index }}.
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $case->case_no }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $case->contentLists->first()->part_no ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->prod_month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->progress }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $case->packing_date ? $case->packing_date->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($case->status == 'packed')
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Packed
                        </span>

                        @else
                        @php
                        $totalBoxes = $case->contentLists()->count();
                        $scannedBoxes = $case->scanHistory()->distinct('box_no')->count();
                        @endphp
                        @if($scannedBoxes == 0)
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            Unpacked
                        </span>
                        @else
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            In Progress
                        </span>
                        @endif
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('casemark.list.detail', $case->case_no) }}"
                                class="text-blue-600 hover:text-blue-900">
                                Detail
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        <div class="py-8">
                            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg">No cases found</p>
                            <p class="text-sm">Upload a content list to get started</p>
                            <a href="{{ route('casemark.upload') }}"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <i class="fas fa-upload mr-2"></i>
                                Upload Content List
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($cases->hasPages())
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing {{ $cases->firstItem() }} to {{ $cases->lastItem() }} of {{ $cases->total() }} results
        </div>

        <div class="flex items-center space-x-2">
            {{-- Previous Page Link --}}
            @if ($cases->onFirstPage())
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
            @else
            <a href="{{ $cases->previousPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">
                <i class="fas fa-chevron-left"></i>
            </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($cases->getUrlRange(1, $cases->lastPage()) as $page => $url)
            @if ($page == $cases->currentPage())
            <span class="px-3 py-2 bg-blue-600 text-white rounded">{{ $page }}</span>
            @elseif($page == 1 || $page == $cases->lastPage() || abs($page - $cases->currentPage()) <= 2) <a
                href="{{ $url }}" class="px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">
                {{ $page }}</a>
                @elseif(abs($page - $cases->currentPage()) == 3)
                <span class="px-3 py-2 text-gray-400">...</span>
                @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($cases->hasMorePages())
                <a href="{{ $cases->nextPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-chevron-right"></i>
                </a>
                @else
                <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                    <i class="fas fa-chevron-right"></i>
                </span>
                @endif
        </div>
    </div>
    @endif

    <!-- Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $cases->where('status', 'unpacked')->count() }}</div>
            <div class="text-sm text-blue-800">Unpacked Cases</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $cases->where('status', 'packed')->count() }}</div>
            <div class="text-sm text-green-800">Packed Cases</div>
        </div>



        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $cases->count() }}</div>
            <div class="text-sm text-gray-800">Total Cases</div>
        </div>
    </div>
</div>

<!-- Mark as Packed Modal -->
<div id="packModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Pack</h3>
        <p class="text-sm text-gray-600 mb-6">Are you sure you want to mark this case as packed?</p>
        <div class="flex justify-end space-x-4">
            <button onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
            <button onclick="confirmPack()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Confirm Pack
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentCaseNo = '';

    function markAsPacked(caseNo) {
        currentCaseNo = caseNo;
        document.getElementById('packModal').classList.remove('hidden');
        document.getElementById('packModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('packModal').classList.add('hidden');
        document.getElementById('packModal').classList.remove('flex');
        currentCaseNo = '';
    }

    function confirmPack() {
        if (!currentCaseNo) return;

        $.ajax({
            url: '{{ route("api.casemark.mark.packed") }}',
            method: 'POST',
            data: {
                case_no: currentCaseNo
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while marking as packed');
            }
        });

        closeModal();
    }

    // Search functionality
    document.querySelector('input[placeholder="Search..."]').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Auto-refresh every 60 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 60000);
</script>
@endsection