@extends('layout')

@section('content')
<div class="mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Regions</h2>
        <button onclick="openAddRegionModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus"></i> Add Region
        </button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="text-left p-4">Code</th>
                    <th class="text-left p-4">Name</th>
                    <th class="text-center p-4">Districts</th>
                    <th class="text-center p-4">Schools</th>
                    <th class="text-center p-4">Candidates</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-left p-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regions as $region)
                    @php
                        $districtsCount = \App\Models\District::where('region_id', $region->id)->count();
                        $schoolsCount = \App\Models\School::where('region_id', $region->id)->count();
                        $candidatesCount = \App\Models\Candidate::whereIn('school_id', 
                            \App\Models\School::where('region_id', $region->id)->pluck('id')
                        )->count();
                    @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4 font-mono">{{ $region->code }}</td>
                        <td class="p-4">{{ $region->name }}</td>
                        <td class="p-4 text-center">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-semibold">{{ $districtsCount }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-semibold">{{ $schoolsCount }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-sm font-semibold">{{ $candidatesCount }}</span>
                        </td>
                        <td class="p-4">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                {{ $region->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-4 flex gap-3">
                            <button onclick="openViewRegionModal({{ $region->id }})" title="View" class="text-blue-600 hover:text-blue-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openEditRegionModal({{ $region->id }})" title="Edit" class="text-yellow-600 hover:text-yellow-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openDeleteRegionModal({{ $region->id }})" title="Delete" class="text-red-600 hover:text-red-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $regions->links() }}
    </div>
</div>

<script>
function openAddRegionModal() {
    const formHTML = `
        <form method="POST" action="/regions" onsubmit="handleFormSubmit(event)">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Code</label>
                <input type="text" name="code" class="w-full border p-2 rounded" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" class="w-full border p-2 rounded" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border p-2 rounded"></textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Create</button>
        </form>
    `;
    openModal('Add Region', formHTML);
}

function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    }).then(response => {
        if (response.ok) {
            closeModal();
            location.reload();
        } else {
            return response.json().then(data => {
                alert('Error: ' + JSON.stringify(data.errors || data.message));
            });
        }
    }).catch(error => console.error('Error:', error));
}

// View Region
async function openViewRegionModal(id) {
    try {
        const response = await fetch(`/regions/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        const content = `
            <div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Code</label>
                    <p class="text-lg">${data.code}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Name</label>
                    <p class="text-lg">${data.name}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Description</label>
                    <p class="text-lg">${data.description || 'N/A'}</p>
                </div>
                <button onclick="closeModal()" class="w-full bg-gray-500 text-white p-2 rounded mt-4">Close</button>
            </div>
        `;
        openModal(`View Region: ${data.name}`, content);
    } catch(e) {
        alert('Error loading region');
    }
}

// Edit Region
async function openEditRegionModal(id) {
    try {
        const response = await fetch(`/regions/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        const formHTML = `
            <form method="POST" action="/regions/${id}" onsubmit="handleEditSubmit(event)">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Code</label>
                    <input type="text" name="code" value="${data.code}" class="w-full border p-2 rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" value="${data.name}" class="w-full border p-2 rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" class="w-full border p-2 rounded">${data.description || ''}</textarea>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Update</button>
            </form>
        `;
        openModal('Edit Region', formHTML);
    } catch(e) {
        alert('Error loading region');
    }
}

// Delete Region
function openDeleteRegionModal(id) {
    const content = `
        <p class="mb-4 text-gray-700">Are you sure you want to delete this region?</p>
        <div class="flex gap-2">
            <button onclick="deleteRegion(${id})" class="flex-1 bg-red-600 text-white p-2 rounded hover:bg-red-700">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button onclick="closeModal()" class="flex-1 bg-gray-400 text-white p-2 rounded hover:bg-gray-500">
                Cancel
            </button>
        </div>
    `;
    openModal('Delete Region', content);
}

function deleteRegion(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/regions/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json'
        }
    }).then(response => {
        if (response.ok) {
            closeModal();
            location.reload();
        }
    }).catch(error => alert('Error deleting region'));
}

function handleEditSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    fetch(form.action, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            code: formData.get('code'),
            name: formData.get('name'),
            description: formData.get('description')
        })
    }).then(response => {
        if (response.ok) {
            closeModal();
            location.reload();
        } else {
            alert('Error updating region');
        }
    }).catch(error => alert('Error: ' + error));
}
</script>
@endsection
