<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACSEE {{ $yearNumeric }} EXAMINATION RESULTS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .header .title {
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .centre-heading {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .back-link {
            margin-bottom: 20px;
        }
        
        .back-link a {
            color: #003366;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 25px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border-left: 3px solid #003366;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        th, td {
            border: 1px solid #999;
            padding: 8px 10px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .summary-table th {
            background-color: #e8e8e8;
            text-align: center;
        }
        
        .summary-table td {
            text-align: center;
            padding: 10px 5px;
        }
        
        .summary-table td:first-child {
            text-align: left;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .results-table td {
            text-align: left;
            padding: 10px;
        }
        
        .results-table td:nth-child(2),
        .results-table td:nth-child(3),
        .results-table td:nth-child(4) {
            text-align: center;
        }
        
        .results-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .results-table tr:hover {
            background-color: #f0f0f0;
        }
        
        .subject-grades {
            font-size: 12px;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Link -->
        <div class="back-link">
            <a href="{{ route('results.public.acsee.index', ['year' => $examYear]) }}">← Back to Centres List</a>
        </div>

        <!-- Header -->
        <div class="header">
            <p class="title">ACSEE {{ $yearNumeric }} EXAMINATION RESULTS</p>
        </div>

        <!-- Centre Heading -->
        <div class="centre-heading">
            {{ $school->code }} {{ strtoupper($school->name) }}
        </div>

        <!-- Division Performance Summary -->
        <div class="section-title">DIVISION PERFORMANCE SUMMARY</div>
        
        <table class="summary-table">
            <thead>
                <tr>
                    <th style="text-align: left;">SEX</th>
                    <th>DIV I</th>
                    <th>DIV II</th>
                    <th>DIV III</th>
                    <th>DIV IV</th>
                    <th>0</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['F' => 'Female', 'M' => 'Male'] as $sex => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $divisionStats[$sex]['I'] ?? 0 }}</td>
                        <td>{{ $divisionStats[$sex]['II'] ?? 0 }}</td>
                        <td>{{ $divisionStats[$sex]['III'] ?? 0 }}</td>
                        <td>{{ $divisionStats[$sex]['IV'] ?? 0 }}</td>
                        <td>{{ $divisionStats[$sex]['0'] ?? 0 }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Total</td>
                    <td>
                        {{ ($divisionStats['F']['I'] ?? 0) + ($divisionStats['M']['I'] ?? 0) }}
                    </td>
                    <td>
                        {{ ($divisionStats['F']['II'] ?? 0) + ($divisionStats['M']['II'] ?? 0) }}
                    </td>
                    <td>
                        {{ ($divisionStats['F']['III'] ?? 0) + ($divisionStats['M']['III'] ?? 0) }}
                    </td>
                    <td>
                        {{ ($divisionStats['F']['IV'] ?? 0) + ($divisionStats['M']['IV'] ?? 0) }}
                    </td>
                    <td>
                        {{ ($divisionStats['F']['0'] ?? 0) + ($divisionStats['M']['0'] ?? 0) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Detailed Results -->
        <div class="section-title">CANDIDATES' DETAILED RESULTS</div>
        
        <table class="results-table">
            <thead>
                <tr>
                    <th>CNO</th>
                    <th>SEX</th>
                    <th>AGGT</th>
                    <th>DIV</th>
                    <th>DETAILED SUBJECTS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidatesData as $data)
                    <tr>
                        <td>{{ $data['candidate_number'] }}</td>
                        <td>{{ $data['sex'] }}</td>
                        <td>{{ number_format($data['aggregate'], 2) }}</td>
                        <td>{{ $data['division'] }}</td>
                        <td class="subject-grades">
                            @foreach ($data['subject_grades'] as $subject)
                                {{ strtoupper(substr($subject['name'], 0, 6)) }} - '{{ $subject['grade'] }}'
                                @if (!$loop->last)
                                    &nbsp;
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                            No candidates found for this centre.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>National Examinations Council of Tanzania</p>
            <p>ACSEE {{ $yearNumeric }} Examination Results</p>
        </div>
    </div>
</body>
</html>
