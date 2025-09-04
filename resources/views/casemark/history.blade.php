@extends('layouts.app')

@section('title', 'Content List History - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT-LIST HISTORY</h1>
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
        <table id="historyTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-[#0A2856]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Case No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Destination</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Prod. Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Total Boxes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Progress</th>
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
                    <th class="px-6 py-2"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cases as $index => $case)
                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}"
                    data-case-no="{{ $case->case_no }}"
                    data-prod-month="{{ $case->prod_month }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $index + 1 }}.
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $case->case_no }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->destination }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->prod_month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->contentLists->count() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $case->progress }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $case->packing_date ? $case->packing_date->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Packed
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('casemark.history.detail', $case->case_no) }}"
                            class="text-[#0A2856] hover:text-[#0A2856]/80">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                        <div class="py-8">
                            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg">No packed cases found</p>
                            <p class="text-sm">Cases will appear here after they are marked as packed</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    {{-- Pagination removed - DataTables handles pagination on client-side --}}

    <!-- Summary Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $packedCasesCount }}</div>
            <div class="text-sm text-green-800">Packed Cases</div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $totalBoxesCount }}</div>
            <div class="text-sm text-blue-800">Total Boxes</div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $totalQuantityCount }}</div>
            <div class="text-sm text-purple-800">Total Quantity</div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ $productionMonthsCount }}</div>
            <div class="text-sm text-gray-800">Production Months</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#historyTable').DataTable({
            orderCellsTop: true, // Ini penting untuk menggunakan baris pertama untuk sorting
            fixedHeader: true,
            initComplete: function() {
                // Menambahkan search input ke baris kedua di thead
                this.api()
                    .columns()
                    .every(function(colIdx) {
                        let column = this;
                        let title = column.header().textContent;

                        // Skip kolom No. dan Action (kolom 0 dan 8)
                        if (colIdx === 0 || colIdx === 8) {
                            return;
                        }

                        let input = document.createElement('input');
                        input.placeholder = title;
                        input.className = 'border border-gray-300 rounded-md px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-[#0A2856] focus:border-[#0A2856] bg-white';

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

    // Date range filter functionality
    $('#filterDateBtn').on('click', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        if (!startDate && !endDate) {
            alert('Please select at least one date');
            return;
        }

        // Custom search function for date range
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'historyTable') {
                return true;
            }

            const packingDateStr = data[6]; // Packing Date column (0-indexed)

            if (!packingDateStr || packingDateStr === '-') {
                return false; // Hide rows without packing date
            }

            // Parse the date from format "dd/mm/yyyy hh:mm"
            const dateParts = packingDateStr.split(' ')[0].split('/');
            if (dateParts.length !== 3) return false;

            const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                return rowDate >= start && rowDate <= end;
            } else if (startDate) {
                const start = new Date(startDate);
                return rowDate >= start;
            } else if (endDate) {
                const end = new Date(endDate);
                return rowDate <= end;
            }

            return true;
        });

        $('#historyTable').DataTable().draw();
    });

    $('#clearDateBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');

        // Remove all custom search functions
        $.fn.dataTable.ext.search = [];

        $('#historyTable').DataTable().draw();
    });

    // Auto-refresh every 60 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 60000);
</script>
@endsection