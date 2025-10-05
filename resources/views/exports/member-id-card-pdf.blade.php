<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member ID Card - {{ $member->full_name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            background-color: #ffffff;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .id-card {
            width: 240px;
            height: 150px;
            border: 2px solid #d97706;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .card-header {
            text-align: center;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
            padding: 4px 0;
            margin: -8px -8px 8px -8px;
            border-radius: 10px 10px 0 0;
        }
        
        .church-name {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-title {
            font-size: 8px;
            margin-top: 2px;
            opacity: 0.9;
        }
        
        .member-info {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
            border: 2px solid #white;
        }
        
        .member-details {
            flex: 1;
            min-width: 0;
        }
        
        .member-name {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2px;
            line-height: 1.2;
            word-wrap: break-word;
        }
        
        .member-id {
            font-size: 9px;
            color: #d97706;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .status {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 7px;
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
        
        .card-details {
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 6px;
            margin-bottom: 6px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 8px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #4b5563;
        }
        
        .detail-value {
            color: #1f2937;
            text-align: right;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .card-footer {
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            margin-top: 4px;
        }
        
        .barcode {
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 6px;
            color: #4b5563;
            margin: 2px 0;
            letter-spacing: 1px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 24px;
            color: rgba(217, 119, 6, 0.1);
            font-weight: bold;
            z-index: 1;
            pointer-events: none;
        }
        
        .card-content {
            position: relative;
            z-index: 2;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 5px;
            }
            
            .id-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="watermark">PARISH</div>
        
        <div class="card-content">
            <div class="card-header">
                <div class="church-name">Parish Management System</div>
                <div class="card-title">Member Identification Card</div>
            </div>
            
            <div class="member-info">
                <div class="avatar">
                    {{ strtoupper(substr($member->first_name ?? '?', 0, 1)) . strtoupper(substr($member->last_name ?? '?', 0, 1)) }}
                </div>
                <div class="member-details">
                    <div class="member-name">{{ $member->full_name }}</div>
                    <div class="member-id">ID: {{ $member->id }}</div>
                    <span class="status {{ $member->membership_status ?? 'active' }}">
                        {{ ucfirst($member->membership_status ?? 'active') }}
                    </span>
                </div>
            </div>
            
            <div class="card-details">
                @if($member->local_church)
                <div class="detail-row">
                    <span class="detail-label">Church:</span>
                    <span class="detail-value">{{ $member->local_church }}</span>
                </div>
                @endif
                
                @if($member->church_group)
                <div class="detail-row">
                    <span class="detail-label">Group:</span>
                    <span class="detail-value">{{ $member->church_group }}</span>
                </div>
                @endif
                
                @if($member->phone)
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $member->phone }}</span>
                </div>
                @endif
                
                @if($member->membership_date)
                <div class="detail-row">
                    <span class="detail-label">Member Since:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($member->membership_date)->format('M Y') }}</span>
                </div>
                @endif
                
                @if($member->family && $member->family->family_name)
                <div class="detail-row">
                    <span class="detail-label">Family:</span>
                    <span class="detail-value">{{ $member->family->family_name }}</span>
                </div>
                @endif
            </div>
            
            <div class="barcode">
                ||||| {{ str_pad($member->id, 6, '0', STR_PAD_LEFT) }} |||||
            </div>
            
            <div class="card-footer">
                <div>Valid {{ $generated_at->format('Y') }} • {{ $generated_at->format('M j, Y') }}</div>
            </div>
        </div>
    </div>
</body>
</html>