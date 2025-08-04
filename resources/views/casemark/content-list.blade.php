@extends('layouts.app')

@section('title', 'Content List - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900">CONTENT LIST</h1>
    </div>

    <!-- Container Scanner Section -->
    <div id="containerScanner" class="mb-2">
        <div class="bg-white rounded-lg shadow p-4">
            <!-- p-4 lebih kecil dari p-6 -->
            <!-- <h2 class="text-base font-semibold text-gray-900 mb-3">Scan Container Barcode</h2> -->
            <div class="mb-3">
                <!-- <label for="containerBarcode" class="block text-xs font-medium text-gray-700 mb-1">
                    Container Barcode
                </label> -->
                <input type="text" id="containerBarcode"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base font-mono"
                    placeholder="Scan container qr code to load case data..." autofocus>
            </div>
            <button type="button" onclick="scanContainer()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium flex items-center text-xs">
                <i class="fas fa-qrcode mr-2"></i>
                Scan Container
            </button>
        </div>
    </div>

    <!-- Box Scanner Section (Hidden initially) -->
    <div id="boxScanner" class="mb-2 hidden">
        <div class="bg-white rounded-lg shadow p-4">
            <!-- <h2 class="text-base font-semibold text-gray-900 mb-3">Scan Box Barcode</h2> -->
            <div class="mb-3">
                <!-- <label for="boxBarcode" class="block text-xs font-medium text-gray-700 mb-1">
                    Box Barcode
                </label> -->
                <input type="text" id="boxBarcode"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-base font-mono"
                    placeholder="Scan box qr code...">
            </div>
            <button type="button" onclick="scanBox()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium flex items-center text-xs">
                <i class="fas fa-barcode mr-2"></i>
                Scan Box
            </button>
        </div>
    </div>

    <!-- Final Barcode Scanner Section (Hidden initially) -->
    <!-- <div id="finalBarcodeScanner" class="mb-2 hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Scan Final Barcode</h2>
            <div class="mb-2">
                <label for="finalBarcode" class="block text-sm font-medium text-gray-700 mb-2">
                    Final Barcode
                </label>
                <input type="text"
                    id="finalBarcode"
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg font-mono"
                    placeholder="Scan final barcode...">
            </div>
            <button type="button" onclick="scanFinalBarcode()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium flex items-center">
                <i class="fas fa-qrcode mr-2"></i>
                Scan Final Barcode
            </button>
        </div>
    </div> -->

    <!-- Case Information Section (Hidden initially) -->
    <div id="caseInfo" class="mb-2 hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Case Information</h2>
            <div class="grid grid-cols-2 gap-4">
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

                </div>
                <div class="space-y-2">
                    <div class="flex">
                        <span class="font-semibold w-32">Case No.</span>
                        <span class="mr-4">:</span>
                        <span id="caseNo">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-semibold w-32">Prod Month</span>
                        <span class="mr-4">:</span>
                        <span id="prodMonth">-</span>
                    </div>
                    <div class="flex hidden">
                        <span class="font-semibold w-32">C/SIZE (CM)</span>
                        <span class="mr-4">:</span>
                        <span id="caseSize">-</span>
                    </div>
                    <div class="flex hidden">
                        <span class="font-semibold w-32">G/W</span>
                        <span class="mr-4">:</span>
                        <span id="grossWeight">-</span>
                    </div>
                    <div class="flex hidden">
                        <span class="font-semibold w-32">N/W</span>
                        <span class="mr-4">:</span>
                        <span id="netWeight">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Progress Table -->
    <div class="mb-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Scan Progress</h2>
        <div class="overflow-x-auto">
            <table id="scanProgressTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No.
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part
                            Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Progress
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No data - Scan container qr code
                            to
                            load case data</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Details Table -->
    <div class="mb-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Details</h2>
        <div class="overflow-x-auto">
            <table id="detailsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Box No.
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Part
                            Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Qty/box
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No data - Scan container qr code to
                            load case data</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Submit Button (Hidden initially) -->
    <!-- Submit Section (Updated) - Ganti bagian Submit Button dengan ini -->
    <div id="submitContainer" class="mt-8 text-center hidden">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center justify-center mb-2">
                <i class="fas fa-check-circle text-green-500 text-3xl mr-3"></i>
                <h3 class="text-lg font-semibold text-green-900">All Items Scanned!</h3>
            </div>
            <p class="text-green-700 mb-6">All items have been successfully scanned. Choose your submit method:</p>

            <!-- Submit Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
                <!-- Manual Submit Button -->
                <div class="bg-white border border-green-200 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-2">Manual Submit</h4>
                    <p class="text-sm text-gray-600 mb-2">Click the button below to submit this case manually.</p>
                    <button type="button" onclick="submitCase()"
                        class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-medium">
                        <i class="fas fa-check mr-2"></i>
                        Submit Case
                    </button>
                </div>

                <!-- Final Barcode Submit -->
                <div class="bg-white border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">Final Barcode Submit</h4>
                    <p class="text-sm text-gray-600 mb-2">Scan the final barcode to submit this case automatically.</p>
                    <div class="mb-3">
                        <input type="text" id="finalBarcode"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono"
                            placeholder="Scan final barcode...">
                    </div>
                    <button type="button" onclick="scanFinalBarcode()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
                        <i class="fas fa-qrcode mr-2"></i>
                        Scan Final Barcode
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Notification Toast -->
<div id="successToast"
    class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50"
    style="display: none;">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-3"></i>
        <div>
            <h4 class="font-semibold" id="toastTitle">Success!</h4>
            <p class="text-sm opacity-90" id="toastMessage">Operation completed successfully.</p>
        </div>
    </div>
</div>

<!-- Error Notification Toast -->
<div id="errorToast"
    class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50"
    style="display: none;">
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

// Tambahkan di bagian atas script, setelah deklarasi variabel
function validateBarcodeFormat(barcode, expectedType) {
    const parts = barcode.split('#');

    if (expectedType === 'container') {
        // Container should have 4 parts
        if (parts.length !== 4) {
            // Check if it's actually a box barcode (8 parts)
            if (parts.length === 8) {
                return {
                    valid: false,
                    message: 'This is a box barcode. Please scan the container barcode first, then scan box barcodes.'
                };
            }
            return {
                valid: false,
                message: 'Invalid container barcode format. Expected 4 parts separated by #, got ' + parts.length +
                    ' parts.'
            };
        }

        // Second part should not start with numbers only (to distinguish from box)
        if (/^\d+/.test(parts[1])) {
            return {
                valid: false,
                message: 'This appears to be a box barcode. Please scan the container barcode first.'
            };
        }
    } else if (expectedType === 'box') {
        // Box should have 8 parts
        if (parts.length !== 8) {
            // Check if it's actually a container barcode (4 parts)
            if (parts.length === 4) {
                return {
                    valid: false,
                    message: 'This is a container barcode. Please scan container barcode first, then scan box barcodes.'
                };
            }
            return {
                valid: false,
                message: 'Invalid box barcode format. Expected 8 parts separated by #, got ' + parts.length + ' parts.'
            };
        }

        // Second part should start with numbers (part number)
        if (!/^\d+/.test(parts[1])) {
            return {
                valid: false,
                message: 'This appears to be a container barcode. Please scan a box barcode.'
            };
        }
    }

    return {
        valid: true
    };
}

// Container barcode scanner
document.getElementById('containerBarcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        scanContainer();
    }
});

// Auto-enter for container barcode (trigger scan automatically after barcode input)
document.getElementById('containerBarcode').addEventListener('input', function(e) {
    const barcode = this.value;
    const parts = barcode.split('#');

    // Auto-trigger scan for container format (4 parts with #) or box format (8 parts with #)
    if ((parts.length === 4 && barcode.length >= 25) || (parts.length === 8 && barcode.length >= 40)) {
        setTimeout(() => {
            if (this.value === barcode) {
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
    const parts = barcode.split('#');

    // Auto-trigger scan for box format (8 parts with #) or container format (4 parts with #)
    if ((parts.length === 8 && barcode.length >= 40) || (parts.length === 4 && barcode.length >= 25)) {
        setTimeout(() => {
            if (this.value === barcode) {
                scanBox();
            }
        }, 100);
    }
});

// Ensure box barcode field maintains focus when box scanner is visible
document.getElementById('boxBarcode').addEventListener('blur', function(e) {
    // Only refocus if box scanner is visible and we're not switching to final barcode
    setTimeout(() => {
        if (document.getElementById('boxScanner').classList.contains('hidden') === false &&
            !document.getElementById('finalBarcode').contains(document.activeElement)) {
            this.focus();
        }
    }, 50);
});

// ESC key support for manual clearing
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Clear current active input field
        const activeElement = document.activeElement;
        if (activeElement && (activeElement.id === 'containerBarcode' || activeElement.id === 'boxBarcode' ||
                activeElement.id === 'finalBarcode')) {
            activeElement.value = '';
        }
    }
});

// Add ESC key support for final barcode field
document.addEventListener('DOMContentLoaded', function() {
    // Existing focus on container barcode
    document.getElementById('containerBarcode').focus();

    // Event delegation untuk final barcode (akan bekerja untuk field yang muncul setelah DOM loaded)
    document.addEventListener('keypress', function(e) {
        if (e.target && e.target.id === 'finalBarcode') {
            if (e.key === 'Enter') {
                console.log('Enter pressed on finalBarcode, calling scanFinalBarcode');
                scanFinalBarcode();
            }
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'finalBarcode') {
            const barcode = e.target.value;
            console.log('Final barcode input:', barcode);
            const hashCount = (barcode.match(/#/g) || []).length;

            // Jika format final barcode pakai #, minimal 3 parts
            if (hashCount >= 3 && barcode.length >= 20) {
                console.log('Auto-triggering scan (final barcode format)');
                setTimeout(() => {
                    if (e.target.value === barcode) {
                        scanFinalBarcode();
                    }
                }, 100);
            }
            // Jika format final barcode sama dengan barcode container (tanpa #, minimal 11 karakter)
            else if (barcode.length >= 11 && !barcode.includes('#')) {
                console.log('Auto-triggering scan (container format)');
                setTimeout(() => {
                    if (e.target.value === barcode) {
                        scanFinalBarcode();
                    }
                }, 100);
            }
        }
    });

    // ESC key support untuk final barcode
    document.addEventListener('keydown', function(e) {
        if (e.target && e.target.id === 'finalBarcode' && e.key === 'Escape') {
            e.target.value = '';
            e.target.focus();
        }
    });
});

function scanContainer() {
    const barcode = document.getElementById('containerBarcode').value;

    if (!barcode) {
        showErrorToast('Please enter container barcode');
        return;
    }

    const validation = validateBarcodeFormat(barcode, 'container');
    if (!validation.valid) {
        showErrorToast(validation.message);
        // Clear and refocus
        setTimeout(() => {
            document.getElementById('containerBarcode').value = '';
            document.getElementById('containerBarcode').focus();
        }, 3000);
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
                document.getElementById('containerScanner').classList.add('hidden');
                document.getElementById('submitContainer').classList.add('hidden');

                // Update progress tables
                updateProgressTables(response.data);

                // Show success notification
                showSuccessToast('Container scanned successfully!', 'Please scan box barcodes now.');

                // Focus on box scanner
                setTimeout(() => {
                    document.getElementById('boxBarcode').focus();
                }, 500);

                // Clear container barcode input
                document.getElementById('containerBarcode').value = '';

            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error scanning container');

            // Clear input field even when error occurs
            setTimeout(() => {
                document.getElementById('containerBarcode').value = '';
                document.getElementById('containerBarcode').focus();
            }, 3000); // Clear after 3 seconds to give user time to read error
        }
    });
}

// Update fungsi scanBox untuk memberikan feedback yang lebih baik saat completion
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

    const validation = validateBarcodeFormat(barcode, 'box');
    if (!validation.valid) {
        showErrorToast(validation.message);
        // Clear and refocus
        setTimeout(() => {
            document.getElementById('boxBarcode').value = '';
            document.getElementById('boxBarcode').focus();
        }, 3000);
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

                // Clear box barcode input
                document.getElementById('boxBarcode').value = '';

                // Focus back to box barcode input for next scan
                // Only focus if not complete (if complete, checkCompletion will handle focus)
                setTimeout(() => {
                    if (document.getElementById('boxScanner').classList.contains('hidden') ===
                        false) {
                        document.getElementById('boxBarcode').focus();
                    }
                }, 100);
            } else {
                showErrorModal(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error scanning box');

            // Clear input field even when error occurs
            setTimeout(() => {
                document.getElementById('boxBarcode').value = '';
                document.getElementById('boxBarcode').focus();
            }, 3000);
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
                                    ${item.is_scanned ? 'Scanned' : 'Unscanned'}
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

                // Cek jika sudah complete, sembunyikan box scanner, tampilkan submitContainer (bukan finalBarcodeScanner)
                if (data.scannedBoxes >= data.totalBoxes && data.totalBoxes > 0) {
                    document.getElementById('boxScanner').classList.add('hidden');
                    const submit = document.getElementById('submitContainer');
                    submit.classList.remove('hidden');
                    setTimeout(() => {
                        submit.classList.add('visible');
                        document.getElementById('finalBarcode').focus();
                        // Hapus attachFinalBarcodeListeners() karena sudah menggunakan event delegation
                    }, 100);
                } else {
                    document.getElementById('boxScanner').classList.remove('hidden');
                    const submit = document.getElementById('submitContainer');
                    submit.classList.remove('visible');
                    setTimeout(() => {
                        submit.classList.add('hidden');
                    }, 500);

                    // Focus back to box barcode input if box scanner is visible
                    setTimeout(() => {
                        if (document.getElementById('boxScanner').classList.contains('hidden') ===
                            false) {
                            document.getElementById('boxBarcode').focus();
                        }
                    }, 600);
                }
            }
        },
        error: function(xhr) {
            console.error('Error updating progress:', xhr);
        }
    });
}

// Update fungsi showSubmitButton untuk auto focus ke final barcode field
function showSubmitButton(totalExpected) {
    const submitContainer = document.getElementById('submitContainer');
    submitContainer.classList.remove('hidden');

    // Auto focus ke final barcode field setelah submit container ditampilkan
    setTimeout(() => {
        const finalBarcodeInput = document.getElementById('finalBarcode');
        if (finalBarcodeInput) {
            finalBarcodeInput.focus();
            finalBarcodeInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }, 500); // Delay 500ms untuk memastikan element sudah terrender
}

// Update fungsi checkCompletion untuk langsung focus jika sudah complete
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

                    // Show completion notification dengan instruksi
                    showSuccessToast(
                        'All boxes scanned!',
                        'Please scan final barcode or click submit button to complete the case.'
                    );
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
                showSuccessToast('Case submitted successfully!',
                    'You can continue scanning other containers.');

                // Reset the form for next container scan
                resetForm();
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

// Update fungsi scanFinalBarcode untuk better error handling
function scanFinalBarcode() {
    const barcode = document.getElementById('finalBarcode').value;

    if (!barcode) {
        showErrorToast('Please enter final barcode');
        // Keep focus on final barcode field
        setTimeout(() => {
            document.getElementById('finalBarcode').focus();
        }, 100);
        return;
    }

    if (!currentCaseId) {
        showErrorToast('No active case to submit');
        return;
    }

    $.ajax({
        url: '/api/casemark/scan-final-barcode',
        method: 'POST',
        data: {
            barcode: barcode,
            case_id: currentCaseId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showSuccessToast('Case submitted successfully via final barcode!',
                    'You can continue scanning other containers.');

                // Reset the form for next container scan
                resetForm();

                // Clear final barcode input
                document.getElementById('finalBarcode').value = '';
            } else {
                showErrorModal(response.message);
                // Keep focus on final barcode field for retry
                setTimeout(() => {
                    document.getElementById('finalBarcode').value = '';
                    document.getElementById('finalBarcode').focus();
                }, 1000);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showErrorToast(response ? response.message : 'Error scanning final barcode');

            // Clear input field and keep focus for retry
            setTimeout(() => {
                document.getElementById('finalBarcode').value = '';
                document.getElementById('finalBarcode').focus();
            }, 1000);
        }
    });
}

// Handle error modal - show error toast instead and auto-clear input
function showErrorModal(message) {
    showErrorToast(message, true);
}

function showSuccessToast(title, message, autoClearInput = false) {
    const toast = document.getElementById('successToast');
    if (toast) {
        document.getElementById('toastTitle').textContent = title;
        document.getElementById('toastMessage').textContent = message;

        // Show and animate
        toast.style.display = 'block';
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 10);

        // Auto hide after 3 seconds
        setTimeout(() => {
            hideSuccessToast();

            // Auto clear input if specified
            if (autoClearInput) {
                const activeElement = document.activeElement;
                if (activeElement && (activeElement.id === 'containerBarcode' || activeElement.id ===
                        'boxBarcode')) {
                    activeElement.value = '';
                    // Focus back to the cleared field
                    setTimeout(() => {
                        if (currentCaseId && document.getElementById('boxScanner').classList.contains(
                                'hidden') === false) {
                            document.getElementById('boxBarcode').focus();
                        } else if (!currentCaseId) {
                            document.getElementById('containerBarcode').focus();
                        }
                    }, 100);
                }
            }
        }, 3000);
    }
}

function hideSuccessToast() {
    const toast = document.getElementById('successToast');
    if (toast) {
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');

        // Hide completely after animation
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }
}

function showErrorToast(message, autoClearInput = true) {
    const toast = document.getElementById('errorToast');
    if (toast) {
        document.getElementById('errorToastMessage').textContent = message;

        // Show and animate
        toast.style.display = 'block';
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 10);

        // Auto hide after 3 seconds
        setTimeout(() => {
            hideErrorToast();

            // Auto clear input and refocus after error
            if (autoClearInput) {
                const activeElement = document.activeElement;
                if (activeElement && (activeElement.id === 'containerBarcode' || activeElement.id ===
                        'boxBarcode')) {
                    activeElement.value = '';
                    activeElement.focus();
                } else {
                    // If no active element, focus on the appropriate field
                    if (currentCaseId) {
                        document.getElementById('boxBarcode').focus();
                    } else {
                        document.getElementById('containerBarcode').focus();
                    }
                }
            }
        }, 3000);
    }
}

function hideErrorToast() {
    const toast = document.getElementById('errorToast');
    if (toast) {
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');

        // Hide completely after animation
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }
}

function resetForm() {
    // Reset current case data
    currentCaseId = null;
    currentCaseData = null;

    // Hide case info and box scanner
    document.getElementById('caseInfo').classList.add('hidden');
    document.getElementById('boxScanner').classList.add('hidden');
    const submit = document.getElementById('submitContainer');
    submit.classList.remove('visible');
    setTimeout(() => {
        submit.classList.add('hidden');
    }, 500);
    document.getElementById('containerScanner').classList.remove('hidden');

    // Clear input fields
    document.getElementById('containerBarcode').value = '';
    document.getElementById('boxBarcode').value = '';
    document.getElementById('finalBarcode').value = '';

    // Clear tables
    document.querySelector('#scanProgressTable tbody').innerHTML =
        '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No data - Scan container barcode to load case data</td></tr>';
    document.querySelector('#detailsTable tbody').innerHTML =
        '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data - Scan container barcode to load case data</td></tr>';

    // Focus on container barcode input
    document.getElementById('containerBarcode').focus();
}
</script>
@endsection