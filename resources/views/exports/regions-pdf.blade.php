<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regions Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 30px;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #1b5e3f;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #1b5e3f;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .header p {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }

        .metadata {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
            color: #555;
        }

        .metadata-item {
            margin-right: 20px;
        }

        .metadata-label {
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background-color: #1b5e3f;
            color: white;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #1b5e3f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 10px 12px;
            font-size: 11px;
            border: 1px solid #ddd;
            color: #333;
        }

        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        tbody tr:hover {
            background-color: #f0f7f5;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 10px;
            color: #999;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f7f5;
            border-left: 4px solid #1b5e3f;
            font-size: 11px;
        }

        .summary-item {
            margin: 5px 0;
        }

        .summary-label {
            font-weight: bold;
            color: #1b5e3f;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            min-width: 50px;
        }

        .badge-blue {
            background-color: #e7f3ff;
            color: #0056b3;
            border: 1px solid #0056b3;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Regions Report</h1>
            <p>Complete list of all regions in the system</p>
        </div>

        <!-- Metadata -->
        <div class="metadata">
            <div class="metadata-item">
                <span class="metadata-label">Generated:</span>
                {{ $generatedAt->format('F d, Y h:i A') }}
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Total Records:</span>
                {{ $totalRecords }}
            </div>
            <div class="metadata-item">
                <span class="metadata-label">System:</span>
                IRMS
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th width="10%">Code</th>
                    <th width="30%">Region Name</th>
                    <th width="15%" class="text-center">Districts</th>
                    <th width="15%" class="text-center">Schools</th>
                    <th width="15%" class="text-center">Status</th>
                    <th width="15%" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($regions as $region)
                    <tr>
                        <td>
                            <strong>{{ $region['code'] }}</strong>
                        </td>
                        <td>
                            {{ $region['name'] }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-blue">{{ $region['districts_count'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-blue">{{ $region['schools_count'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="@if($region['status'] === 'Active') status-active @else status-inactive @endif">
                                {{ $region['status'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            {{ $loop->iteration }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No regions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-item">
                <span class="summary-label">Summary Statistics:</span>
            </div>
            <div class="summary-item">
                • Total Regions: <strong>{{ $totalRecords }}</strong>
            </div>
            <div class="summary-item">
                • Total Districts: <strong>{{ $regions->sum('districts_count') }}</strong>
            </div>
            <div class="summary-item">
                • Total Schools: <strong>{{ $regions->sum('schools_count') }}</strong>
            </div>
            <div class="summary-item">
                • Active Regions: <strong>{{ $regions->where('status', 'Active')->count() }}</strong>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-info">
                <span>IRMS - Intelligent Result Management System</span>
                <span>Page 1 of 1</span>
            </div>
            <div style="text-align: center; margin-top: 10px;">
                <p>This document is confidential and intended for authorized use only.</p>
                <p>© 2024 All Rights Reserved</p>
            </div>
        </div>
    </div>
</body>
</html>
