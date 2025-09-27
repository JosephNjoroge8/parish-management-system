<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .parish-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .export-info {
            font-size: 8pt;
            color: #666;
        }
        
        .statistics-section {
            margin: 10px 0;
            display: table;
            width: 100%;
            background-color: #f5f5f5;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 3px 8px;
            border-right: 1px solid #ccc;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 10pt;
            color: #2c5530;
        }
        
        .stat-label {
            font-size: 7pt;
            color: #666;
        }
        
        .filters-section {
            margin: 8px 0;
            font-size: 7pt;
            color: #666;
            text-align: center;
        }
        
        .members-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .members-table th {
            background-color: #2c5530;
            color: white;
            padding: 4px;
            text-align: left;
            font-size: 7pt;
            font-weight: bold;
            border: 1px solid #1a3d1f;
        }
        
        .members-table td {
            padding: 3px 4px;
            border: 1px solid #ddd;
            font-size: 7pt;
            vertical-align: top;
        }
        
        .members-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .members-table tr:hover {
            background-color: #e8f4e8;
        }
        
        .member-photo {
            width: 20px;
            height: 20px;
            border-radius: 2px;
            object-fit: cover;
        }
        
        .sacrament-status {
            text-align: center;
        }
        
        .sacrament-yes {
            color: #2c5530;
            font-weight: bold;
        }
        
        .sacrament-no {
            color: #999;
        }
        
        .contact-info {
            font-size: 6pt;
        }
        
        .membership-status {
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-transferred {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-deceased {
            background-color: #f5f5f5;
            color: #6c757d;
        }
        
        .footer {
            position: fixed;
            bottom: 8mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 6pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        
        .page-number:before {
            content: "Page " counter(page) " of " counter(pages);
        }
        
        .additional-info {
            margin-top: 5px;
            padding: 5px;
            background-color: #f0f8ff;
            border-left: 3px solid #2c5530;
            font-size: 7pt;
        }
        
        .section-header {
            background-color: #2c5530;
            color: white;
            padding: 5px;
            margin: 10px 0 5px 0;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        .abbreviated {
            font-size: 6pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="parish-name">{{ $parish_name ?? 'Sacred Heart Kandara Parish' }}</div>
        <div class="report-title">{{ $title }}</div>
        <div class="export-info">
            Generated on: {{ $exportDate }} | 
            Total Members: {{ $statistics['total_members'] ?? $members->count() }}
            @if(!empty($filters))
                | Filters Applied
            @endif
        </div>
    </div>

    @if($statistics)
    <div class="statistics-section">
        <div class="stat-item">
            <div class="stat-value">{{ $statistics['total_members'] }}</div>
            <div class="stat-label">Total Members</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $statistics['baptized_members'] }}</div>
            <div class="stat-label">Baptized</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $statistics['confirmed_members'] }}</div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $statistics['married_members'] }}</div>
            <div class="stat-label">Married</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $statistics['male_members'] }}</div>
            <div class="stat-label">Male</div>
        </div>
        <div class="stat-item" style="border-right: none;">
            <div class="stat-value">{{ $statistics['female_members'] }}</div>
            <div class="stat-label">Female</div>
        </div>
    </div>
    @endif

    @if(!empty($filters))
    <div class="filters-section">
        <strong>Applied Filters:</strong>
        @foreach($filters as $key => $value)
            @if($value && $key !== 'format')
                {{ ucwords(str_replace('_', ' ', $key)) }}: {{ $value }} |
            @endif
        @endforeach
    </div>
    @endif

    @if($members && $members->count() > 0)
    <table class="members-table">
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="15%">Full Name</th>
                <th width="8%">Gender/Age</th>
                <th width="12%">Contact Info</th>
                <th width="12%">Church Details</th>
                <th width="10%">Membership</th>
                <th width="12%">Sacraments</th>
                <th width="10%">Family Status</th>
                <th width="8%">Education</th>
                <th width="10%">Additional Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $index => $member)
            <tr>
                <td>{{ $index + 1 }}</td>
                
                <!-- Full Name -->
                <td>
                    <strong>{{ $member->first_name }} {{ $member->last_name }}</strong>
                    @if($member->middle_name)
                        <br><span class="abbreviated">{{ $member->middle_name }}</span>
                    @endif
                    @if($member->member_number)
                        <br><span class="abbreviated">ID: {{ $member->member_number }}</span>
                    @endif
                </td>
                
                <!-- Gender/Age -->
                <td>
                    {{ $member->gender ?? 'N/A' }}
                    @if($member->date_of_birth)
                        <br><span class="abbreviated">{{ \Carbon\Carbon::parse($member->date_of_birth)->age }}yrs</span>
                        <br><span class="abbreviated">{{ \Carbon\Carbon::parse($member->date_of_birth)->format('d/m/Y') }}</span>
                    @endif
                </td>
                
                <!-- Contact Info -->
                <td class="contact-info">
                    @if($member->phone)
                        📞 {{ $member->phone }}<br>
                    @endif
                    @if($member->email)
                        ✉ {{ $member->email }}<br>
                    @endif
                    @if($member->residence)
                        🏠 {{ $member->residence }}
                    @endif
                </td>
                
                <!-- Church Details -->
                <td class="abbreviated">
                    @if($member->local_church)
                        <strong>{{ $member->local_church }}</strong><br>
                    @endif
                    @if($member->church_group)
                        Group: {{ $member->church_group }}<br>
                    @endif
                    @if($member->small_christian_community)
                        SCC: {{ $member->small_christian_community }}
                    @endif
                </td>
                
                <!-- Membership -->
                <td>
                    <span class="membership-status status-{{ strtolower($member->membership_status ?? 'active') }}">
                        {{ $member->membership_status ?? 'Active' }}
                    </span>
                    @if($member->membership_date)
                        <br><span class="abbreviated">Since: {{ \Carbon\Carbon::parse($member->membership_date)->format('M Y') }}</span>
                    @endif
                </td>
                
                <!-- Sacraments -->
                <td class="sacrament-status abbreviated">
                    <span class="{{ $member->baptism_date ? 'sacrament-yes' : 'sacrament-no' }}">
                        B: {{ $member->baptism_date ? \Carbon\Carbon::parse($member->baptism_date)->format('M Y') : '✗' }}
                    </span><br>
                    <span class="{{ $member->confirmation_date ? 'sacrament-yes' : 'sacrament-no' }}">
                        C: {{ $member->confirmation_date ? \Carbon\Carbon::parse($member->confirmation_date)->format('M Y') : '✗' }}
                    </span><br>
                    @if($member->matrimony_status === 'married')
                        <span class="sacrament-yes">M: {{ $member->marriage_type ?? 'Yes' }}</span>
                    @else
                        <span class="sacrament-no">M: ✗</span>
                    @endif
                </td>
                
                <!-- Family Status -->
                <td class="abbreviated">
                    @if($member->matrimony_status)
                        <strong>{{ ucfirst($member->matrimony_status) }}</strong><br>
                    @endif
                    @if($member->spouse_name)
                        Spouse: {{ $member->spouse_name }}<br>
                    @endif
                    @if($member->children_count)
                        Children: {{ $member->children_count }}
                    @endif
                </td>
                
                <!-- Education -->
                <td class="abbreviated">
                    @if($member->education_level)
                        {{ $member->education_level }}<br>
                    @endif
                    @if($member->occupation)
                        {{ $member->occupation }}
                    @endif
                </td>
                
                <!-- Additional Info -->
                <td class="abbreviated">
                    @if($member->tribe)
                        Tribe: {{ $member->tribe }}<br>
                    @endif
                    @if($member->special_needs)
                        Needs: {{ $member->special_needs }}<br>
                    @endif
                    @if($member->skills_talents)
                        Skills: {{ Str::limit($member->skills_talents, 20) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($members->count() > 50)
    <div class="additional-info">
        <strong>Report Summary:</strong> This comprehensive report contains detailed information for {{ $members->count() }} parish members, 
        including personal details, sacramental records, church involvement, and family information. All data is sourced directly from 
        the parish database and is current as of {{ $exportDate }}.
    </div>
    @endif

    @else
    <div class="no-data">
        No members found matching the specified criteria.
    </div>
    @endif

    <div class="footer">
        <div>
            {{ $parish_name ?? 'Sacred Heart Kandara Parish' }} - Comprehensive Members Report | 
            Generated: {{ $exportDate }} | 
            <span class="page-number"></span>
        </div>
        <div style="margin-top: 2px;">
            This document contains confidential parish information. Handle with care and maintain privacy standards.
        </div>
    </div>
</body>
</html>