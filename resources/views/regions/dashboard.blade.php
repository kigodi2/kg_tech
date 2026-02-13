@extends('layout')

@section('content')
    <!-- Main Content -->
    <main class="w-full p-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <a href="/dashboard" class="text-blue-600 hover:underline">Dashboard</a>
            <span class="text-gray-400 mx-2">/</span>
            <span class="text-gray-600">Registration Management</span>
        </nav>

        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-gray-200">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Registration Management</h1>
            <p class="text-gray-600">Manage regions, districts, and schools</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-0 border-b border-gray-200 mb-6">
            <button class="tab-btn active px-6 py-3 font-semibold text-gray-700 border-b-2 border-blue-600 text-blue-600" data-tab="regions">
                <i class="fas fa-globe mr-2"></i>Regions
            </button>
            <button class="tab-btn px-6 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700" data-tab="districts">
                <i class="fas fa-map mr-2"></i>Districts
            </button>
            <button class="tab-btn px-6 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700" data-tab="schools">
                <i class="fas fa-school mr-2"></i>Schools
            </button>
        </div>

        <!-- Toolbar -->
        <div class="bg-white p-6 border-b border-gray-200 mb-6">
            <div class="flex gap-3 items-center">
                <input 
                    type="text" 
                    id="regionSearch" 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    placeholder="Search by region name or code..."
                >
                <div class="relative">
                    <button class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition flex items-center gap-2" onclick="toggleMenu('actionMenu')">
                        <i class="fas fa-ellipsis-v"></i> More
                    </button>
                    <div id="actionMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-300 rounded-lg shadow-lg z-40">
                        <button class="btn-action download-template-region block w-full text-left px-4 py-2 hover:bg-gray-100">
                            <i class="fas fa-download mr-2"></i>Download Template
                        </button>
                        <button class="btn-action export-pdf-region block w-full text-left px-4 py-2 hover:bg-gray-100">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </button>
                        <button class="btn-action export-excel-region block w-full text-left px-4 py-2 hover:bg-gray-100">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                        <button class="btn-action import-csv-region block w-full text-left px-4 py-2 hover:bg-gray-100">
                            <i class="fas fa-upload mr-2"></i>Import CSV/Excel
                        </button>
                    </div>
                </div>
                <button class="btn-action add-region bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Region
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Code</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Region Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Districts</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Schools</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700 uppercase text-xs tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="regionsTableBody">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Loading regions...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationControls" class="mt-6"></div>
    </main>

<!-- View Modal -->
<div id="viewRegionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Region Details</h2>
            <button class="view-modal-close text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Region Code</label>
                <p id="viewRegionCode" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800">-</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Region Name</label>
                <p id="viewRegionName" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800">-</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Districts Count</label>
                <p id="viewDistrictsCount" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800">-</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Schools Count</label>
                <p id="viewSchoolsCount" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800">-</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <p id="viewStatus" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800">-</p>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" class="flex-1 view-modal-close bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition">
                    Close
                </button>
                <button type="button" onclick="switchToEdit()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Edit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="addRegionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">Add New Region</h2>
            <button class="modal-close text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        <form id="addRegionForm" class="p-6">
            <input type="hidden" id="editRegionId" value="">
            
            <div class="mb-4">
                <label for="regionCode" class="block text-sm font-semibold text-gray-700 mb-2">Region Code *</label>
                <input 
                    type="text" 
                    id="regionCode" 
                    name="code" 
                    required 
                    placeholder="e.g., 01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
            </div>
            
            <div class="mb-6">
                <label for="regionName" class="block text-sm font-semibold text-gray-700 mb-2">Region Name *</label>
                <input 
                    type="text" 
                    id="regionName" 
                    name="name" 
                    required 
                    placeholder="e.g., Arusha"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
            </div>
            
            <div class="flex gap-3">
                <button type="button" class="flex-1 modal-close bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Add Region
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// Modal functions
document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('addRegionModal').classList.add('hidden');
        resetForm();
    });
});

document.querySelectorAll('.view-modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('viewRegionModal').classList.add('hidden');
    });
});

document.getElementById('addRegionModal').addEventListener('click', (e) => {
    if (e.target.id === 'addRegionModal') {
        document.getElementById('addRegionModal').classList.add('hidden');
        resetForm();
    }
});

document.getElementById('viewRegionModal').addEventListener('click', (e) => {
    if (e.target.id === 'viewRegionModal') {
        document.getElementById('viewRegionModal').classList.add('hidden');
    }
});

document.querySelector('.add-region').addEventListener('click', () => {
    resetForm();
    document.getElementById('addRegionModal').classList.remove('hidden');
});

// Search
document.getElementById('regionSearch').addEventListener('input', (e) => {
    const search = e.target.value;
    if (search.length > 0 || search === '') {
        loadRegions(1, search);
    }
});

// Load regions
function loadRegions(page = 1, search = '') {
    const url = new URL('/api/regions', window.location.origin);
    url.searchParams.set('page', page);
    url.searchParams.set('page_size', 25);
    if (search) url.searchParams.set('search', search);

    fetch(url)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                renderTable(result.data);
                renderPagination(result.pagination);
            } else {
                showMessage(result.error || 'Error loading regions', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error loading regions', 'error');
        });
}

function renderTable(regions) {
    const tbody = document.getElementById('regionsTableBody');
    
    if (regions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No regions found</td></tr>';
        return;
    }

    tbody.innerHTML = regions.map(region => `
        <tr class="border-b border-gray-200 hover:bg-blue-100">
            <td class="px-6 py-3 text-gray-800 font-semibold">${region.code}</td>
            <td class="px-6 py-3 text-gray-800">${region.name}</td>
            <td class="px-6 py-3">
                <span class="inline-block bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">${region.districts_count}</span>
            </td>
            <td class="px-6 py-3">
                <span class="inline-block bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">${region.schools_count}</span>
            </td>
            <td class="px-6 py-3">
                <span class="inline-block px-3 py-1 rounded text-xs font-semibold ${
                    region.status === 'active' 
                        ? 'bg-green-100 text-green-700' 
                        : 'bg-red-100 text-red-700'
                }">
                    ${region.status.charAt(0).toUpperCase() + region.status.slice(1)}
                </span>
            </td>
            <td class="px-6 py-3 flex gap-2">
                <button onclick="viewRegion(${region.id})" class="text-green-600 hover:text-green-800 font-semibold" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button onclick="editRegion(${region.id}, '${region.code}', '${region.name}')" class="text-blue-600 hover:text-blue-800 font-semibold" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteRegion(${region.id})" class="text-red-600 hover:text-red-800 font-semibold" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('paginationControls');
    let html = '<div class="flex justify-center gap-2">';

    if (pagination.has_previous) {
        html += `<button onclick="loadRegions(${pagination.page - 1})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">← Previous</button>`;
    }

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="px-3 py-1 bg-blue-600 text-white rounded">${i}</button>`;
        } else if (i <= 5 || i > pagination.total_pages - 2) {
            html += `<button onclick="loadRegions(${i})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">${i}</button>`;
        } else if (i === 6) {
            html += `<span class="px-3 py-1">...</span>`;
        }
    }

    if (pagination.has_next) {
        html += `<button onclick="loadRegions(${pagination.page + 1})" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">Next →</button>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

// Add/Update region
document.getElementById('addRegionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const editId = document.getElementById('editRegionId').value;
    const code = document.getElementById('regionCode').value;
    const name = document.getElementById('regionName').value;

    const url = editId ? `/api/regions/${editId}/update` : '/api/regions/add';
    const method = editId ? 'POST' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ code, name }),
        });

        const result = await response.json();
        
        if (result.success) {
            loadRegions();
            document.getElementById('addRegionModal').classList.add('hidden');
            showMessage(result.message, 'success');
        } else {
            showMessage(result.error || 'Error saving region', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Error saving region', 'error');
    }
});

// Store current viewing region ID
let currentViewingRegionId = null;

// View region
function viewRegion(id) {
    fetch(`/api/regions?page=1&page_size=100`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                const region = result.data.find(r => r.id == id);
                if (region) {
                    currentViewingRegionId = region.id;
                    document.getElementById('viewRegionCode').textContent = region.code;
                    document.getElementById('viewRegionName').textContent = region.name;
                    document.getElementById('viewDistrictsCount').textContent = region.districts_count;
                    document.getElementById('viewSchoolsCount').textContent = region.schools_count;
                    document.getElementById('viewStatus').textContent = region.status.charAt(0).toUpperCase() + region.status.slice(1);
                    document.getElementById('viewRegionModal').classList.remove('hidden');
                } else {
                    showMessage('Region not found', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error loading region details', 'error');
        });
}

// Switch from view to edit modal
function switchToEdit() {
    const code = document.getElementById('viewRegionCode').textContent;
    const name = document.getElementById('viewRegionName').textContent;
    const id = currentViewingRegionId;
    
    document.getElementById('viewRegionModal').classList.add('hidden');
    editRegion(id, code, name);
}

// Edit region
function editRegion(id, code, name) {
    document.getElementById('modalTitle').textContent = 'Edit Region';
    document.getElementById('submitBtn').textContent = 'Update Region';
    document.getElementById('editRegionId').value = id;
    document.getElementById('regionCode').value = code;
    document.getElementById('regionName').value = name;
    document.getElementById('addRegionModal').classList.remove('hidden');
}

// Delete region
function deleteRegion(id) {
    if (!confirm('Are you sure you want to delete this region?')) return;

    fetch(`/api/regions/${id}/delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            loadRegions();
            showMessage(result.message, 'success');
        } else {
            let msg = result.error;
            if (result.details) {
                msg += ` [Districts: ${result.details.districts}, Schools: ${result.details.schools}]`;
            }
            showMessage(msg, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error deleting region', 'error');
    });
}

// Export to PDF (Server-side using DomPDF)
document.querySelector('.export-pdf-region').addEventListener('click', () => {
    const rows = document.getElementById('regionsTableBody').rows;
    if (!rows || rows.length === 0) {
        showMessage('No regions data to export', 'error');
        return;
    }

    showMessage('Generating PDF...', 'info');

    fetch('/api/regions/export-pdf')
        .then(response => {
            if (!response.ok) throw new Error('PDF export failed');
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `regions_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showMessage('Regions exported to PDF successfully', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error exporting PDF. Please try again.', 'error');
        });
});

// Export to Excel (XLSX format)
document.querySelector('.export-excel-region').addEventListener('click', () => {
    const rowsData = document.getElementById('regionsTableBody').rows;
    if (rowsData.length === 0) {
        showMessage('No regions data to export', 'error');
        return;
    }

    showMessage('Generating Excel file...', 'info');

    fetch('/api/regions/export-excel')
        .then(res => {
            if (!res.ok) throw new Error('Export failed');
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `regions_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showMessage('Regions exported to Excel successfully', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error exporting to Excel. Please try again.', 'error');
        });
});

// Import regions
document.querySelector('.import-csv-region').addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv,.xlsx,.xls';
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file size (max 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showMessage('File size exceeds 5MB limit', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        showMessage(`Importing regions from ${file.name}...`, 'info');

        fetch('/api/regions/import', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        })
        .then(res => {
            if (!res.ok) throw new Error('Import failed');
            return res.json();
        })
        .then(result => {
            if (result.success) {
                loadRegions();
                let message = result.message;
                if (result.errors && result.errors.length > 0) {
                    message += ` (${result.errors.length} errors)`;
                    console.warn('Import errors:', result.errors);
                }
                showMessage(message, 'success');
            } else {
                showMessage(result.error || 'Error importing regions', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error importing regions. Please check the file format.', 'error');
        });
    };
    input.click();
});

// Download template
document.querySelector('.download-template-region').addEventListener('click', () => {
    const headers = ['Code', 'Region Name'];
    const sampleData = [
        ['01', 'Arusha'],
        ['02', 'Dar es Salaam'],
        ['03', 'Dodoma'],
        ['04', 'Iringa'],
        ['05', 'Kagera'],
    ];

    let csv = headers.join(',') + '\n';
    sampleData.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
    });

    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
    element.setAttribute('download', `regions_template_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);

    showMessage('Template downloaded successfully', 'success');
});

// Reset form
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Add New Region';
    document.getElementById('submitBtn').textContent = 'Add Region';
    document.getElementById('editRegionId').value = '';
    document.getElementById('addRegionForm').reset();
}

// Show message
function showMessage(message, type = 'info') {
    const alertDiv = document.createElement('div');
    
    let bgColor, textColor, borderColor;
    if (type === 'success') {
        bgColor = '#d1fae5';
        textColor = '#065f46';
        borderColor = '#a7f3d0';
    } else if (type === 'error') {
        bgColor = '#fee2e2';
        textColor = '#991b1b';
        borderColor = '#fecaca';
    } else {
        bgColor = '#dbeafe';
        textColor = '#0c2340';
        borderColor = '#bfdbfe';
    }

    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${bgColor};
        color: ${textColor};
        border: 1px solid ${borderColor};
        border-radius: 6px;
        z-index: 3000;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-width: 400px;
    `;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}

// Toggle dropdown menu
function toggleMenu(menuId) {
    const menu = document.getElementById(menuId);
    menu.classList.toggle('hidden');
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            menu.classList.add('hidden');
        }
    });
}

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const tab = e.currentTarget.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'border-blue-600', 'text-blue-600');
            b.classList.add('border-transparent', 'text-gray-500');
        });
        e.currentTarget.classList.add('active', 'border-blue-600', 'text-blue-600');
        e.currentTarget.classList.remove('border-transparent', 'text-gray-500');
        
        // Show only relevant section (future: implement for districts/schools)
        console.log('Tab switched to:', tab);
    });
});

// Load on page load
document.addEventListener('DOMContentLoaded', () => {
    loadRegions();
});
</script>
@endsection
