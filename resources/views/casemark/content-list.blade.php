@extends('layouts.app')

@section('title', 'Content List - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT LIST</h1>
    </div>

    <!-- Container Scanner Section -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Scan Container Barcode</h2>
            
            <div class="mb-4">
                <label for="containerBarcode" class="block text-sm font-medium text-gray-700 mb-2">
                    Container Barcode
                </label>
                <input type="text" 
                       id="containerBarcode" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg font-mono"
                       placeholder="Scan container barcode to load case data..."
                       autofocus>
            </div>
            
            <button type="button" onclick="scanContainer()" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium flex items-center">
                <i class="fas fa-qrcode mr-2"></i>
                Scan Container
            </button>
        </div>
    </div>

    <!-- Box Scanner Section (Hidden initially) -->
    <div id="boxScanner" class="mb-8 hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Scan Box Barcode</h2>
            
            <div class="mb-4">
                <label for="boxBarcode" class="block text-sm font-medium text-gray-700 mb-2">
                    Box Barcode
                </label>
                <input type="text" 
                       id="boxBarcode" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-lg font-mono"
                       placeholder="Scan box barcode...">
            </div>
            
            <button type="button" onclick="scanBox()" 
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-medium flex items-center">
                <i class="fas fa-barcode mr-2"></i>
                Scan Box
            </button>
        </div>
    </div>

    <!-- Case Information Section (Hidden initially) -->
    <div id="caseInfo" class="mb-8 hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Case Information</h2>
            <div class="grid grid-cols-2 gap-8">
                <div class="space-y-2">
                    <div class="flex">
                        <span class="font-semibold w-32">Destination</span>
                        <span class="mr-4">:</span>
                        <span id="destination">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">Order No.</span>
                        <span class="mr-4">:</span>
                        <span id="orderNo">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">Prod Month</span>
                        <span class="mr-4">:</span>
                        <span id="prodMonth">-</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex">
                        <span class="font-semibold w-32">Case No.</span>
                        <span class="mr-4">:</span>
                        <span id="caseNo">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">C/SIZE (CM)</span>
                        <span class="mr-4">:</span>
                        <span id="caseSize">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">G/W</span>
                        <span class="mr-4">:</span>
                        <span id="grossWeight">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">N/W</span>
                        <span class="mr-4">:</span>
                        <span id="netWeight">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No data - Scan container barcode to load case data</td>
                    </tr>
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
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No data - Scan container barcode to load case data</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Submit Button (Hidden initially) -->
    <div id="submitContainer" class="mt-8 text-center hidden">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-check-circle text-green-500 text-3xl mr-3"></i>
                <h3 class="text-lg font-semibold text-green-900">All Items Scanned!</h3>
            </div>
            <p class="text-green-700 mb-4">All items have been successfully scanned.</p>
            <button type="button" onclick="submitCase()" 
                class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-md font-medium text-lg">
                <i class="fas fa-check mr-2"></i>
                Submit Case
            </button>
        </div>
    </div>
</div>

<!-- Success Notification Toast -->
<div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-3"></i>
        <div>
            <h4 class="font-semibold" id="toastTitle">Success!</h4>
            <p class="text-sm opacity-90" id="toastMessage">Operation completed successfully.</p>
        </div>
    </div>
</div>

<!-- Error Notification Toast -->
<div id="errorToast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle mr-3"></i>
        <div>
            <h4 class="font-semibold">Error!</h4>
            <p class="text-sm opacity-90" id="errorToastMessage">An error occurred.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentCaseId = null;
let currentCaseData = null;

// Container barcode scanner
document.getElementById('containerBarcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        scanContainer();
    }
});

// Auto-enter for container barcode (trigger scan automatically after barcode input)
document.getElementById('containerBarcode').addEventListener('input', function(e) {
    const barcode = this.value;
    // Auto-trigger scan if barcode length is sufficient (assuming barcode has minimum length)
    if (barcode.length >= 20) {
        // Small delay to ensure barcode is completely scanned
        setTimeout(() => {
            if (this.value === barcode) { // Check if value hasn't changed (barcode complete)
                scanContainer();
            }
        }, 100);
    }
});

// Box barcode scanner
document.getElementById('boxBarcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        scanBox();
    }
});

// Auto-enter for box barcode (trigger scan automatically after barcode input)
document.getElementById('boxBarcode').addEventListener('input', function(e) {
    const barcode = this.value;
    // Auto-trigger scan if barcode contains '#' (indicates complete box barcode)
    if (barcode.includes('#')) {
        // Small delay to ensure barcode is completely scanned
        setTimeout(() => {
            if (this.value === barcode) { // Check if value hasn't changed (barcode complete)
                scanBox();
            }
        }, 100);
    }
});

function scanContainer() {
    const barcode = document.getElementById('containerBarcode').value;
    
    if (!barcode) {
        showErrorToast('Please enter container barcode');
        return;
    }

    $.ajax({
        url: '/api/casemark/scan-container',
        method: 'POST',
        data: {
            barcode: barcode,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                currentCaseId = response.data.case.id;
                currentCaseData = response.data;
                
                // Show case information
                displayCaseInfo(response.data.case);
                
                // Show box scanner
                document.getElementById('boxScanner').classList.remove('hidden');
                
                // Update progress tables
                updateProgressTables(response.data);
                
                // Show success notification
                showSuccessToast('Container scanned successfully!', 'Please scan box barcodes now.');
                
                // Focus on box scanner
                setTimeout(() => {
                    document.getElementById('boxBarcode').focus();
                }, 2000);
                
                // Clear container barcode input
                document.getElementById('containerBarcode').value = '';
                
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error scanning container');
        }
    });
}

function scanBox() {
    const barcode = document.getElementById('boxBarcode').value;
    
    if (!barcode) {
        showErrorToast('Please enter box barcode');
        return;
    }
    
    if (!currentCaseId) {
        showErrorToast('Please scan container first');
        return;
    }
    
    $.ajax({
        url: '/api/casemark/scan-box',
        method: 'POST',
        data: {
            barcode: barcode,
            case_id: currentCaseId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Update progress tables
                updateProgressTables(currentCaseData);
                
                // Show success notification
                showSuccessToast('Box scanned successfully!', 'Box sequence: ' + response.data.sequence);
                
                // Check if all items are scanned
                checkCompletion();
                
                // Focus back on box scanner
                setTimeout(() => {
                    document.getElementById('boxBarcode').focus();
                }, 2000);
                
                // Clear box barcode input
                document.getElementById('boxBarcode').value = '';
                
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error scanning box');
        }
    });
}

function displayCaseInfo(caseData) {
    // Show case info section
    document.getElementById('caseInfo').classList.remove('hidden');
    
    // Fill case information
    document.getElementById('destination').textContent = caseData.destination || '-';
    document.getElementById('orderNo').textContent = caseData.order_no || '-';
    document.getElementById('prodMonth').textContent = caseData.prod_month || '-';
    document.getElementById('caseNo').textContent = caseData.case_no || '-';
    document.getElementById('caseSize').textContent = caseData.case_size || '-';
    document.getElementById('grossWeight').textContent = caseData.gross_weight ? caseData.gross_weight + ' KGS' : '-';
    document.getElementById('netWeight').textContent = caseData.net_weight ? caseData.net_weight + ' KGS' : '-';
}

function updateProgressTables(data) {
    // Fetch updated data from server
    if (!currentCaseId) return;
    
    $.ajax({
        url: `/api/casemark/get-case-progress/${currentCaseId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update scan progress table
                const progressBody = document.querySelector('tbody');
                if (progressBody) {
                    const isComplete = data.scannedBoxes >= data.totalBoxes && data.totalBoxes > 0;
                    progressBody.innerHTML = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.scanProgress.part_no}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.scanProgress.part_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.scanProgress.quantity}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${isComplete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                    ${data.progress}
                                </span>
                            </td>
                        </tr>
                    `;
                }
                
                // Update details table
                const detailsBody = document.querySelectorAll('tbody')[1];
                if (detailsBody) {
                    detailsBody.innerHTML = '';
                    
                    data.details.forEach((item, index) => {
                        const row = document.createElement('tr');
                        row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${index + 1}.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.box_no}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_no}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.quantity}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${item.is_scanned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                    ${item.is_scanned ? 'Scanned' : 'Not Scanned'}
                                </span>
                            </td>
                        `;
                        detailsBody.appendChild(row);
                    });
                }
                
                // Check completion and show submit button
                if (data.scannedBoxes >= data.totalBoxes && data.totalBoxes > 0) {
                    showSubmitButton(data.totalBoxes);
                }
            }
        },
        error: function(xhr) {
            console.error('Error updating progress:', xhr);
        }
    });
}

function showSubmitButton(totalExpected) {
    const submitContainer = document.getElementById('submitContainer');
    submitContainer.classList.remove('hidden');
}

function checkCompletion() {
    if (!currentCaseId) return;
    
    // Fetch latest progress from server
    $.ajax({
        url: `/api/casemark/get-case-progress/${currentCaseId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                if (data.scannedBoxes >= data.totalBoxes && data.totalBoxes > 0) {
                    showSubmitButton(data.totalBoxes);
                }
            }
        },
        error: function(xhr) {
            console.error('Error checking completion:', xhr);
        }
    });
}

function submitCase() {
    if (!currentCaseData) return;
    
    $.ajax({
        url: '/api/casemark/submit-case',
        method: 'POST',
        data: {
            case_no: currentCaseData.case.case_no,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showSuccessToast('Case submitted successfully!', 'Redirecting to case list...');
                setTimeout(() => {
                    window.location.href = '{{ route("casemark.list") }}';
                }, 2000);
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error submitting case');
        }
    });
}

function showSuccessToast(title, message) {
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    
    const toast = document.getElementById('successToast');
    toast.classList.remove('translate-x-full');
    toast.classList.add('translate-x-0');
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        hideSuccessToast();
    }, 3000);
}

function hideSuccessToast() {
    const toast = document.getElementById('successToast');
    toast.classList.remove('translate-x-0');
    toast.classList.add('translate-x-full');
}

function showErrorToast(message) {
    document.getElementById('errorToastMessage').textContent = message;
    
    const toast = document.getElementById('errorToast');
    toast.classList.remove('translate-x-full');
    toast.classList.add('translate-x-0');
    
    // Auto hide after 4 seconds
    setTimeout(() => {
        hideErrorToast();
    }, 4000);
}

function hideErrorToast() {
    const toast = document.getElementById('errorToast');
    toast.classList.remove('translate-x-0');
    toast.classList.add('translate-x-full');
}

// Focus on container barcode input on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('containerBarcode').focus();
});
</script>
@endsection