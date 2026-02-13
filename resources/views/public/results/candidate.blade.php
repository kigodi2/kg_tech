@extends('layout')

@section('content')
<div style="background-color: #f5f5f5; min-height: 100vh; padding: 2rem;">
    <div class="container mx-auto max-w-4xl">
        
        <!-- Back Button -->
        <div style="margin-bottom: 1.5rem;">
            <a href="/results/{{ $examYear }}/{{ strtolower($examType) }}" style="color: #003366; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Results Search
            </a>
        </div>

        <!-- Header Section -->
        <div style="background-color: #003366; color: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
            <h1 style="margin: 0 0 1rem 0; font-size: 1.8rem; font-weight: bold;">EXAMINATION RESULTS DETAIL</h1>
            <p style="margin: 0; font-size: 1.1rem;">{{ strtoupper($examType) }} - {{ $examYear }}</p>
        </div>

        <!-- Candidate Information -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 style="color: #003366; margin-top: 0; margin-bottom: 1rem; border-bottom: 2px solid #003366; padding-bottom: 0.5rem;">Candidate Information</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <p style="margin: 0.5rem 0; color: #666;">
                        <strong>Index Number:</strong><br>
                        <span style="font-size: 1.1rem; color: #003366; font-weight: 600;">{{ $candidate->index_number }}</span>
                    </p>
                </div>
                <div>
                    <p style="margin: 0.5rem 0; color: #666;">
                        <strong>Full Name:</strong><br>
                        <span style="font-size: 1.1rem;">{{ $candidate->full_name }}</span>
                    </p>
                </div>
                <div>
                    <p style="margin: 0.5rem 0; color: #666;">
                        <strong>School:</strong><br>
                        <span style="font-size: 1rem;">{{ $candidate->school?->name ?? 'Unknown' }}</span>
                    </p>
                </div>
                <div>
                    <p style="margin: 0.5rem 0; color: #666;">
                        <strong>Gender:</strong><br>
                        <span style="font-size: 1rem;">{{ $candidate->gender === 'F' ? 'Female' : 'Male' }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 style="color: #003366; margin-top: 0; margin-bottom: 1rem; border-bottom: 2px solid #003366; padding-bottom: 0.5rem;">Results Summary</h2>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                <div style="background: #f0f0f0; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #003366;">
                    <p style="margin: 0; color: #666; font-size: 0.9rem; text-transform: uppercase;">Total Marks</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 1.8rem; color: #003366; font-weight: bold;">{{ $totalMarks }}</p>
                </div>
                <div style="background: #f0f0f0; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #003366;">
                    <p style="margin: 0; color: #666; font-size: 0.9rem; text-transform: uppercase;">GPA</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 1.8rem; color: white; font-weight: bold; background-color: {{ $gpaInfo['color'] }}; padding: 0.5rem; border-radius: 4px;">{{ $gpa }}</p>
                </div>
                <div style="background: #f0f0f0; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #003366;">
                    <p style="margin: 0; color: #666; font-size: 0.9rem; text-transform: uppercase;">Grade</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 1.5rem; color: #003366; font-weight: bold;">{{ $gpaInfo['grade'] ?? 'N/A' }}</p>
                </div>
                <div style="background: #f0f0f0; padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #003366;">
                    <p style="margin: 0; color: #666; font-size: 0.9rem; text-transform: uppercase;">Competence</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem; color: #003366; font-weight: bold;">{{ str_replace('Grade ', '', $gpaInfo['competence'] ?? 'N/A') }}</p>
                </div>
            </div>
        </div>

        <!-- Subject Results -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="background-color: #003366; color: white; padding: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.2rem;">Subject Results</h2>
            </div>
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="padding: 1rem; text-align: left; border: 1px solid #ddd; font-weight: 600;">Subject</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd; font-weight: 600; width: 15%;">Marks</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid #ddd; font-weight: 600; width: 15%;">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjectGrades as $index => $subject)
                        @php
                            $rowBg = $index % 2 === 0 ? 'white' : '#f9f9f9';
                            $gradeColor = match($subject['grade']) {
                                'A' => '#00A82A',
                                'B' => '#1FEE0B',
                                'C' => '#1FEE0B',
                                'D' => '#DEF043',
                                'E' => '#DEF043',
                                'S' => '#FF772F',
                                'F' => '#FF272F',
                                default => '#999'
                            };
                        @endphp
                        <tr style="background-color: {{ $rowBg }};">
                            <td style="padding: 1rem; border: 1px solid #ddd;">{{ $subject['subject'] }}</td>
                            <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; font-weight: 600;">{{ number_format($subject['marks'], 2) }}</td>
                            <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; background-color: {{ $gradeColor }}; color: white; font-weight: bold;">{{ $subject['grade'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding: 2rem; text-align: center; color: #999;">No subject results available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Info -->
        <div style="margin-top: 2rem; padding: 1.5rem; background: #e8f4f8; border-radius: 8px; border-left: 4px solid #003366; color: #003366;">
            <strong>Important Notice:</strong> This is an official examination result as per NECTA guidelines. 
            Results are subject to verification. Any discrepancies should be reported immediately to your examination center.
        </div>

        <!-- Print Button -->
        <div style="margin-top: 2rem; text-align: center;">
            <button onclick="window.print()" style="background-color: #003366; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                <i class="fas fa-print"></i> Print Results
            </button>
        </div>
    </div>
</div>
@endsection
