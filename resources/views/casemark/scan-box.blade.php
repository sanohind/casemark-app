@extends('layouts.app')

@section('title', 'Scan Box - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">SCAN BOX</h1>
                @if(request('case_no'))
                <p class="text-gray-600 mt-1">Container: <span class="font-semibold">{{ request('case_no') }}</span></p>
                @endif
            </div>

            <!-- Container Status -->
            <div class="text-right">
                <div id="progressInfo" class="text-sm text-gray-500">
                    Progress: <span id="currentProgress">0/0</span>
                </div>
                <div id="scanCount" class="text-2xl font-bold text-blue-600">0</div>
                <div class="text-sm text-gray-500">Boxes Scanned</div>
            </div>
        </div>
    </div>

    @if(!request('case_no'))
    <!-- No Container Selected -->
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-exclamation-triangle text-6xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Container Selected</h3>
        <p class="text-gray-600 mb-6">Please scan a container first before scanning boxes</p>
        <a href="{{ route('casemark.scan.container') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
            <i class="fas fa-qrcode mr-2"></i>
            Scan Container
        </a>
    </div>
    @else

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Scan Area -->
        <div class="lg:col-span-2">
            <!-- QR Scanner Section -->
            <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-6 text-center mb-6">
                <div class="mb-4">
                    <i class="fas fa-box text-4xl text-gray-400"></i>
                </div>

                <!-- Manual Input -->
                <div class="mb-4">
                    <label for="box_qr" class="block text-sm font-medium text-gray-700 mb-2">
                        Box QR Code
                    </label>
                    <input type="text" id="box_qr" placeholder="Scan or enter box QR code..."
                        class="w-full px-4 py-3 text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-center"
                        autofocus>
                </div>

                <!-- Camera Scanner -->
                <div class="mb-4">
                    <button id="startCamera" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-camera mr-2"></i>
                        Use Camera
                    </button>
                </div>

                <video id="scanner" width="300" height="200" class="hidden mx-auto border rounded"></video>
            </div>

            <!-- Scan Button -->
            <div class="text-center mb-6">
                <button id="scanBox" onclick="scanBox()"
                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-md font-medium text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-check mr-2"></i>
                    Scan Box
                </button>
            </div>

            <!-- Scan Result -->
            <div id="scanResult" class="hidden"></div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Container Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Container Info</h3>
                <div id="containerDetails" class="space-y-1 text-sm text-gray-600">
                    <div>Loading container info...</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white border rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <button onclick="clearInput()"
                        class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded text-sm">
                        <i class="fas fa-eraser mr-2"></i>
                        Clear Input
                    </button>
                    <button onclick="showContentList()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded text-sm">
                        <i class="fas fa-list mr-2"></i>
                        View Content List
                    </button>
                    <button onclick="markAllPacked()"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded text-sm">
                        <i class="fas fa-box mr-2"></i>
                        Mark as Packed
                    </button>
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="bg-white border rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Recent Scans</h3>
                <div id="recentScans" class="space-y-2 max-h-64 overflow-y-auto">
                    <!-- Recent scans will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scan History Table -->
    <div class="mt-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Scan History</h3>
        <div class="overflow-x-auto">
            <table id="scanHistoryTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Box No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Part No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Scan Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    </tr>
                </thead>
                <tbody id="scanHistoryBody" class="bg-white divide-y divide-gray-200">
                    <!-- Scan history will be populated here -->
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Box Scanned Successfully!</h3>
            <div id="successDetails" class="text-sm text-gray-600 mb-4">
                <!-- Success details will be populated here -->
            </div>
            <button onclick="closeSuccessModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                Continue Scanning
            </button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Scan Error</h3>
            <div id="errorDetails" class="text-sm text-gray-600 mb-4">
                <!-- Error details will be populated here -->
            </div>
            <button onclick="closeErrorModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                Try Again
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const caseNo = '{{ request("case_no") }}';
let scanHistory = [];
let containerInfo = {};

document.addEventListener('DOMContentLoaded', function() {
    if (caseNo) {
        loadContainerInfo();
        loadScanHistory();
        setupScanInput();

        // Auto-refresh every 10 seconds
        setInterval(loadScanHistory, 10000);
    }
});

function setupScanInput() {
    const boxInput = document.getElementById('box_qr');
    const scanBtn = document.getElementById('scanBox');

    boxInput.addEventListener('input', function() {
        scanBtn.disabled = !this.value.trim();
    });

    boxInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            scanBox();
        }
    });
}

function loadContainerInfo() {
    // Load container information
    const containerDetails = document.getElementById('containerDetails');
    containerDetails.innerHTML = `
        <div><strong>Case No:</strong> ${caseNo}</div>
        <div><strong>Status:</strong> Active</div>
        <div>Loading details...</div>
    `;
}

function loadScanHistory() {
    // This would typically fetch from the server
    // For now, we'll simulate with local data
    updateScanHistoryTable();
    updateProgress();
}

function scanBox() {
    const boxQr = document.getElementById('box_qr').value.trim();

    if (!boxQr) {
        showError('Please enter or scan a box QR code');
        return;
    }

    // Show loading
    const scanBtn = document.getElementById('scanBox');
    scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Scanning...';
    scanBtn.disabled = true;

    // Process scan
    $.ajax({
        url: '{{ route("api.casemark.scan") }}',
        method: 'POST',
        data: {
            case_no: caseNo,
            box_qr: boxQr
        },
        success: function(response) {
            if (response.success) {
                showSuccess(response.data);
                addToScanHistory(response.data);
                clearInput();
                updateProgress();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showError(response ? response.message : 'An error occurred while scanning');
        },
        complete: function() {
            // Reset button
            scanBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Scan Box';
            scanBtn.disabled = false;
            document.getElementById('box_qr').focus();
        }
    });
}

function showSuccess(data) {
    const successDetails = document.getElementById('successDetails');
    successDetails.innerHTML = `
        <div><strong>Box:</strong> ${data.box_no}</div>
        <div><strong>Part:</strong> ${data.part_no}</div>
        <div><strong>Name:</strong> ${data.part_name}</div>
        <div><strong>Quantity:</strong> ${data.quantity}</div>
    `;

    document.getElementById('successModal').classList.remove('hidden');
    document.getElementById('successModal').classList.add('flex');
}

function showError(message) {
    const errorDetails = document.getElementById('errorDetails');
    errorDetails.textContent = message;

    document.getElementById('errorModal').classList.remove('hidden');
    document.getElementById('errorModal').classList.add('flex');
}

function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
    document.getElementById('successModal').classList.remove('flex');
}

function closeErrorModal() {
    document.getElementById('errorModal').classList.add('hidden');
    document.getElementById('errorModal').classList.remove('flex');
}

function addToScanHistory(data) {
    scanHistory.unshift({
        ...data,
        scan_time: new Date().toLocaleString()
    });

    updateScanHistoryTable();
    updateRecentScans();
}

function updateScanHistoryTable() {
    const tbody = document.getElementById('scanHistoryBody');

    if (scanHistory.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    No boxes scanned yet
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = scanHistory.map((item, index) => `
        <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.box_no}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_no}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.quantity}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.scan_time}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Scanned
                </span>
            </td>
        </tr>
    `).join('');
}

function updateRecentScans() {
    const recentScans = document.getElementById('recentScans');
    const recent = scanHistory.slice(0, 5);

    if (recent.length === 0) {
        recentScans.innerHTML = '<div class="text-sm text-gray-500">No recent scans</div>';
        return;
    }

    recentScans.innerHTML = recent.map(item => `
        <div class="flex items-center justify-between p-2 bg-green-50 rounded text-xs">
            <div>
                <div class="font-medium">${item.box_no}</div>
                <div class="text-gray-500">${item.part_no}</div>
            </div>
            <div class="text-green-600">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    `).join('');
}

function updateProgress() {
    const scanCount = document.getElementById('scanCount');
    const currentProgress = document.getElementById('currentProgress');

    scanCount.textContent = scanHistory.length;
    currentProgress.textContent = `${scanHistory.length}/10`; // Assume 10 total boxes
}

function clearInput() {
    document.getElementById('box_qr').value = '';
    document.getElementById('scanBox').disabled = true;
    document.getElementById('box_qr').focus();
}

function showContentList() {
    window.open(`{{ route('casemark.content-list', ':caseNo') }}`.replace(':caseNo', caseNo), '_blank');
}

function markAllPacked() {
    if (confirm('Are you sure you want to mark this container as packed?')) {
        $.ajax({
            url: '{{ route("api.casemark.mark.packed") }}',
            method: 'POST',
            data: {
                case_no: caseNo
            },
            success: function(response) {
                if (response.success) {
                    alert('Container marked as packed successfully!');
                    window.location.href = '{{ route("casemark.list") }}';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while marking as packed');
            }
        });
    }
}

// Auto-focus on input
window.addEventListener('load', function() {
    document.getElementById('box_qr').focus();
});
</script>
@endsection