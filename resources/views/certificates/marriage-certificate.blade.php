<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Certificate</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .certificate-container {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            border: 2px solid #000;
            padding: 15px;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            flex-shrink: 0;
        }
        
        .form-number {
            position: absolute;
            top: 0;
            left: 0;
            font-size: 10px;
            font-weight: bold;
        }
        
        .certificate-number {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            font-weight: bold;
        }
        
        .country-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 8px 0;
            letter-spacing: 1px;
        }
        
        .act-title {
            font-size: 12px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .certificate-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        
        .marriage-location {
            margin: 12px 0;
            text-align: left;
            font-size: 11px;
            flex-shrink: 0;
            line-height: 1.4;
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            flex: 1;
            font-size: 10px;
        }
        
        .form-table td, .form-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .form-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .field-label {
            font-weight: bold;
            background-color: #f8f8f8;
            width: 25%;
            font-size: 10px;
        }
        
        .field-value {
            width: 75%;
            min-height: 16px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .section-header {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .two-column {
            display: table;
            width: 100%;
        }
        
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .column:first-child {
            border-right: 1px solid #000;
            padding-right: 15px;
        }
        
        .column:last-child {
            padding-left: 15px;
        }
        
        .marriage-details {
            margin-top: 12px;
            border: 2px solid #000;
            padding: 12px;
            flex-shrink: 0;
            font-size: 10px;
            background-color: #fafafa;
        }
        
        .signature-section {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            border: 1px solid #000;
            padding: 12px;
            text-align: center;
            font-size: 10px;
        }
        
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 120px;
            margin: 0 5px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 12px;
            font-size: 9px;
            text-align: left;
            flex-shrink: 0;
        }
        
        .small-text {
            font-size: 8px;
        }
        
        @media print {
            body { margin: 0; padding: 0; }
            .certificate-container { 
                border: 2px solid #000; 
                box-shadow: none; 
                page-break-inside: avoid;
                min-height: 100vh;
            }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <!-- Header -->
        <div class="header">
            <div class="form-number">FORM MA1</div>
            <div class="certificate-number">№ {{ $marriageRecord->civil_marriage_certificate_number ?? $marriageRecord->record_number ?? str_pad($marriageRecord->id, 6, '0', STR_PAD_LEFT) }}</div>
            
            <div class="country-title">REPUBLIC OF KENYA</div>
            <div class="act-title">THE MARRIAGE ACT, 2014</div>
            <div class="certificate-title">CERTIFICATE OF MARRIAGE</div>
        </div>

        <!-- Marriage Location -->
        <div class="marriage-location">
            Marriage solemnised at 
            <span class="dotted-line">{{ $marriageRecord->marriage_church ?? 'Sacred Heart Kandara Parish' }}</span>
            in
            <span class="dotted-line">{{ $marriageRecord->district ?? 'Kandara' }}</span>
            Sub-county
            <span class="dotted-line">{{ $marriageRecord->province ?? 'Murang\'a' }}</span>
            County in the Republic of Kenya
        </div>

        <!-- Main Form Table -->
        <table class="form-table">
            <!-- Date and Marriage Entry Number -->
            <tr>
                <td class="field-label">Date of marriage:</td>
                <td class="field-value">{{ $marriageRecord->marriage_date ? \Carbon\Carbon::parse($marriageRecord->marriage_date)->format('d/m/Y') : '' }}</td>
                <td class="field-label">Marriage Entry No:</td>
                <td class="field-value">{{ $marriageRecord->record_number ?? str_pad($marriageRecord->id, 4, '0', STR_PAD_LEFT) }}</td>
            </tr>

            <!-- Bridegroom Section -->
            <tr>
                <td class="section-header" colspan="4">BRIDEGROOM'S DETAILS</td>
            </tr>
            <tr>
                <td class="field-label">Bridegroom's name:</td>
                <td class="field-value">{{ $marriageRecord->husband_name ?? '' }}</td>
                <td class="field-label">Age:</td>
                <td class="field-value">{{ $marriageRecord->husband_age ?? '' }}</td>
            </tr>
            <tr>
                <td rowspan="2" class="field-label">Residence at the time of marriage:</td>
                <td rowspan="2" class="field-value">{{ $marriageRecord->husband_domicile ?? $marriageRecord->husband_residence ?? '' }}</td>
                <td class="field-label">Marital status:</td>
                <td class="field-value">{{ $marriageRecord->husband_widower_of ?? null ? 'Widower' : 'Single' }}</td>
            </tr>
            <tr>
                <td class="field-label">County:</td>
                <td class="field-value">{{ $marriageRecord->husband_county ?? $marriageRecord->province }}</td>
            </tr>
            <tr>
                <td class="field-label">Occupation:</td>
                <td class="field-value" colspan="3">{{ $marriageRecord->husband_occupation ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Father's name:</td>
                <td class="field-value">{{ $marriageRecord->husband_father_name ?? '' }}</td>
                <td class="field-label">Mother's name:</td>
                <td class="field-value">{{ $marriageRecord->husband_mother_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Occupation:</td>
                <td class="field-value">{{ $marriageRecord->husband_father_occupation ?? '' }}</td>
                <td class="field-label">Occupation:</td>
                <td class="field-value">{{ $marriageRecord->husband_mother_occupation ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Residence:</td>
                <td class="field-value">{{ $marriageRecord->husband_father_residence ?? '' }}</td>
                <td class="field-label">Residence:</td>
                <td class="field-value">{{ $marriageRecord->husband_mother_residence ?? '' }}</td>
            </tr>

            <!-- Bride Section -->
            <tr>
                <td class="section-header" colspan="4">BRIDE'S DETAILS</td>
            </tr>
            <tr>
                <td class="field-label">Bride's name:</td>
                <td class="field-value">{{ $marriageRecord->wife_name ?? '' }}</td>
                <td class="field-label">Age:</td>
                <td class="field-value">{{ $marriageRecord->wife_age ?? '' }}</td>
            </tr>
            <tr>
                <td rowspan="2" class="field-label">Residence at the time of marriage:</td>
                <td rowspan="2" class="field-value">{{ $marriageRecord->wife_domicile ?? $marriageRecord->wife_residence ?? '' }}</td>
                <td class="field-label">Marital status:</td>
                <td class="field-value">{{ $marriageRecord->wife_widow_of ?? null ? 'Widow' : 'Single' }}</td>
            </tr>
            <tr>
                <td class="field-label">County:</td>
                <td class="field-value">{{ $marriageRecord->wife_county ?? $marriageRecord->province }}</td>
            </tr>
            <tr>
                <td class="field-label">Occupation:</td>
                <td class="field-value" colspan="3">{{ $marriageRecord->wife_occupation ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Father's name:</td>
                <td class="field-value">{{ $marriageRecord->wife_father_name ?? '' }}</td>
                <td class="field-label">Mother's name:</td>
                <td class="field-value">{{ $marriageRecord->wife_mother_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Occupation:</td>
                <td class="field-value">{{ $marriageRecord->wife_father_occupation ?? '' }}</td>
                <td class="field-label">Occupation:</td>
                <td class="field-value">{{ $marriageRecord->wife_mother_occupation ?? '' }}</td>
            </tr>
            <tr>
                <td class="field-label">Residence:</td>
                <td class="field-value">{{ $marriageRecord->wife_father_residence ?? '' }}</td>
                <td class="field-label">Residence:</td>
                <td class="field-value">{{ $marriageRecord->wife_mother_residence ?? '' }}</td>
            </tr>
        </table>

        <!-- Marriage Officiation Details -->
        <div class="marriage-details">
            <p style="font-size: 11px; margin-bottom: 8px;">
                Married in the 
                <span class="dotted-line">{{ $marriageRecord->religion ?? 'Catholic' }}</span>
                By Registrar's Certificate/Special License No. 
                <span class="dotted-line">{{ $marriageRecord->civil_marriage_certificate_number ?? $marriageRecord->banns_number ?? '' }}</span>
            </p>
            
            <p style="text-align: center; margin: 25px 0; font-size: 12px; font-weight: bold;">
                by 
                <span class="dotted-line">{{ $marriageRecord->presence_of ?? 'Rev. Parish Priest' }}</span>
            </p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 10px;">
                <div style="flex: 1;">
                    <strong style="font-size: 11px;">This marriage was solemnized between:</strong>
                    <div style="border: 2px solid #000; min-height: 40px; margin: 8px 0; padding: 8px; font-size: 10px; background-color: white;">
                        <strong>Bridegroom:</strong> {{ $marriageRecord->husband_name ?? '' }}<br>
                        <strong>Bride:</strong> {{ $marriageRecord->wife_name ?? '' }}
                    </div>
                </div>
                
                <div style="flex: 0 0 80px; text-align: center; padding: 0 10px; font-size: 9px; display: flex; align-items: center; justify-content: center;">
                    <em>(in the presence of)</em>
                </div>
                
                <div style="flex: 1;">
                    <div style="border: 2px solid #000; min-height: 60px; padding: 8px; font-size: 10px; background-color: white;">
                        <strong style="font-size: 11px;">Witnesses:</strong><br>
                        1. {{ $marriageRecord->male_witness_full_name ?: 'Not provided' }}
                        @if($marriageRecord->male_witness_father ?? null)<span style="font-size: 8px;"> (s/o {{ $marriageRecord->male_witness_father }})</span>@endif<br><br>
                        2. {{ $marriageRecord->female_witness_full_name ?: 'Not provided' }}
                        @if($marriageRecord->female_witness_father ?? null)<span style="font-size: 8px;"> (d/o {{ $marriageRecord->female_witness_father }})</span>@endif<br><br>
                        <strong>Officiant:</strong><br>
                        {{ $marriageRecord->presence_of ?? 'Rev. Parish Priest' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="small-text">
                GPK (L) 1085—3m—5/2016
            </p>
        </div>
    </div>

    <script>
        // Auto-print functionality (optional)
        window.onload = function() {
            // Uncomment the line below if you want auto-print
            // window.print();
        }
    </script>
</body>
</html>