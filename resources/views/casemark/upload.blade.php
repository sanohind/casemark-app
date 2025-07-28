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
                        <input type="text" name="destination" value="{{ old('destination', '') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="SMDD" required>
                        @error('destination')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order No.</label>
                        <input type="text" name="order_no" value="{{ old('order_no') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500
                            " placeholder="J986382T1G" required>
                        @error('order_no')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prod Month</label>
                        <input type="text" name="prod_month" value=""
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
                            placeholder="KSJ-BOY-37389" required>
                        @error('case_no')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">C/SIZE (CM)</label>
                        <input type="text" name="case_size" value="{{ old('case_size', '') }}"
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
                                value="{{ old('gross_weight', '') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            @error('gross_weight')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">N/W (KGS)</label>
                            <input type="number" step="0.01" name="net_weight" value="{{ old('net_weight', '') }}"
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

        <!-- Box Details Preview Section -->
        <div class="mb-8" id="boxDetailsPreview" style="display: none;">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Box Details Preview</h2>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">NO.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">BOX NO.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">PART NO</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">PART NAME</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">QTY/BOX</th>
                            </tr>
                        </thead>
                        <tbody id="boxDetailsTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Data akan diisi secara dinamis -->
                        </tbody>
                    </table>
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
    
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            const file = e.target.files[0];
            fileName.textContent = `Memproses: ${file.name}`;
            fileName.classList.remove('hidden', 'text-red-600', 'text-green-600');
            fileName.classList.add('text-blue-600');
            
            // Tambahkan loading state
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            uploadBtn.classList.add('opacity-50');
            
            previewExcelFile(file).finally(() => {
                uploadBtn.disabled = false;
                uploadBtn.classList.remove('opacity-50');
            });
        }
    });

    async function previewExcelFile(file) {
        const formData = new FormData();
        formData.append('excel_file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        try {
            const response = await fetch('/api/casemark/preview-excel', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                }
            });

            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Gagal memproses file');
            }

            if (result.success) {
                // Update semua field form
                updateFormFields(result.data);
                fileName.textContent = `File dipilih: ${file.name}`;
                fileName.classList.remove('text-blue-600', 'text-red-600');
                fileName.classList.add('text-green-600');
                
                // Hapus pesan error jika ada
                const existingError = document.querySelector('.text-red-600');
                if (existingError) {
                    existingError.remove();
                }
                
                // Debug information (akan dihapus di production)
                if (result.debug) {
                    console.log('Debug info:', result.debug);
                    console.log('Extracted data:', result.data);
                    
                    // Tampilkan debug info di halaman untuk troubleshooting
                    const debugDiv = document.createElement('div');
                    debugDiv.className = 'mt-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded text-xs';
                    debugDiv.innerHTML = `
                        <strong>Debug Info:</strong><br>
                        <strong>Column Mapping:</strong> ${JSON.stringify(result.debug.column_mapping)}<br>
                        <strong>Row 19:</strong> ${JSON.stringify(result.debug.row19)}<br>
                        <strong>Row 20:</strong> ${JSON.stringify(result.debug.row20)}<br>
                        <strong>Row 21:</strong> ${JSON.stringify(result.debug.row21)}<br>
                        <strong>Row 22:</strong> ${JSON.stringify(result.debug.row22)}<br>
                        <strong>Row 23:</strong> ${JSON.stringify(result.debug.row23)}<br>
                        <strong>Extracted Values:</strong> ${JSON.stringify(result.debug.extracted_values)}<br>
                        <strong>Content List Preview:</strong> ${JSON.stringify(result.debug.content_list_preview)}
                    `;
                    
                    const previewSection = document.querySelector('.mb-8');
                    previewSection.appendChild(debugDiv);
                    
                    // Hapus debug info setelah 10 detik
                    setTimeout(() => {
                        debugDiv.remove();
                    }, 10000);
                }
            } else {
                throw new Error(result.message || 'Format file tidak sesuai');
            }
        } catch (error) {
            console.error('Error:', error);
            fileName.textContent = `Error: ${error.message}`;
            fileName.classList.remove('text-blue-600', 'text-green-600');
            fileName.classList.add('text-red-600');
            
            // Tampilkan pesan error yang lebih detail
            const errorMessage = document.createElement('div');
            errorMessage.className = 'mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
            errorMessage.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i>Error: ${error.message}`;
            
            const previewSection = document.querySelector('.mb-8');
            previewSection.appendChild(errorMessage);
            
            // Hapus pesan setelah 5 detik
            setTimeout(() => {
                errorMessage.remove();
            }, 5000);
        }
    }

    function updateFormFields(data) {
        // Mapping antara field form dan key dari response
        const fieldMap = {
            'destination': 'destination',
            'order_no': 'order_no',
            'prod_month': 'prod_month',
            'case_no': 'case_no',
            'case_size': 'case_size',
            'gross_weight': 'gross_weight',
            'net_weight': 'net_weight'
        };

        // Update setiap field
        Object.entries(fieldMap).forEach(([fieldName, dataKey]) => {
            const input = document.querySelector(`input[name="${fieldName}"]`);
            if (input && data[dataKey]) {
                input.value = data[dataKey];
                console.log(`Mengisi ${fieldName} dengan:`, data[dataKey]);
                
                // Tambahkan visual feedback
                input.classList.add('bg-green-50', 'border-green-300');
                setTimeout(() => {
                    input.classList.remove('bg-green-50', 'border-green-300');
                }, 2000);
            }
        });

        // Update Box Details Preview
        updateBoxDetailsPreview(data);

        // Tampilkan pesan sukses
        const successMessage = document.createElement('div');
        successMessage.className = 'mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded';
        successMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Data berhasil diekstrak dari Excel file!';
        
        const previewSection = document.querySelector('.mb-8');
        previewSection.appendChild(successMessage);
        
        // Hapus pesan setelah 5 detik
        setTimeout(() => {
            successMessage.remove();
        }, 5000);
    }

    function updateBoxDetailsPreview(data) {
        const boxDetailsPreview = document.getElementById('boxDetailsPreview');
        const tableBody = document.getElementById('boxDetailsTableBody');
        
        // Tampilkan section preview
        boxDetailsPreview.style.display = 'block';
        
        // Kosongkan tabel
        tableBody.innerHTML = '';
        
        // Jika ada data content list preview
        if (data.content_list_preview && data.content_list_preview.length > 0) {
            data.content_list_preview.forEach((item, index) => {
                const row = document.createElement('tr');
                row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.no || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.box_no || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_no || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.part_name || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.quantity || '0'}</td>
                `;
                
                tableBody.appendChild(row);
            });
        } else {
            // Jika tidak ada data, tampilkan pesan
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                    Tidak ada data box details yang ditemukan
                </td>
            `;
            tableBody.appendChild(row);
        }
    }
});
</script>
@endsection