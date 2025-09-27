<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Marriage Certificate - {{ $marriageRecord->husband_name }} & {{ $marriageRecord->wife_name }}</title>
    <style>
        @page {
            size: A4 landscape;
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
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .parish-name {
            font-size: 20pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .diocese {
            font-size: 16pt;
            margin-bottom: 10px;
        }
        
        .certificate-title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 15px;
            color: #8B0000;
        }
        
        .marriage-announcement {
            font-size: 14pt;
            font-style: italic;
            margin: 20px 0;
            text-align: center;
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 5px solid #8B0000;
        }
        
        .couple-section {
            display: table;
            width: 100%;
            margin: 25px 0;
        }
        
        .spouse-info {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #fafafa;
        }
        
        .spouse-info.husband {
            margin-right: 2%;
        }
        
        .spouse-title {
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            border-bottom: 2px solid #8B0000;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .marriage-details {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #8B0000;
            background-color: #fff8f8;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            color: #8B0000;
            border-bottom: 1px solid #8B0000;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .info-row {
            margin-bottom: 12px;
            padding: 3px 0;
        }
        
        .info-label {
            font-weight: bold;
            width: 40%;
            display: inline-block;
        }
        
        .info-value {
            width: 58%;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-height: 20px;
            padding-left: 5px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 30%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 50px;
            vertical-align: top;
        }
        
        .date-issued {
            text-align: right;
            margin-top: 30px;
            font-style: italic;
        }
        
        .seal-area {
            text-align: center;
            margin: 30px auto;
            width: 150px;
            height: 100px;
            border: 2px dashed #8B0000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8B0000;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 30px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .record-number {
            text-align: right;
            font-size: 10pt;
            margin-bottom: 20px;
        }
        
        .witnesses {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .witness-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .witness-cell {
            display: table-cell;
            width: 48%;
            padding: 5px;
        }
        
        .biblical-quote {
            text-align: center;
            font-style: italic;
            margin: 20px 0;
            padding: 15px;
            background-color: #f0f8ff;
            border-left: 3px solid #8B0000;
            font-size: 11pt;
        }
    </style>
</head>
<body>
    <div class="record-number">
        <strong>Certificate No:</strong> {{ $marriageRecord->certificate_number ?? 'MAR-' . str_pad($marriageRecord->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="header">
        <div class="parish-name">{{ $parish_name ?? 'Sacred Heart Kandara Parish' }}</div>
        <div class="diocese">Catholic Diocese of Murang'a</div>
        <div class="certificate-title">Certificate of Marriage</div>
    </div>

    <div class="marriage-announcement">
        This is to certify that <strong>{{ $marriageRecord->husband_name }}</strong> and <strong>{{ $marriageRecord->wife_name }}</strong><br>
        were lawfully joined in Holy Matrimony according to the rites of the Roman Catholic Church
    </div>

    <div class="biblical-quote">
        "Therefore what God has joined together, let no one separate." - Mark 10:9
    </div>

    <!-- Couple Information -->
    <div class="couple-section">
        <div class="spouse-info husband">
            <div class="spouse-title">Husband</div>
            
            <div class="info-row">
                <span class="info-label">Full Name:</span>
                <span class="info-value">{{ $marriageRecord->husband_name }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Father's Name:</span>
                <span class="info-value">{{ $marriageRecord->husband_father_name ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Mother's Name:</span>
                <span class="info-value">{{ $marriageRecord->husband_mother_name ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value">{{ $marriageRecord->husband_birth_date ? \Carbon\Carbon::parse($marriageRecord->husband_birth_date)->format('F j, Y') : 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Place of Birth:</span>
                <span class="info-value">{{ $marriageRecord->husband_birth_place ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Residence:</span>
                <span class="info-value">{{ $marriageRecord->husband_residence ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tribe:</span>
                <span class="info-value">{{ $marriageRecord->husband_tribe ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Baptism Parish:</span>
                <span class="info-value">{{ $marriageRecord->husband_baptism_parish ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="spouse-info">
            <div class="spouse-title">Wife</div>
            
            <div class="info-row">
                <span class="info-label">Full Name:</span>
                <span class="info-value">{{ $marriageRecord->wife_name }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Father's Name:</span>
                <span class="info-value">{{ $marriageRecord->wife_father_name ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Mother's Name:</span>
                <span class="info-value">{{ $marriageRecord->wife_mother_name ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value">{{ $marriageRecord->wife_birth_date ? \Carbon\Carbon::parse($marriageRecord->wife_birth_date)->format('F j, Y') : 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Place of Birth:</span>
                <span class="info-value">{{ $marriageRecord->wife_birth_place ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Residence:</span>
                <span class="info-value">{{ $marriageRecord->wife_residence ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tribe:</span>
                <span class="info-value">{{ $marriageRecord->wife_tribe ?? 'N/A' }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Baptism Parish:</span>
                <span class="info-value">{{ $marriageRecord->wife_baptism_parish ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Marriage Details -->
    <div class="marriage-details">
        <div class="section-title">Marriage Details</div>
        
        <div class="info-row">
            <span class="info-label">Date of Marriage:</span>
            <span class="info-value">{{ $marriageRecord->marriage_date ? \Carbon\Carbon::parse($marriageRecord->marriage_date)->format('F j, Y') : 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Place of Marriage:</span>
            <span class="info-value">{{ $marriageRecord->marriage_location ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Officiant:</span>
            <span class="info-value">{{ $marriageRecord->officiant_name ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Register Volume:</span>
            <span class="info-value">{{ $marriageRecord->register_volume ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Register Number:</span>
            <span class="info-value">{{ $marriageRecord->register_number ?? 'N/A' }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Page Number:</span>
            <span class="info-value">{{ $marriageRecord->page_number ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Witnesses -->
    @if($marriageRecord->witness1_name || $marriageRecord->witness2_name)
    <div class="witnesses">
        <div class="section-title">Witnesses</div>
        
        <div class="witness-row">
            <div class="witness-cell">
                <div class="info-row">
                    <span class="info-label">Witness 1:</span>
                    <span class="info-value">{{ $marriageRecord->witness1_name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="witness-cell">
                <div class="info-row">
                    <span class="info-label">Witness 2:</span>
                    <span class="info-value">{{ $marriageRecord->witness2_name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="seal-area">
        [PARISH SEAL]
    </div>

    <div class="signature-section">
        <div class="signature-box">
            Parish Priest<br>
            <small>Celebrating Priest</small>
        </div>
        <div class="signature-box">
            Parish Secretary<br>
            <small>Registrar</small>
        </div>
        <div class="signature-box">
            Bishop/Delegate<br>
            <small>Ecclesiastical Authority</small>
        </div>
    </div>

    <div class="date-issued">
        <strong>Date Issued:</strong> {{ now()->format('F j, Y') }}
    </div>

    <div class="footer">
        This certificate is issued in accordance with Canon Law and the regulations of the Catholic Church.<br>
        For verification, contact {{ $parish_name ?? 'Sacred Heart Kandara Parish' }} - Phone: {{ config('app.parish_phone', '+254 XXX XXX XXX') }}<br>
        <em>"Marriage is a sacred covenant between a man and a woman, instituted by God and blessed by His Church."</em>
    </div>
</body>
</html>