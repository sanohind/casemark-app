@extends('layouts.app')

@section('title', 'Content List - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT LIST</h1>
    </div>

    @if(isset($case))
    <!-- Case Information -->
    <div class="grid grid-cols-2 gap-8 mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="space-y-2">
            <div class="flex">
                <span class="font-semibold w-32">Destination</span>
                <span class="mr-4">:</span>
                <span>{{ $case->destination }}</span>
            </div>
            <div class="flex">
                <span class="font-semibold w-32">Order No.</span>
                <span class="mr-4">:</span>
                <span>{{ $case->order_no }}</span>
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
                <span>{{ $case->gross_weight }} KGS</span>
            </div>
            <div class="flex">
                <span class="font-semibold w-32">N/W</span>
                <span class="mr-4">:</span>
                <span>{{ $case->net_weight }} KGS</span>
            </div>
        </div>
    </div>

    <!-- Content Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No.
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Progress
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-lg font-bold">
                        Progress: <span class="inline-flex px-2 py-1 text-base font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $progress }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Box Details Table (Scanned Only) -->
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Box Scanned History</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Box No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Scan Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($scanHistory as $index => $scan)
                        @php
                            $content = $contentLists->first(function($item) use ($scan) {
                                return $item->box_no === $scan->box_no && $item->part_no === $scan->part_no;
                            });
                        @endphp
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $scan->box_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $scan->part_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($content)
                                    {{ $content->part_name }}
                                @else
                                    <span class="text-red-600 font-bold">Tidak Cocok!</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $scan->scanned_qty }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Scanned
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $scan->scanned_at ? $scan->scanned_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada box yang di-scan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @else
    <!-- No Case Selected -->
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-box-open text-6xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Case Selected</h3>
        <p class="text-gray-600 mb-6">Please select a case or upload content list to view details</p>
        <div class="space-x-4">
            <a href="{{ route('casemark.upload') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Upload Content List
            </a>
            <a href="{{ route('casemark.list') }}"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                View All Cases
            </a>
        </div>
    </div>
    @endif
</div>
@endsection