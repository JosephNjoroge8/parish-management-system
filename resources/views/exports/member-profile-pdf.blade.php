<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile - {{ $member->full_name }}</title>
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
            border-bottom: 3px solid #d97706;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #d97706;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 28px;
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
            color: #d97706;
            font-weight: bold;
        }
        
        .section {
            margin-bottom: 25px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #d97706;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
        }
        
        .row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .label {
            font-weight: bold;
            color: #374151;
            width: 180px;
            flex-shrink: 0;
        }
        
        .value {
            color: #1f2937;
            flex: 1;
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
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
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
        }
        
        .notes {
            background-color: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .notes-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .section {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">⛪ Parish Management System</div>
        <div class="title">Complete Member Profile</div>
        <div class="subtitle">{{ $member->full_name }}</div>
        <div class="member-id">Member ID: {{ $member->id }}</div>
    </div>

    <!-- Personal Information -->
    <div class="section">
        <div class="section-title">👤 Personal Information</div>
        <div class="row">
            <div class="label">Full Name:</div>
            <div class="value">{{ $member->full_name }}</div>
        </div>
        <div class="row">
            <div class="label">First Name:</div>
            <div class="value">{{ $member->first_name ?? 'Not specified' }}</div>
        </div>
        @if($member->middle_name)
        <div class="row">
            <div class="label">Middle Name:</div>
            <div class="value">{{ $member->middle_name }}</div>
        </div>
        @endif
        <div class="row">
            <div class="label">Last Name:</div>
            <div class="value">{{ $member->last_name ?? 'Not specified' }}</div>
        </div>
        <div class="row">
            <div class="label">Date of Birth:</div>
            <div class="value">
                @if($member->date_of_birth)
                    {{ \Carbon\Carbon::parse($member->date_of_birth)->format('F j, Y') }}
                    ({{ \Carbon\Carbon::parse($member->date_of_birth)->age }} years old)
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
            <div class="label">National ID:</div>
            <div class="value">{{ $member->id_number ?? 'Not specified' }}</div>
        </div>
        @if($member->tribe)
        <div class="row">
            <div class="label">Tribe:</div>
            <div class="value">{{ $member->tribe }}</div>
        </div>
        @endif
        @if($member->clan)
        <div class="row">
            <div class="label">Clan:</div>
            <div class="value">{{ $member->clan }}</div>
        </div>
        @endif
    </div>

    <!-- Contact Information -->
    <div class="section">
        <div class="section-title">📞 Contact Information</div>
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
    </div>

    <!-- Emergency Contact -->
    @if($member->emergency_contact || $member->emergency_phone)
    <div class="section">
        <div class="section-title">🚨 Emergency Contact</div>
        @if($member->emergency_contact)
        <div class="row">
            <div class="label">Contact Name:</div>
            <div class="value">{{ $member->emergency_contact }}</div>
        </div>
        @endif
        @if($member->emergency_phone)
        <div class="row">
            <div class="label">Contact Phone:</div>
            <div class="value">{{ $member->emergency_phone }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Church Information -->
    <div class="section">
        <div class="section-title">⛪ Church Information</div>
        <div class="row">
            <div class="label">Local Church:</div>
            <div class="value">{{ $member->local_church ?? 'Not specified' }}</div>
        </div>
        <div class="row">
            <div class="label">Small Christian Community:</div>
            <div class="value">{{ $member->small_christian_community ?? 'Not specified' }}</div>
        </div>
        <div class="row">
            <div class="label">Church Group:</div>
            <div class="value">{{ $member->church_group ?? 'Not specified' }}</div>
        </div>
        @if($member->additional_church_groups && count($member->additional_church_groups) > 0)
        <div class="row">
            <div class="label">Additional Groups:</div>
            <div class="value">{{ implode(', ', $member->additional_church_groups) }}</div>
        </div>
        @endif
        @if($member->baptism_date)
        <div class="row">
            <div class="label">Baptism Date:</div>
            <div class="value">{{ \Carbon\Carbon::parse($member->baptism_date)->format('F j, Y') }}</div>
        </div>
        @endif
        @if($member->confirmation_date)
        <div class="row">
            <div class="label">Confirmation Date:</div>
            <div class="value">{{ \Carbon\Carbon::parse($member->confirmation_date)->format('F j, Y') }}</div>
        </div>
        @endif
        @if($member->godparent)
        <div class="row">
            <div class="label">Godparent:</div>
            <div class="value">{{ $member->godparent }}</div>
        </div>
        @endif
        @if($member->minister)
        <div class="row">
            <div class="label">Minister:</div>
            <div class="value">{{ $member->minister }}</div>
        </div>
        @endif
    </div>

    <!-- Membership Information -->
    <div class="section">
        <div class="section-title">📋 Membership Information</div>
        <div class="row">
            <div class="label">Member Since:</div>
            <div class="value">
                @if($member->membership_date)
                    {{ \Carbon\Carbon::parse($member->membership_date)->format('F j, Y') }}
                @else
                    Not specified
                @endif
            </div>
        </div>
        <div class="row">
            <div class="label">Status:</div>
            <div class="value">
                <span class="status {{ $member->membership_status ?? 'active' }}">
                    {{ ucfirst($member->membership_status ?? 'active') }}
                </span>
            </div>
        </div>
        @if($member->matrimony_status)
        <div class="row">
            <div class="label">Matrimony Status:</div>
            <div class="value">
                {{ ucfirst($member->matrimony_status) }}
                @if($member->matrimony_status === 'married' && $member->marriage_type)
                    ({{ ucfirst($member->marriage_type) }} Marriage)
                @endif
            </div>
        </div>
        @endif
        @if($member->parent)
        <div class="row">
            <div class="label">Parent/Guardian:</div>
            <div class="value">{{ $member->parent }}</div>
        </div>
        @endif
    </div>

    <!-- Education & Occupation -->
    @if($member->education_level || $member->occupation)
    <div class="section">
        <div class="section-title">🎓 Education & Occupation</div>
        @if($member->education_level)
        <div class="row">
            <div class="label">Education Level:</div>
            <div class="value">{{ ucfirst($member->education_level) }}</div>
        </div>
        @endif
        @if($member->occupation)
        <div class="row">
            <div class="label">Occupation:</div>
            <div class="value">{{ ucfirst(str_replace('_', ' ', $member->occupation)) }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Family Information -->
    @if($member->family)
    <div class="section">
        <div class="section-title">👨‍👩‍👧‍👦 Family Information</div>
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
        @if($member->family->address)
        <div class="row">
            <div class="label">Family Address:</div>
            <div class="value">{{ $member->family->address }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Additional Notes -->
    @if($member->notes)
    <div class="notes">
        <div class="notes-title">📝 Additional Notes</div>
        <div>{{ $member->notes }}</div>
    </div>
    @endif

    <div class="footer">
        <div>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
        <div>Generated by: {{ $generated_by }}</div>
        <div>Parish Management System - Member Profile Report</div>
    </div>
</body>
</html>