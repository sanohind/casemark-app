@extends('layouts.app')

@section('title', 'History Detail - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('casemark.history') }}" class="text-[#0A2856] hover:text-[#0A2856]/80">
                <i class="fas fa-arrow-left mr-2"></i>Back to History
            </a>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">HISTORY DETAIL - {{ $case->case_no }}</h1>
    </div>

    <!-- Case Information Section -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Case Information</h2>
            <div class="grid grid-cols-2 gap-8">
                <div class="space-y-2">
                    <div class="flex">
                        <span class="font-semibold w-32">Destination</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->destination }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">Order No.</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->order_no ?? '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">Prod Month</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->prod_month }}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex">
                        <span class="font-semibold w-32">Case No.</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->case_no }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">C/SIZE (CM)</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->case_size }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">G/W</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->gross_weight }} KG</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">N/W</span>
                        <span class="mr-4">:</span>
                        <span>{{ $case->net_weight }} KG</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Case Status Section -->
    <div class="mb-8">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <div>
                    <h3 class="text-lg font-medium text-green-800">Case Status: Packed</h3>
                    <p class="text-sm text-green-700">Progress: {{ $progress }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Progress Table -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Scan Progress</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#0A2856]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Progress</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($scanProgress as $index => $progress)
                    <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $index + 1 }}.
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $progress['part_no'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $progress['part_name'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $progress['quantity'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                {{ $progress['progress'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Details Table -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#0A2856]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Box No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contentLists as $index => $content)
                    <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $index + 1 }}.
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $content->box_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $content->part_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $content->part_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $content->quantity }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $isScanned = $scanHistory->where('box_no', $content->box_no)->count() > 0;
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $isScanned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $isScanned ? 'Scanned' : 'Unscanned' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $scanHistory->count() }}</div>
            <div class="text-sm text-green-800">Packed Items</div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $scanHistory->sum('scanned_qty') }}</div>
            <div class="text-sm text-blue-800">Total Quantity</div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $scanHistory->unique('box_no')->count() }}</div>
            <div class="text-sm text-purple-800">Unique Boxes</div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $scanHistory->unique('part_no')->count() }}</div>
            <div class="text-sm text-gray-800">Unique Parts</div>
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
});
</script>
@endsection