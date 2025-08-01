@extends('layouts.app')

@section('title', 'List Case Mark Detail - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('casemark.list') }}" class="text-blue-600 hover:text-blue-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to List Case Mark
            </a>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">LIST CASE MARK DETAIL - {{ $case->case_no }}</h1>
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
        @if($case->status == 'packed')
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <div>
                    <h3 class="text-lg font-medium text-green-800">Case Status: Packed</h3>
                    <p class="text-sm text-green-700">Progress: {{ $progress }}</p>
                    <p class="text-sm text-green-700">Packed on: {{ $case->packing_date ? $case->packing_date->format('d/m/Y H:i') : '-' }}</p>
                </div>
            </div>
        </div>
        @else
        @php
            $totalBoxes = $contentLists->count();
            $scannedBoxes = $scanHistory->where('status', 'scanned')->count();
        @endphp
        @if($scannedBoxes == 0)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                <div>
                    <h3 class="text-lg font-medium text-red-800">Case Status: Unpacked</h3>
                    <p class="text-sm text-red-700">Progress: {{ $progress }}</p>
                    <p class="text-sm text-red-700">No boxes have been scanned yet</p>
                </div>
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-clock text-yellow-600 mr-3"></i>
                <div>
                    <h3 class="text-lg font-medium text-yellow-800">Case Status: In Progress</h3>
                    <p class="text-sm text-yellow-700">Progress: {{ $progress }}</p>
                    <p class="text-sm text-yellow-700">{{ $scannedBoxes }} of {{ $totalBoxes }} boxes scanned</p>
                </div>
            </div>
        </div>
        @endif
        @endif
    </div>

    <!-- Action Buttons -->
    @if($case->status == 'unpacked')
    <div class="mb-8">
        <div class="flex space-x-4">
            <a href="{{ route('casemark.content-list', $case->case_no) }}" 
               class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 flex items-center">
                <i class="fas fa-qrcode mr-2"></i>
                Start Scanning
            </a>
            @php
                $scannedBoxes = $scanHistory->where('status', 'scanned')->count();
            @endphp
            @if($scannedBoxes > 0)
            <button onclick="markAsPacked('{{ $case->case_no }}')"
                    class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 flex items-center">
                <i class="fas fa-check mr-2"></i>
                Mark as Packed
            </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Scan Progress Table -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Scan Progress</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-900">
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
                            @php
                                $progressParts = explode('/', $progress['progress']);
                                $scanned = (int)$progressParts[0];
                                $total = (int)$progressParts[1];
                                $isComplete = $scanned >= $total;
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $isComplete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
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
                <thead class="bg-blue-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Box No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        @if($case->status == 'packed')
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Scanned At</th>
                        @endif
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
                                $scannedBox = $scanHistory->where('box_no', $content->box_no)->first();
                                $isScanned = $scannedBox ? true : false;
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $isScanned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $isScanned ? 'Scanned' : 'Unscanned' }}
                            </span>
                        </td>
                        @if($case->status == 'packed')
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($scannedBox && $scannedBox->scanned_at)
                                @if(is_string($scannedBox->scanned_at))
                                    {{ \Carbon\Carbon::parse($scannedBox->scanned_at)->format('d/m/Y H:i') }}
                                @else
                                    {{ $scannedBox->scanned_at->format('d/m/Y H:i') }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $case->status == 'packed' ? '7' : '6' }}" class="px-6 py-4 text-center text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $contentLists->count() }}</div>
            <div class="text-sm text-blue-800">Total Boxes</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $scanHistory->count() }}</div>
            <div class="text-sm text-green-800">Scanned Boxes</div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $contentLists->count() - $scanHistory->count() }}</div>
            <div class="text-sm text-yellow-800">Remaining Boxes</div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $scanHistory->sum('scanned_qty') }}</div>
            <div class="text-sm text-purple-800">Total Quantity</div>
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
                    showSuccessToast('Success!', 'Case marked as packed successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showErrorToast('Error', response.message);
                }
            },
            error: function() {
                showErrorToast('Error', 'An error occurred while marking as packed');
            }
        });

        closeModal();
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000);
</script>
@endsection