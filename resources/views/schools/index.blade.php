@extends('layout')

@section('content')
<div class="mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Schools</h2>
        <button onclick="openAddSchoolModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus"></i> Add School
        </button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="text-left p-4">Code</th>
                    <th class="text-left p-4">Name</th>
                    <th class="text-left p-4">Region</th>
                    <th class="text-left p-4">Type</th>
                    <th class="text-left p-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schools as $school)
                    <tr class="border-b">
                        <td class="p-4">{{ $school->code }}</td>
                        <td class="p-4">{{ $school->name }}</td>
                        <td class="p-4">{{ $school->region->name }}</td>
                        <td class="p-4">{{ $school->school_type }}</td>
                        <td class="p-4 flex gap-3">
                            <button onclick="openViewSchoolModal({{ $school->id }})" title="View" class="text-blue-600 hover:text-blue-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openEditSchoolModal({{ $school->id }})" title="Edit" class="text-yellow-600 hover:text-yellow-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openDeleteSchoolModal({{ $school->id }})" title="Delete" class="text-red-600 hover:text-red-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $schools->links() }}
    </div>
</div>

<script>
function openAddSchoolModal() {
    const regionsHTML = `
        @foreach ($schools->first()?->whereBelongsTo(\App\Models\Region::first())->with('region')->get() ?? [] as $school)
        @endforeach
        <select id="region_id" name="region_id" class="w-full border p-2 rounded" required>
            <option value="">Select Region</option>
            @foreach (\App\Models\Region::all() as $region)
                <option value="{{ $region->id }}">{{ $region->name }}</option>
            @endforeach
        </select>
    `;
    
    const formHTML = `
        <form method="POST" action="/schools" onsubmit="handleSchoolSubmit(event)">
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
                <label class="block text-gray-700 mb-2">Region</label>
                <select name="region_id" class="w-full border p-2 rounded" required>
                    <option value="">Select Region</option>
                    @foreach (\App\Models\Region::all() as $region)
                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="school_type" class="w-full border p-2 rounded" required>
                    <option value="PRIMARY">Primary</option>
                    <option value="SECONDARY">Secondary</option>
                    <option value="BOTH">Both</option>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Create</button>
        </form>
    `;
    openModal('Add School', formHTML);
}

function handleSchoolSubmit(event) {
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

// View School
async function openViewSchoolModal(id) {
    try {
        const response = await fetch(`/schools/${id}`, {
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
                    <label class="block text-gray-600 text-sm">Type</label>
                    <p class="text-lg">${data.school_type}</p>
                </div>
                <button onclick="closeModal()" class="w-full bg-gray-500 text-white p-2 rounded mt-4">Close</button>
            </div>
        `;
        openModal(`View School: ${data.name}`, content);
    } catch(e) {
        alert('Error loading school');
    }
}

// Edit School
async function openEditSchoolModal(id) {
    try {
        const response = await fetch(`/schools/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        const formHTML = `
            <form method="POST" action="/schools/${id}" onsubmit="handleEditSchoolSubmit(event)">
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
                    <label class="block text-gray-700 mb-2">Region</label>
                    <select name="region_id" class="w-full border p-2 rounded" required>
                        @foreach (\App\Models\Region::all() as $region)
                            <option value="{{ $region->id }}" ${data.region_id == '{{ $region->id }}' ? 'selected' : ''}>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Type</label>
                    <select name="school_type" class="w-full border p-2 rounded" required>
                        <option value="PRIMARY" ${data.school_type == 'PRIMARY' ? 'selected' : ''}>Primary</option>
                        <option value="SECONDARY" ${data.school_type == 'SECONDARY' ? 'selected' : ''}>Secondary</option>
                        <option value="BOTH" ${data.school_type == 'BOTH' ? 'selected' : ''}>Both</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Update</button>
            </form>
        `;
        openModal('Edit School', formHTML);
    } catch(e) {
        alert('Error loading school');
    }
}

// Delete School
function openDeleteSchoolModal(id) {
    const content = `
        <p class="mb-4 text-gray-700">Are you sure you want to delete this school?</p>
        <div class="flex gap-2">
            <button onclick="deleteSchool(${id})" class="flex-1 bg-red-600 text-white p-2 rounded hover:bg-red-700">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button onclick="closeModal()" class="flex-1 bg-gray-400 text-white p-2 rounded hover:bg-gray-500">
                Cancel
            </button>
        </div>
    `;
    openModal('Delete School', content);
}

function deleteSchool(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/schools/${id}`, {
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
    }).catch(error => alert('Error deleting school'));
}

function handleEditSchoolSubmit(event) {
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
            region_id: formData.get('region_id'),
            school_type: formData.get('school_type')
        })
    }).then(response => {
        if (response.ok) {
            closeModal();
            location.reload();
        } else {
            alert('Error updating school');
        }
    }).catch(error => alert('Error: ' + error));
}
</script>
@endsection
