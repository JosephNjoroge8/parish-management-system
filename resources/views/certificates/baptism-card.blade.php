<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Baptism Certificate - {{ $member->full_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .parish-name {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .diocese {
            font-size: 14pt;
            margin-bottom: 10px;
        }
        
        .certificate-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 15px;
        }
        
        .content-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding: 3px 0;
        }
        
        .info-label {
            font-weight: bold;
            width: 35%;
            display: inline-block;
        }
        
        .info-value {
            width: 65%;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-height: 20px;
            padding-left: 5px;
        }
        
        .baptism-details {
            background-color: #f9f9f9;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 50px;
        }
        
        .date-issued {
            text-align: right;
            margin-top: 30px;
            font-style: italic;
        }
        
        .seal-area {
            text-align: center;
            margin: 30px 0;
            height: 80px;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 30px;
            color: #666;
        }
        
        .record-number {
            text-align: right;
            font-size: 10pt;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="record-number">
        <strong>Certificate No:</strong> {{ $baptismRecord->record_number ?? 'BAP-' . str_pad($member->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="header">
        <div class="parish-name">{{ $parish_name ?? 'Sacred Heart Kandara Parish' }}</div>
        <div class="diocese">Catholic Diocese of Murang'a</div>
        <div class="certificate-title">Certificate of Baptism</div>
    </div>

    <!-- Personal Information -->
    <div class="content-section">
        <div class="section-title">Personal Information</div>
        
        <div class="info-row">
            <span class="info-label">Full Name:</span>
            <span class="info-value">{{ $member->first_name }} {{ $member->middle_name }} {{ $member->last_name }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Father's Name:</span>
            <span class="info-value">{{ $baptismRecord->father_name ?? $member->parent?->first_name . ' ' . $member->parent?->last_name ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Mother's Name:</span>
            <span class="info-value">{{ $baptismRecord->mother_name ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span class="info-value">{{ $member->date_of_birth ? $member->date_of_birth->format('F j, Y') : 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Place of Birth:</span>
            <span class="info-value">{{ $baptismRecord->birth_village ?? 'N/A' }}, {{ $baptismRecord->county ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Tribe:</span>
            <span class="info-value">{{ $member->tribe ?? $baptismRecord->tribe ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Residence:</span>
            <span class="info-value">{{ $member->residence ?? $baptismRecord->residence ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Baptism Information -->
    <div class="content-section baptism-details">
        <div class="section-title">Baptism Details</div>
        
        <div class="info-row">
            <span class="info-label">Baptized At:</span>
            <span class="info-value">{{ $baptismRecord->baptism_location ?? $member->local_church ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date of Baptism:</span>
            <span class="info-value">{{ $member->baptism_date ? $member->baptism_date->format('F j, Y') : 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Baptized By:</span>
            <span class="info-value">{{ $baptismRecord->baptized_by ?? $member->minister?->first_name . ' ' . $member->minister?->last_name ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Sponsor/Godparent:</span>
            <span class="info-value">{{ $baptismRecord->sponsor ?? $member->godparent?->first_name . ' ' . $member->godparent?->last_name ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Eucharist Information (if available) -->
    @if($member->confirmation_date || ($baptismRecord && $baptismRecord->eucharist_date))
    <div class="content-section">
        <div class="section-title">First Holy Communion</div>
        
        <div class="info-row">
            <span class="info-label">Location:</span>
            <span class="info-value">{{ $baptismRecord->eucharist_location ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $baptismRecord && $baptismRecord->eucharist_date ? \Carbon\Carbon::parse($baptismRecord->eucharist_date)->format('F j, Y') : 'N/A' }}</span>
        </div>
    </div>
    @endif

    <!-- Confirmation Information (if available) -->
    @if($member->confirmation_date || ($baptismRecord && $baptismRecord->confirmation_date))
    <div class="content-section">
        <div class="section-title">Confirmation</div>
        
        <div class="info-row">
            <span class="info-label">Location:</span>
            <span class="info-value">{{ $baptismRecord->confirmation_location ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $member->confirmation_date ? $member->confirmation_date->format('F j, Y') : ($baptismRecord && $baptismRecord->confirmation_date ? \Carbon\Carbon::parse($baptismRecord->confirmation_date)->format('F j, Y') : 'N/A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Register No:</span>
            <span class="info-value">{{ $baptismRecord->confirmation_register_number ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Confirmation No:</span>
            <span class="info-value">{{ $baptismRecord->confirmation_number ?? 'N/A' }}</span>
        </div>
    </div>
    @endif

    <!-- Marriage Information (if married) -->
    @if($member->matrimony_status === 'married' && $member->marriage_type === 'church')
    <div class="content-section">
        <div class="section-title">Marriage</div>
        
        <div class="info-row">
            <span class="info-label">Spouse:</span>
            <span class="info-value">{{ $baptismRecord->marriage_spouse ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Location:</span>
            <span class="info-value">{{ $baptismRecord->marriage_location ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $baptismRecord && $baptismRecord->marriage_date ? \Carbon\Carbon::parse($baptismRecord->marriage_date)->format('F j, Y') : 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Register No:</span>
            <span class="info-value">{{ $baptismRecord->marriage_register_number ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Marriage No:</span>
            <span class="info-value">{{ $baptismRecord->marriage_number ?? 'N/A' }}</span>
        </div>
    </div>
    @endif

    <div class="seal-area">
        [PARISH SEAL]
    </div>

    <div class="signature-section">
        <div class="signature-box">
            Parish Priest
        </div>
        <div class="signature-box">
            Parish Secretary
        </div>
    </div>

    <div class="date-issued">
        <strong>Date Issued:</strong> {{ now()->format('F j, Y') }}
    </div>

    <div class="footer">
        This certificate is issued in accordance with Canon Law and the regulations of the Catholic Church.<br>
        For verification, contact {{ $parish_name ?? 'Sacred Heart Kandara Parish' }} - Phone: {{ config('app.parish_phone', '+254 XXX XXX XXX') }}
    </div>
</body>
</html>