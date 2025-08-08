{{-- filepath: resources/views/exports/members-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Members Export</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .export-info {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .export-info p {
            margin: 3px 0;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            font-size: 8px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding: 5px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Parish Members Export</h1>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="export-info">
        <p><strong>Total Records:</strong> {{ $members->count() }}</p>
        @if(!empty($filters['local_church']))
            <p><strong>Church:</strong> {{ $filters['local_church'] }}</p>
        @endif
        @if(!empty($filters['church_group']))
            <p><strong>Group:</strong> {{ $filters['church_group'] }}</p>
        @endif
        @if(!empty($filters['membership_status']))
            <p><strong>Status:</strong> {{ $filters['membership_status'] }}</p>
        @endif
        @if(!empty($filters['gender']))
            <p><strong>Gender:</strong> {{ $filters['gender'] }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 20%;">Name</th>
                <th style="width: 8%;">Age</th>
                <th style="width: 8%;">Gender</th>
                <th style="width: 12%;">Phone</th>
                <th style="width: 15%;">Church</th>
                <th style="width: 12%;">Group</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 8%;">Family</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $index => $member)
                <tr>
                    <td>{{ $member->id }}</td>
                    <td>{{ $member->full_name }}</td>
                    <td>{{ $member->age ?? 'N/A' }}</td>
                    <td>{{ $member->gender }}</td>
                    <td>{{ $member->phone }}</td>
                    <td>{{ $member->local_church }}</td>
                    <td>{{ $member->church_group }}</td>
                    <td>{{ $member->membership_status }}</td>
                    <td>{{ $member->family_name }}</td>
                </tr>
                @if(($index + 1) % 30 == 0 && !$loop->last)
                    </tbody>
                    </table>
                    <div class="page-break"></div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 20%;">Name</th>
                                <th style="width: 8%;">Age</th>
                                <th style="width: 8%;">Gender</th>
                                <th style="width: 12%;">Phone</th>
                                <th style="width: 15%;">Church</th>
                                <th style="width: 12%;">Group</th>
                                <th style="width: 12%;">Status</th>
                                <th style="width: 8%;">Family</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Parish Management System - Members Export Report
    </div>
</body>
</html>