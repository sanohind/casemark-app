@extends('layouts.app')

@section('title', 'Excel Upload - Case Mark System')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">EXCEL UPLOAD</h1>
    </div>

    <!-- Upload Form -->
    <form action="{{ route('casemark.upload.excel') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <!-- File Upload Section -->
        <div class="mb-8">
            <div
                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                <div class="mb-4">
                    <i class="fas fa-upload text-4xl text-gray-400"></i>
                </div>
                <div class="mb-4">
                    <label for="excel_file" class="cursor-pointer">
                        <span
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center">
                            <i class="fas fa-file-excel mr-2"></i>
                            Choose Excel File
                        </span>
                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" class="hidden"
                            required>
                    </label>
                </div>
                <p class="text-sm text-gray-600">
                    Upload Excel file (.xlsx, .xls, .csv) containing content list data
                </p>
                <div id="fileName" class="mt-2 text-sm font-medium text-green-600 hidden"></div>
            </div>
            @error('excel_file')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Preview Data Section -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Preview Data</h2>

            <div class="grid grid-cols-2 gap-8 bg-gray-50 p-6 rounded-lg">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                        <input type="text" name="destination" value="{{ old('destination', 'PEMSB') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        @error('destination')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order No.</label>
                        <input type="text" name="order_no" value="{{ old('order_no', '0') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('order_no')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prod Month</label>
                        <input type="text" name="prod_month" value="{{ old('prod_month', date('Ym')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="YYYYMM" required>
                        @error('prod_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Case No.</label>
                        <input type="text" name="case_no" value="{{ old('case_no') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="I2E-SAN-00435" required>
                        @error('case_no')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">C/SIZE (CM)</label>
                        <input type="text" name="case_size" value="{{ old('case_size', '149x113x75') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="149x113x75" required>
                        @error('case_size')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">G/W (KGS)</label>
                            <input type="number" step="0.01" name="gross_weight"
                                value="{{ old('gross_weight', '136') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            @error('gross_weight')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N/W (KGS)</label>
                            <input type="number" step="0.01" name="net_weight" value="{{ old('net_weight', '54') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            @error('net_weight')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Excel Format Guide -->
        <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 mb-2">Excel Format Requirements:</h3>
            <div class="text-sm text-blue-800">
                <p class="mb-2">Ensure your Excel file contains the following columns:</p>
                <div class="grid grid-cols-2 gap-4">
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>box_no</strong> - Box number (e.g., BOX_01)</li>
                        <li><strong>part_no</strong> - Part number</li>
                        <li><strong>part_name</strong> - Part description</li>
                    </ul>
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>quantity</strong> - Quantity per box</li>
                        <li><strong>remark</strong> - Additional notes (optional)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('casemark.content-list') }}"
                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Cancel
            </a>
            <button type="submit" id="uploadBtn"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="uploadText">Upload & Process</span>
                <span id="uploadLoading" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Processing...
                </span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('excel_file');
    const fileName = document.getElementById('fileName');
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadText = document.getElementById('uploadText');
    const uploadLoading = document.getElementById('uploadLoading');

    // File input change handler
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            const file = e.target.files[0];
            fileName.textContent = `Selected: ${file.name}`;
            fileName.classList.remove('hidden');

            // Auto-generate case number based on current timestamp
            const caseInput = document.querySelector('input[name="case_no"]');
            if (!caseInput.value) {
                const timestamp = new Date().getTime().toString().slice(-5);
                caseInput.value = `I2E-SAN-${timestamp}`;
            }
        }
    });

    // Form submit handler
    uploadForm.addEventListener('submit', function() {
        uploadBtn.disabled = true;
        uploadText.classList.add('hidden');
        uploadLoading.classList.remove('hidden');
    });

    // Drag and drop functionality
    const dropZone = document.querySelector('.border-dashed');

    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            const event = new Event('change', {
                bubbles: true
            });
            fileInput.dispatchEvent(event);
        }
    });
});
</script>
@endsection