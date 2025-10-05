<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Summary - {{ $member->full_name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        
        .member-id {
            font-size: 14px;
            color: #3b82f6;
            font-weight: bold;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-section {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .icon {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }
        
        .label {
            font-weight: bold;
            color: #4b5563;
            width: 120px;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .value {
            color: #1f2937;
            flex: 1;
            font-size: 14px;
        }
        
        .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status.active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status.inactive {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status.transferred {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status.deceased {
            background-color: #f3f4f6;
            color: #374151;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">⛪ Parish Management System</div>
        <div class="title">Member Profile Summary</div>
        <div class="subtitle">{{ $member->full_name }}</div>
        <div class="member-id">Member ID: {{ $member->id }}</div>
    </div>

    <div class="summary-grid">
        <!-- Personal Information -->
        <div class="summary-section">
            <div class="section-title">
                <span class="icon">👤</span>
                Personal Information
            </div>
            <div class="row">
                <div class="label">Full Name:</div>
                <div class="value">{{ $member->full_name }}</div>
            </div>
            <div class="row">
                <div class="label">Date of Birth:</div>
                <div class="value">
                    @if($member->date_of_birth)
                        {{ \Carbon\Carbon::parse($member->date_of_birth)->format('M j, Y') }}
                        ({{ \Carbon\Carbon::parse($member->date_of_birth)->age }} years)
                    @else
                        Not specified
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="label">Gender:</div>
                <div class="value">{{ $member->gender ?? 'Not specified' }}</div>
            </div>
            <div class="row">
                <div class="label">ID Number:</div>
                <div class="value">{{ $member->id_number ?? 'Not specified' }}</div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="summary-section">
            <div class="section-title">
                <span class="icon">📞</span>
                Contact Information
            </div>
            <div class="row">
                <div class="label">Phone:</div>
                <div class="value">{{ $member->phone ?? 'Not specified' }}</div>
            </div>
            <div class="row">
                <div class="label">Email:</div>
                <div class="value">{{ $member->email ?? 'Not specified' }}</div>
            </div>
            <div class="row">
                <div class="label">Residence:</div>
                <div class="value">{{ $member->residence ?? 'Not specified' }}</div>
            </div>
            @if($member->emergency_contact)
            <div class="row">
                <div class="label">Emergency:</div>
                <div class="value">{{ $member->emergency_contact }}</div>
            </div>
            @endif
        </div>

        <!-- Church Information -->
        <div class="summary-section">
            <div class="section-title">
                <span class="icon">⛪</span>
                Church Information
            </div>
            <div class="row">
                <div class="label">Local Church:</div>
                <div class="value">{{ $member->local_church ?? 'Not specified' }}</div>
            </div>
            <div class="row">
                <div class="label">SCC:</div>
                <div class="value">{{ $member->small_christian_community ?? 'Not specified' }}</div>
            </div>
            <div class="row">
                <div class="label">Church Group:</div>
                <div class="value">{{ $member->church_group ?? 'Not specified' }}</div>
            </div>
            @if($member->baptism_date)
            <div class="row">
                <div class="label">Baptized:</div>
                <div class="value">{{ \Carbon\Carbon::parse($member->baptism_date)->format('M j, Y') }}</div>
            </div>
            @endif
        </div>

        <!-- Membership Information -->
        <div class="summary-section">
            <div class="section-title">
                <span class="icon">📋</span>
                Membership Status
            </div>
            <div class="row">
                <div class="label">Status:</div>
                <div class="value">
                    <span class="status {{ $member->membership_status ?? 'active' }}">
                        {{ ucfirst($member->membership_status ?? 'active') }}
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="label">Member Since:</div>
                <div class="value">
                    @if($member->membership_date)
                        {{ \Carbon\Carbon::parse($member->membership_date)->format('M j, Y') }}
                    @else
                        Not specified
                    @endif
                </div>
            </div>
            @if($member->matrimony_status)
            <div class="row">
                <div class="label">Matrimony:</div>
                <div class="value">{{ ucfirst($member->matrimony_status) }}</div>
            </div>
            @endif
            @if($member->occupation)
            <div class="row">
                <div class="label">Occupation:</div>
                <div class="value">{{ ucfirst(str_replace('_', ' ', $member->occupation)) }}</div>
            </div>
            @endif
        </div>

        <!-- Family Information -->
        @if($member->family)
        <div class="summary-section full-width">
            <div class="section-title">
                <span class="icon">👨‍👩‍👧‍👦</span>
                Family Information
            </div>
            <div class="row">
                <div class="label">Family Name:</div>
                <div class="value">
                    {{ $member->family->family_name }}
                    @if($member->family->family_code)
                        ({{ $member->family->family_code }})
                    @endif
                </div>
            </div>
            @if($member->family->head_of_family_name)
            <div class="row">
                <div class="label">Head of Family:</div>
                <div class="value">{{ $member->family->head_of_family_name }}</div>
            </div>
            @endif
            @if($member->family->parish_section)
            <div class="row">
                <div class="label">Parish Section:</div>
                <div class="value">{{ $member->family->parish_section }}</div>
            </div>
            @endif
        </div>
        @endif
    </div>

    <div class="footer">
        <div>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }} by {{ $generated_by }}</div>
        <div>Parish Management System - Member Profile Summary</div>
    </div>
</body>
</html>