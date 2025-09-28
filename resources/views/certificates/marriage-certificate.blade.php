<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Certificate</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 8mm;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .certificate-container {
            width: 100%;
            height: 100vh;
            margin: 0;
            border: 1px solid #000;
            padding: 8px;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            position: relative;
            flex-shrink: 0;
        }
        
        .form-number {
            position: absolute;
            top: 0;
            left: 0;
            font-size: 8px;
            font-weight: bold;
        }
        
        .certificate-number {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            font-weight: bold;
        }
        
        .country-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 5px 0;
        }
        
        .act-title {
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .certificate-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .marriage-location {
            margin: 8px 0;
            text-align: left;
            font-size: 9px;
            flex-shrink: 0;
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            flex: 1;
            font-size: 8px;
        }
        
        .form-table td, .form-table th {
            border: 1px solid #000;
            padding: 3px 4px;
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
            background-color: #f5f5f5;
            width: 25%;
            font-size: 8px;
        }
        
        .field-value {
            width: 75%;
            min-height: 12px;
            font-size: 8px;
        }
        
        .section-header {
            background-color: #333;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 9px;
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
            padding-right: 10px;
        }
        
        .column:last-child {
            padding-left: 10px;
        }
        
        .marriage-details {
            margin-top: 8px;
            border: 1px solid #000;
            padding: 8px;
            flex-shrink: 0;
            font-size: 8px;
        }
        
        .signature-section {
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 8px;
        }
        
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 100px;
            margin: 0 3px;
        }
        
        .footer {
            margin-top: 8px;
            font-size: 7px;
            text-align: left;
            flex-shrink: 0;
        }
        
        .small-text {
            font-size: 7px;
        }
        
        @media print {
            body { margin: 0; padding: 0; }
            .certificate-container { 
                border: 1px solid #000; 
                box-shadow: none; 
                page-break-inside: avoid;
                height: 100vh;
            }
            @page { margin: 8mm; }
        }
        
        @media screen and (max-width: 768px) {
            .certificate-container {
                padding: 4px;
                font-size: 8px;
            }
            .form-table { font-size: 7px; }
            .marriage-details { font-size: 7px; }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <!-- Header -->
        <div class="header">
            <div class="form-number">FORM MA1</div>
            <div class="certificate-number">№ {{ $marriageRecord->certificate_number ?? str_pad($marriageRecord->id, 6, '0', STR_PAD_LEFT) }}</div>
            
            <div class="country-title">REPUBLIC OF KENYA</div>
            <div class="act-title">THE MARRIAGE ACT, 2014</div>
            <div class="certificate-title">CERTIFICATE OF MARRIAGE</div>
        </div>

        <!-- Marriage Location -->
        <div class="marriage-location">
            Marriage solemnised at 
            <span class="dotted-line">{{ $marriageRecord->marriage_location ?? '................................' }}</span>
            in
            <span class="dotted-line">{{ $marriageRecord->sub_county ?? '................................' }}</span>
            Sub-county
            <span class="dotted-line">{{ $marriageRecord->county ?? '................................' }}</span>
            County in the Republic of Kenya
        </div>

        <!-- Main Form Table -->
        <table class="form-table">
            <!-- Date and Marriage Entry Number -->
            <tr>
                <td class="field-label">Date of marriage:</td>
                <td class="field-value">{{ $marriageRecord->marriage_date ? \Carbon\Carbon::parse($marriageRecord->marriage_date)->format('d/m/Y') : '' }}</td>
                <td class="field-label">Marriage Entry No:</td>
                <td class="field-value">{{ $marriageRecord->entry_number ?? str_pad($marriageRecord->id, 4, '0', STR_PAD_LEFT) }}</td>
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
                <td rowspan="2" class="field-value">{{ $marriageRecord->husband_residence ?? '' }}</td>
                <td class="field-label">Marital status:</td>
                <td class="field-value">{{ $marriageRecord->husband_marital_status ?? 'Single' }}</td>
            </tr>
            <tr>
                <td class="field-label">County:</td>
                <td class="field-value">{{ $marriageRecord->husband_county ?? '' }}</td>
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
                <td rowspan="2" class="field-value">{{ $marriageRecord->wife_residence ?? '' }}</td>
                <td class="field-label">Marital status:</td>
                <td class="field-value">{{ $marriageRecord->wife_marital_status ?? 'Single' }}</td>
            </tr>
            <tr>
                <td class="field-label">County:</td>
                <td class="field-value">{{ $marriageRecord->wife_county ?? '' }}</td>
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
            <p>
                Married in the 
                <span class="dotted-line">{{ $marriageRecord->religion ?? 'Civil' }}</span>
                By Registrar's Certificate/Special License No. 
                <span class="dotted-line">{{ $marriageRecord->license_number ?? '' }}</span>
            </p>
            
            <p style="text-align: center; margin: 20px 0;">
                by 
                <span class="dotted-line">{{ $marriageRecord->officiant_name ?? '' }}</span>
            </p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 8px;">
                <div style="flex: 1;">
                    This marriage was solemnized between
                    <div style="border: 1px solid #000; height: 30px; margin: 4px 0; padding: 4px; font-size: 7px;">
                        {{ $marriageRecord->husband_name ?? '' }}<br>
                        {{ $marriageRecord->wife_name ?? '' }}
                    </div>
                </div>
                
                <div style="flex: 0 0 60px; text-align: center; padding: 0 8px; font-size: 7px;">
                    (in the presence of)
                </div>
                
                <div style="flex: 1;">
                    <div style="border: 1px solid #000; height: 50px; padding: 4px; font-size: 7px;">
                        <strong>Witnesses:</strong><br>
                        1. {{ $marriageRecord->witness1_name ?? '' }}<br>
                        2. {{ $marriageRecord->witness2_name ?? '' }}<br>
                        <strong>Officiant:</strong><br>
                        {{ $marriageRecord->officiant_name ?? '' }}
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