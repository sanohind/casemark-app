@extends('layouts.app')

@section('title', 'Content List History - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT-LIST HISTORY</h1>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex space-x-4">
        <div class="relative">
            <select
                class="appearance-none bg-blue-900 text-white px-4 py-2 pr-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Case No</option>
                <option>I2E-SAN-00435</option>
                <option>I2E-SAN-00442</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>

        <div class="relative">
            <select
                class="appearance-none bg-blue-900 text-white px-4 py-2 pr-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>Status</option>
                <option>Packed</option>
                <option>Scanned</option>
                <option>Pending</option>
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

    <!-- History Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Case No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Prod. Month
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Packing Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($histories as $index => $history)
                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $histories->firstItem() + $index }}.</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->case->case_no }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->part_no }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->case->prod_month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->scanned_qty }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->progress }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $history->packing_date ? $history->packing_date->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($history->status == 'packed')
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Packed
                        </span>
                        @elseif($history->status == 'scanned')
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Scanned
                        </span>
                        @else
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            Unpacked
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        <div class="py-8">
                            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg">No history records found</p>
                            <p class="text-sm">Start scanning boxes to see history here</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($histories->hasPages())
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing {{ $histories->firstItem() }} to {{ $histories->lastItem() }} of {{ $histories->total() }} results
        </div>

        <div class="flex items-center space-x-2">
            {{-- Previous Page Link --}}
            @if ($histories->onFirstPage())
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </span>
            @else
            <a href="{{ $histories->previousPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">
                <i class="fas fa-chevron-left"></i>
            </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($histories->getUrlRange(1, $histories->lastPage()) as $page => $url)
            @if ($page == $histories->currentPage())
            <span class="px-3 py-2 bg-blue-600 text-white rounded">{{ $page }}</span>
            @else
            <a href="{{ $url }}"
                class="px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded">{{ $page }}</a>
            @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($histories->hasMorePages())
            <a href="{{ $histories->nextPageUrl() }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">
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

    <!-- Summary Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $histories->where('status', 'packed')->count() }}</div>
            <div class="text-sm text-blue-800">Packed Items</div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $histories->where('status', 'scanned')->count() }}</div>
            <div class="text-sm text-yellow-800">Scanned Items</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $histories->sum('scanned_qty') }}</div>
            <div class="text-sm text-green-800">Total Quantity</div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $histories->groupBy('case_id')->count() }}</div>
            <div class="text-sm text-gray-800">Total Cases</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000);

    // Filter functionality
    const filterSelects = document.querySelectorAll('select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Implement filter logic here
            console.log('Filter changed:', this.value);
        });
    });
});
</script>
@endsection