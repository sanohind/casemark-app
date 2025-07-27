@extends('layouts.app')

@section('title', 'Scan Container - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">SCAN CONTAINER</h1>
        <p class="text-gray-600">Scan container QR code to start box scanning process</p>
    </div>

    <!-- Scan Area -->
    <div class="max-w-2xl mx-auto">
        <!-- QR Scanner Section -->
        <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6">
            <div class="mb-6">
                <i class="fas fa-qrcode text-6xl text-gray-400"></i>
            </div>

            <!-- Manual Input -->
            <div class="mb-6">
                <label for="container_qr" class="block text-sm font-medium text-gray-700 mb-2">
                    Container QR Code / Case Number
                </label>
                <input type="text" id="container_qr" placeholder="Scan or enter container QR code..."
                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-center"
                    autofocus>
            </div>

            <!-- Camera Scanner (if supported) -->
            <div class="mb-6">
                <button id="startCamera"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
                    <i class="fas fa-camera mr-2"></i>
                    Use Camera Scanner
                </button>
            </div>

            <!-- Video element for camera -->
            <video id="scanner" width="400" height="300" class="hidden mx-auto border rounded"></video>
            <canvas id="canvas" class="hidden"></canvas>
        </div>

        <!-- Process Button -->
        <div class="text-center mb-6">
            <button id="processContainer" onclick="processContainer()"
                class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-md font-medium text-lg disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
                <i class="fas fa-check mr-2"></i>
                Confirm Container
            </button>
        </div>

        <!-- Container Info Display -->
        <div id="containerInfo" class="hidden bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-green-900 mb-4">Container Confirmed</h3>
            <div id="containerDetails" class="space-y-2 text-sm text-green-800">
                <!-- Container details will be populated here -->
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('casemark.scan.box') }}" id="scanBoxLink"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">
                    Start Box Scanning
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Error Display -->
        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
            <span id="errorText" class="text-red-700"></span>
        </div>

        <!-- Recent Containers -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Containers</h3>
            <div class="space-y-2">
                @foreach(\App\Models\CaseModel::latest()->take(5)->get() as $case)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
                    onclick="selectContainer('{{ $case->case_no }}')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-box text-gray-400"></i>
                        <div>
                            <div class="font-medium text-gray-900">{{ $case->case_no }}</div>
                            <div class="text-sm text-gray-500">{{ $case->destination }} - {{ $case->prod_month }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">{{ $case->progress }}</div>
                        <div class="text-xs text-gray-500">
                            @if($case->status == 'packed')
                            <span class="text-green-600">Packed</span>
                            @elseif($case->status == 'active')
                            <span class="text-blue-600">Active</span>
                            @else
                            <span class="text-gray-600">{{ ucfirst($case->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let stream = null;
let scanning = false;

document.addEventListener('DOMContentLoaded', function() {
    const containerInput = document.getElementById('container_qr');
    const processBtn = document.getElementById('processContainer');
    const startCameraBtn = document.getElementById('startCamera');
    const scanner = document.getElementById('scanner');

    // Enable/disable process button based on input
    containerInput.addEventListener('input', function() {
        processBtn.disabled = !this.value.trim();
    });

    // Enter key to process
    containerInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            processContainer();
        }
    });

    // Camera scanner button
    startCameraBtn.addEventListener('click', function() {
        if (!scanning) {
            startCamera();
        } else {
            stopCamera();
        }
    });
});

function selectContainer(caseNo) {
    document.getElementById('container_qr').value = caseNo;
    document.getElementById('processContainer').disabled = false;
    processContainer();
}

function processContainer() {
    const containerQr = document.getElementById('container_qr').value.trim();

    if (!containerQr) {
        showError('Please enter or scan a container QR code');
        return;
    }

    hideError();
    showLoading();

    // Extract case number from QR code (assuming format: CASE_NO or just case number)
    const caseNo = containerQr.includes('|') ? containerQr.split('|')[0] : containerQr;

    // Validate container
    $.ajax({
        url: '{{ route("casemark.content-list", ":caseNo") }}'.replace(':caseNo', caseNo),
        method: 'GET',
        success: function(response) {
            // If successful, show container info
            showContainerInfo(caseNo);
            hideLoading();
        },
        error: function(xhr) {
            if (xhr.status === 404) {
                showError('Container not found. Please check the QR code or upload content list first.');
            } else {
                showError('Error validating container. Please try again.');
            }
            hideLoading();
        }
    });
}

function showContainerInfo(caseNo) {
    // Fetch container details and display
    $.ajax({
        url: '/api/casemark/container-info/' + caseNo,
        method: 'GET',
        success: function(data) {
            const containerInfo = document.getElementById('containerInfo');
            const containerDetails = document.getElementById('containerDetails');
            const scanBoxLink = document.getElementById('scanBoxLink');

            containerDetails.innerHTML = `
                <div><strong>Case No:</strong> ${data.case_no}</div>
                <div><strong>Destination:</strong> ${data.destination}</div>
                <div><strong>Production Month:</strong> ${data.prod_month}</div>
                <div><strong>Case Size:</strong> ${data.case_size}</div>
                <div><strong>Progress:</strong> ${data.progress}</div>
                <div><strong>Status:</strong> ${data.status}</div>
            `;

            // Update scan box link with case number
            scanBoxLink.href = '{{ route("casemark.scan.box") }}?case_no=' + caseNo;

            containerInfo.classList.remove('hidden');
        },
        error: function() {
            // Fallback if API doesn't exist, use basic info
            const containerInfo = document.getElementById('containerInfo');
            const containerDetails = document.getElementById('containerDetails');
            const scanBoxLink = document.getElementById('scanBoxLink');

            containerDetails.innerHTML = `
                <div><strong>Case No:</strong> ${caseNo}</div>
                <div><strong>Status:</strong> Ready for scanning</div>
            `;

            scanBoxLink.href = '{{ route("casemark.scan.box") }}?case_no=' + caseNo;
            containerInfo.classList.remove('hidden');
        }
    });
}

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    errorText.textContent = message;
    errorDiv.classList.remove('hidden');

    // Auto-hide after 5 seconds
    setTimeout(() => {
        hideError();
    }, 5000);
}

function hideError() {
    document.getElementById('errorMessage').classList.add('hidden');
}

function showLoading() {
    const processBtn = document.getElementById('processContainer');
    processBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    processBtn.disabled = true;
}

function hideLoading() {
    const processBtn = document.getElementById('processContainer');
    processBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Confirm Container';
    processBtn.disabled = false;
}

// Camera functionality
function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            })
            .then(function(mediaStream) {
                stream = mediaStream;
                const scanner = document.getElementById('scanner');
                const startCameraBtn = document.getElementById('startCamera');

                scanner.srcObject = stream;
                scanner.classList.remove('hidden');
                scanner.play();

                startCameraBtn.innerHTML = '<i class="fas fa-stop mr-2"></i>Stop Camera';
                startCameraBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                startCameraBtn.classList.add('bg-red-600', 'hover:bg-red-700');

                scanning = true;

                // Start QR detection (simplified - in real implementation use a QR library)
                detectQR();
            })
            .catch(function(error) {
                console.error('Camera access denied:', error);
                showError('Camera access denied. Please use manual input.');
            });
    } else {
        showError('Camera not supported on this device. Please use manual input.');
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    const scanner = document.getElementById('scanner');
    const startCameraBtn = document.getElementById('startCamera');

    scanner.classList.add('hidden');
    startCameraBtn.innerHTML = '<i class="fas fa-camera mr-2"></i>Use Camera Scanner';
    startCameraBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
    startCameraBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');

    scanning = false;
}

function detectQR() {
    // Simplified QR detection - in production, use a proper QR code library like ZXing
    // This is just a placeholder for the QR detection logic
    if (!scanning) return;

    // In real implementation, capture frame and detect QR code
    // For now, we'll just simulate detection after 3 seconds for demo
    setTimeout(() => {
        if (scanning) {
            // Simulate QR detection
            // const detectedCode = 'I2E-SAN-00435'; // This would come from QR detection
            // document.getElementById('container_qr').value = detectedCode;
            // processContainer();
            // stopCamera();

            // Continue detection loop
            detectQR();
        }
    }, 1000);
}

// Barcode scanner support (if available)
if ('BarcodeDetector' in window) {
    const barcodeDetector = new BarcodeDetector({
        formats: ['qr_code']
    });

    function scanFromCamera() {
        const scanner = document.getElementById('scanner');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');

        if (scanner.videoWidth === 0 || scanner.videoHeight === 0) {
            setTimeout(scanFromCamera, 100);
            return;
        }

        canvas.width = scanner.videoWidth;
        canvas.height = scanner.videoHeight;
        ctx.drawImage(scanner, 0, 0);

        barcodeDetector.detect(canvas)
            .then(barcodes => {
                if (barcodes.length > 0) {
                    const qrCode = barcodes[0].rawValue;
                    document.getElementById('container_qr').value = qrCode;
                    processContainer();
                    stopCamera();
                }
            })
            .catch(console.error);

        if (scanning) {
            requestAnimationFrame(scanFromCamera);
        }
    }
}

// Auto-focus on input when page loads
window.addEventListener('load', function() {
    document.getElementById('container_qr').focus();
});
</script>
@endsection