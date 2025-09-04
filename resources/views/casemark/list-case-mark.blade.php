@extends('layouts.app')

@section('title', 'List Case Mark - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">LIST CASE MARK</h1>
    </div>

    <!-- Date Range Search -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1">
                <label for="startDate" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="startDate" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0A2856] focus:border-[#0A2856]">
            </div>
            <div class="flex-1">
                <label for="endDate" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="endDate" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0A2856] focus:border-[#0A2856]">
            </div>
            <div class="flex gap-2">
                <button id="filterDateBtn" class="px-4 py-2 bg-[#0A2856] text-white rounded-md hover:bg-[#0A2856]/90 text-sm font-medium">
                    <i class="fas fa-search mr-1"></i>Filter
                </button>
                <button id="clearDateBtn" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm font-medium">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="overflow-x-auto">
        <table id="casesTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-[#0A2856]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Case No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Prod. Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Packing Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Action</th>
                </tr>
                <!-- Search Row -->
                <tr class="bg-gray-100">
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                    <th class="px-6 py-2"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cases as $index => $case)
                @php
                $hasScanHistory = $case->scanHistory()->exists();
                $status = $case->status == 'packed' ? 'packed' : ($hasScanHistory ? 'in-progress' : 'unpacked');
                @endphp
                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}" data-prod-month="{{ $case->prod_month }}" data-status="{{ $status }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}.
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $case->case_no }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $case->contentLists->first()->part_no ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->prod_month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->progress }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-date="{{ $case->packing_date ? $case->packing_date->format('Y-m-d') : '' }}">
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
                        $hasScanHistory = $case->scanHistory()->exists();
                        @endphp
                        @if($hasScanHistory)
                        <span
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            In Progress
                        </span>
                        @else
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            Unpacked
                        </span>
                        @endif
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('casemark.list.detail', $case->case_no) }}"
                                class="text-[#0A2856] hover:text-[#0A2856]/80">
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
                                class="mt-4 inline-flex items-center px-4 py-2 bg-[#0A2856] text-white rounded-md hover:bg-[#0A2856]/90">
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

    <!-- Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-red-100 border border-red-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $unpackedCount }}</div>
            <div class="text-sm text-red-800">Unpacked Cases</div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $inProgressCount }}</div>
            <div class="text-sm text-yellow-800">In Progress Cases</div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $packedCount }}</div>
            <div class="text-sm text-green-800">Packed Cases</div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $unpackedCount + $inProgressCount + $packedCount }}</div>
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
    $(document).ready(function() {
        $('#casesTable').DataTable({
            orderCellsTop: true, // Menggunakan baris pertama untuk sorting
            fixedHeader: true,
            initComplete: function() {
                // Menambahkan search input ke baris kedua di thead
                this.api()
                    .columns()
                    .every(function(colIdx) {
                        let column = this;
                        let title = column.header().textContent;

                        // Skip kolom No. dan Action (kolom 0 dan 7)
                        if (colIdx === 0 || colIdx === 7) {
                            return;
                        }

                        let input = document.createElement('input');
                        // Buat placeholder yang lebih pendek berdasarkan nama kolom
                        let placeholder = '';
                        switch (colIdx) {
                            case 1:
                                placeholder = 'Case No';
                                break;
                            case 2:
                                placeholder = 'Part No';
                                break;
                            case 3:
                                placeholder = 'Prod. Month';
                                break;
                            case 4:
                                placeholder = 'Total';
                                break;
                            case 5:
                                placeholder = 'Packing Date';
                                break;
                            case 6:
                                placeholder = 'Status';
                                break;
                            default:
                                placeholder = title;
                        }
                        input.placeholder = placeholder;
                        input.className = 'border border-gray-300 rounded-md px-2 py-1 text-xs w-full focus:outline-none focus:ring-2 focus:ring-[#0A2856] focus:border-[#0A2856] bg-white min-w-0';

                        // Menambahkan input ke baris kedua (search row) di thead
                        $(input).appendTo($(column.header()).parent().next().find('th').eq(colIdx))
                            .on('keyup change clear', function() {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    });
            },
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'All']
            ],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });
    });

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

    // Date range filter functionality - Updated to match history.blade logic
    let casemarkDateRangeFilter = null;
    $('#filterDateBtn').on('click', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        if (!startDate && !endDate) {
            alert('Please select at least one date');
            return;
        }

        // Custom search function for date range
        // Remove previously-registered date range filter for this table (if any)
        if (casemarkDateRangeFilter) {
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) { return fn !== casemarkDateRangeFilter; });
        }

        casemarkDateRangeFilter = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'casesTable') {
                return true;
            }

            // Get the row element to access data-date attribute
            const row = settings.aoData[dataIndex].nTr;
            const dateCell = row.querySelector('td[data-date]');
            
            if (!dateCell) {
                return false; // Hide rows without date data
            }
            
            const rowDateStr = dateCell.getAttribute('data-date');
            
            if (!rowDateStr || rowDateStr === '') {
                return false; // Hide rows without packing date
            }
            
            // Use the data-date attribute which is already in Y-m-d format
            // This avoids timezone issues from parsing displayed date
            const rowDate = new Date(rowDateStr + 'T00:00:00');

            if (startDate && endDate) {
                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T23:59:59');
                return rowDate >= start && rowDate <= end;
            } else if (startDate) {
                const start = new Date(startDate + 'T00:00:00');
                return rowDate >= start;
            } else if (endDate) {
                const end = new Date(endDate + 'T23:59:59');
                return rowDate <= end;
            }

            return true;
        };

        $.fn.dataTable.ext.search.push(casemarkDateRangeFilter);

        $('#casesTable').DataTable().draw();
    });

    $('#clearDateBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');

        // Remove only this table's date range filter
        if (casemarkDateRangeFilter) {
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) { return fn !== casemarkDateRangeFilter; });
            casemarkDateRangeFilter = null;
        }

        $('#casesTable').DataTable().draw();
    });

    // Optional: keep auto-refresh
    // Auto-refresh every 60 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 60000);
</script>
@endsection