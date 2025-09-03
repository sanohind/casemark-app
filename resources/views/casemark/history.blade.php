@extends('layouts.app')

@section('title', 'Content List History - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT-LIST HISTORY</h1>
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
            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
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
            initComplete: function () {
                this.api()
                    .columns()
                    .every(function () {
                        let column = this;
                        let title = column.header().textContent;
                        let input = document.createElement('input');
                        input.placeholder = title;
                        input.className = 'border border-gray-300 rounded-md px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-[#0A2856] focus:border-[#0A2856]';

                        if (column.footer() && column.footer().textContent !== undefined) {
                            column.footer().replaceChildren(input);
                        }

                        input.addEventListener('keyup', () => {
                            if (column.search() !== input.value) {
                                column.search(input.value).draw();
                            }
                        });
                    });
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries per page',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
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