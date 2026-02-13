@extends('layout')

@section('content')
<div class="mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Candidates</h2>
        <button onclick="openAddCandidateModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus"></i> Add Candidate
        </button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="text-left p-4">ID</th>
                    <th class="text-left p-4">Name</th>
                    <th class="text-left p-4">School</th>
                    <th class="text-left p-4">Gender</th>
                    <th class="text-left p-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($candidates as $candidate)
                    <tr class="border-b">
                        <td class="p-4">{{ $candidate->candidate_id }}</td>
                        <td class="p-4">{{ $candidate->full_name }}</td>
                        <td class="p-4">{{ $candidate->school->name }}</td>
                        <td class="p-4">{{ $candidate->gender === 'M' ? 'Male' : 'Female' }}</td>
                        <td class="p-4 flex gap-3">
                            <button onclick="openViewCandidateModal({{ $candidate->id }})" title="View" class="text-blue-600 hover:text-blue-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openEditCandidateModal({{ $candidate->id }})" title="Edit" class="text-yellow-600 hover:text-yellow-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openDeleteCandidateModal({{ $candidate->id }})" title="Delete" class="text-red-600 hover:text-red-800 bg-none border-none cursor-pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $candidates->links() }}
    </div>
</div>

<script>
function openAddCandidateModal() {
    const formHTML = `
        <form method="POST" action="/candidates" onsubmit="handleCandidateSubmit(event)">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">School</label>
                <select name="school_id" class="w-full border p-2 rounded" required>
                    <option value="">Select School</option>
                    @foreach (\App\Models\School::all() as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Candidate ID</label>
                <input type="text" name="candidate_id" class="w-full border p-2 rounded" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">First Name</label>
                <input type="text" name="first_name" class="w-full border p-2 rounded" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Last Name</label>
                <input type="text" name="last_name" class="w-full border p-2 rounded" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Gender</label>
                <select name="gender" class="w-full border p-2 rounded" required>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Date of Birth</label>
                <input type="date" name="date_of_birth" class="w-full border p-2 rounded">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Create</button>
        </form>
    `;
    openModal('Add Candidate', formHTML);
}

function handleCandidateSubmit(event) {
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

// View Candidate
async function openViewCandidateModal(id) {
    try {
        const response = await fetch(`/candidates/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        const content = `
            <div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Candidate ID</label>
                    <p class="text-lg">${data.candidate_id}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Name</label>
                    <p class="text-lg">${data.first_name} ${data.last_name}</p>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm">Gender</label>
                    <p class="text-lg">${data.gender === 'M' ? 'Male' : 'Female'}</p>
                </div>
                <button onclick="closeModal()" class="w-full bg-gray-500 text-white p-2 rounded mt-4">Close</button>
            </div>
        `;
        openModal(`View Candidate: ${data.first_name} ${data.last_name}`, content);
    } catch(e) {
        alert('Error loading candidate');
    }
}

// Edit Candidate
async function openEditCandidateModal(id) {
    try {
        const response = await fetch(`/candidates/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        const formHTML = `
            <form method="POST" action="/candidates/${id}" onsubmit="handleEditCandidateSubmit(event)">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Candidate ID</label>
                    <input type="text" name="candidate_id" value="${data.candidate_id}" class="w-full border p-2 rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">First Name</label>
                    <input type="text" name="first_name" value="${data.first_name}" class="w-full border p-2 rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Last Name</label>
                    <input type="text" name="last_name" value="${data.last_name}" class="w-full border p-2 rounded" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Gender</label>
                    <select name="gender" class="w-full border p-2 rounded" required>
                        <option value="M" ${data.gender == 'M' ? 'selected' : ''}>Male</option>
                        <option value="F" ${data.gender == 'F' ? 'selected' : ''}>Female</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Update</button>
            </form>
        `;
        openModal('Edit Candidate', formHTML);
    } catch(e) {
        alert('Error loading candidate');
    }
}

// Delete Candidate
function openDeleteCandidateModal(id) {
    const content = `
        <p class="mb-4 text-gray-700">Are you sure you want to delete this candidate?</p>
        <div class="flex gap-2">
            <button onclick="deleteCandidate(${id})" class="flex-1 bg-red-600 text-white p-2 rounded hover:bg-red-700">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button onclick="closeModal()" class="flex-1 bg-gray-400 text-white p-2 rounded hover:bg-gray-500">
                Cancel
            </button>
        </div>
    `;
    openModal('Delete Candidate', content);
}

function deleteCandidate(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/candidates/${id}`, {
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
    }).catch(error => alert('Error deleting candidate'));
}

function handleEditCandidateSubmit(event) {
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
            candidate_id: formData.get('candidate_id'),
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            gender: formData.get('gender')
        })
    }).then(response => {
        if (response.ok) {
            closeModal();
            location.reload();
        } else {
            alert('Error updating candidate');
        }
    }).catch(error => alert('Error: ' + error));
}
</script>
@endsection
