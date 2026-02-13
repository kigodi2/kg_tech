@extends('layout')

@section('content')
<div style="background-color: #f5f5f5; min-height: 100vh; padding: 2rem;">
    <div class="container mx-auto max-w-6xl">
        
        <!-- Header Section -->
        <div style="background-color: #003366; color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
            <h1 style="margin: 0; font-size: 2rem; font-weight: bold;">ZONAL EXAMINATION RESULTS</h1>
            <p style="margin: 0.5rem 0 0 0; font-size: 1.1rem;">{{ strtoupper($examType) }} - {{ $examYear }}</p>
        </div>

        <!-- Search Section -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 style="color: #003366; margin-top: 0; font-size: 1.3rem;">Search Results</h2>
            
            <div id="searchForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Candidate Index Number</label>
                    <input type="text" id="indexNumber" placeholder="e.g., S1378-0523" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">School Name/Code</label>
                    <input type="text" id="schoolName" placeholder="e.g., TOSAMACANGA SECONDARY SCHOOL" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button onclick="searchResults()" style="background-color: #003366; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-search"></i> Search
                </button>
                <button onclick="resetSearch()" style="background-color: #6c757d; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <!-- Results Table -->
        <div id="resultsContainer" style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="padding: 2rem; text-align: center; color: #666;">
                <p style="font-size: 1.1rem; margin: 0;">Enter search criteria above to view results</p>
            </div>
        </div>

        <!-- Footer Info -->
        <div style="margin-top: 2rem; padding: 1.5rem; background: #e8f4f8; border-radius: 8px; border-left: 4px solid #003366; color: #003366;">
            <strong>Note:</strong> This is the official examination results portal for {{ strtoupper($examType) }} {{ $examYear }}. 
            Results are published as per NECTA guidelines. For further inquiries, contact your examination center or visit the NECTA office.
        </div>
    </div>
</div>

<script>
    async function searchResults() {
        const indexNumber = document.getElementById('indexNumber').value.trim().toUpperCase();
        const schoolName = document.getElementById('schoolName').value.trim().toUpperCase();
        
        if (!indexNumber && !schoolName) {
            alert('Please enter at least Index Number or School Name');
            return;
        }
        
        const resultsContainer = document.getElementById('resultsContainer');
        resultsContainer.innerHTML = '<div style="padding: 2rem; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading results...</div>';
        
        try {
            const response = await fetch('/api/public-results', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    exam_year: '{{ $examYear }}',
                    exam_type: '{{ $examType }}',
                    index_number: indexNumber,
                    school_name: schoolName
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.results && data.results.length > 0) {
                displayResults(data.results);
            } else {
                resultsContainer.innerHTML = `
                    <div style="padding: 2rem; text-align: center; color: #666;">
                        <p style="font-size: 1.1rem; margin: 0; color: #d9534f;">
                            <i class="fas fa-exclamation-circle"></i> No results found matching your criteria
                        </p>
                    </div>
                `;
            }
        } catch (error) {
            resultsContainer.innerHTML = `
                <div style="padding: 2rem; text-align: center; color: #d9534f;">
                    <p style="margin: 0;">Error loading results. Please try again.</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Error: ${error.message}</p>
                </div>
            `;
            console.error('Error:', error);
        }
    }
    
    function displayResults(results) {
        const resultsContainer = document.getElementById('resultsContainer');
        
        // Get school ID from first result for school link
        const schoolId = results.length > 0 ? results[0].school_id : null;
        
        let html = `
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #003366; color: white;">
                        <th style="padding: 1rem; text-align: left; border: 1px solid #ddd;">Index Number</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid #ddd;">Candidate Name</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid #ddd;">School</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd;">Total Marks</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd;">Division</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd;">GPA</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        results.forEach((result, index) => {
            const rowBg = index % 2 === 0 ? '#f9f9f9' : 'white';
            html += `
                <tr style="background-color: ${rowBg};">
                    <td style="padding: 1rem; border: 1px solid #ddd; font-weight: 600;">${result.index_number}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd;">${result.candidate_name}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd;">${result.school_name}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd; text-align: center;">${result.total_marks}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; font-weight: 600;">${result.division}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; background-color: ${result.gpa_color || '#f0f0f0'}; color: white;">${result.gpa}</td>
                    <td style="padding: 1rem; border: 1px solid #ddd; text-align: center;">
                        <button onclick="viewDetails('${result.candidate_id}')" style="background-color: #003366; color: white; padding: 0.5rem 0.75rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; margin-right: 0.25rem;">
                            View
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        // Add school results button if we have results
        if (results.length > 0 && schoolId) {
            html += `
                <div style="padding: 1.5rem; text-align: center; background-color: #f9f9f9; border-top: 1px solid #ddd;">
                    <button onclick="viewSchoolResults(${schoolId})" style="background-color: #00AA7A; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: 600;">
                        <i class="fas fa-school"></i> View All School Results (${results[0].school_name})
                    </button>
                </div>
            `;
        }
        
        resultsContainer.innerHTML = html;
    }
    
    function viewSchoolResults(schoolId) {
        if (!schoolId) {
            alert('School information not available');
            return;
        }
        window.location.href = `/results/{{ $examYear }}/{{ $examType }}/school/${schoolId}`;
    }
    
    function viewDetails(candidateId) {
        // Redirect to detailed results page
        window.location.href = `/results/{{ $examYear }}/{{ $examType }}/candidate/${candidateId}`;
    }
    
    function resetSearch() {
        document.getElementById('indexNumber').value = '';
        document.getElementById('schoolName').value = '';
        document.getElementById('resultsContainer').innerHTML = `
            <div style="padding: 2rem; text-align: center; color: #666;">
                <p style="font-size: 1.1rem; margin: 0;">Enter search criteria above to view results</p>
            </div>
        `;
        document.getElementById('indexNumber').focus();
    }
    
    // Focus on index number input on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('indexNumber').focus();
    });
</script>
@endsection
