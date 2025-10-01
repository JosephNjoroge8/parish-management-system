<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Baptism Card - {{ $member->full_name }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 5mm;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 8pt;
            line-height: 1.1;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #a8c4b8;
            height: 100%;
            width: 100%;
        }
        
        .card {
            background-color: #a8c4b8;
            width: 100%;
            height: 185mm;
            display: table;
            table-layout: fixed;
            padding: 8mm;
            box-sizing: border-box;
        }
        
        .left-column {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            padding-right: 5mm;
        }
        
        .right-column {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-left: 5mm;
        }
        
        .cell {
            margin-bottom: 5mm;
            padding: 3mm;
        }
        
        .cell:last-child {
            margin-bottom: 0;
        }
        
        .left-section {
            display: block;
        }
        
        .right-section {
            display: block;
        }
        
        .sacrament-cell {
            margin-bottom: 4mm;
            padding: 3mm;
        }
        
        .sacrament-cell:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3mm;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
        }
        
        .field {
            display: flex;
            align-items: baseline;
            margin-bottom: 2mm;
            font-size: 8pt;
            line-height: 1.2;
        }
        
        .field-label {
            font-weight: normal;
            white-space: nowrap;
            margin-right: 3mm;
            min-width: 15mm;
            font-size: 8pt;
        }
        
        .field-value {
            background-color: #a8c4b8;
            font-weight: normal;
            padding: 0 2mm;
            font-size: 8pt;
            white-space: nowrap;
            min-width: 25mm;
        }
        
        .dotted-line {
            border-bottom: 1px dotted #000;
            flex: 1;
            height: 1px;
            margin-left: 3mm;
        }
        
        /* Special styling for specific sections */
        .baptism-section {
            margin-top: 0;
        }
        
        .baptism-section .section-title {
            text-decoration: underline;
        }
        
        .confirmation-fields {
            display: flex;
            gap: 5mm;
        }
        
        .confirmation-fields .field {
            flex: 1;
        }
        
        .confirmation-fields .field-label {
            min-width: 12mm;
            font-size: 8pt;
        }
        
        .confirmation-fields .field-value {
            font-size: 8pt;
        }
        
        .marriage-section {
            margin-top: 0;
        }
        
        .name-line {
            border-bottom: 2px solid #000;
            height: 2px;
            margin: 2mm 0 4mm 0;
        }
        
        .sacrament-group {
            margin-bottom: 3mm;
        }
        
        .compact-field {
            margin-bottom: 2mm;
        }
        
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
            }
            
            .card { 
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                height: auto !important;
                width: 100% !important;
            }
            
            @page { 
                margin: 5mm !important;
                size: A5 landscape !important;
            }
        }
        
        @media screen and (max-width: 1024px) {
            html, body { font-size: 6pt; }
            .field { font-size: 5pt; }
            .field-label { font-size: 5pt; min-width: 7mm; }
            .field-value { font-size: 5pt; }
            .section-title { font-size: 6pt; }
        }
        
        @media screen and (max-width: 768px) {
            html, body { font-size: 5pt; }
            .card { padding: 2mm; }
            .field { font-size: 4pt; margin-bottom: 0.5mm; }
            .field-label { font-size: 4pt; min-width: 6mm; }
            .field-value { font-size: 4pt; }
            .section-title { font-size: 5pt; }
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Left Column - 2 Cells -->
        <div class="left-column">
            <!-- Cell 1: Personal Details -->
            <div class="cell">
                <!-- Member's Name -->
                <div style="text-align: center; font-weight: bold; font-size: 8pt; margin-bottom: 1mm; text-transform: uppercase; line-height: 1.0;">
                    {{ $member->full_name }}
                </div>
                <!-- Name line -->
                <div class="name-line"></div>
                
                <div class="field compact-field">
                    <span class="field-label">Father</span>
                    <span class="field-value">{{ $member->father_name }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Mother</span>
                    <span class="field-value">{{ $member->mother_name }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Tribe</span>
                    <span class="field-value">{{ $member->tribe }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Born on</span>
                    <span class="field-value">{{ $member->birth_village }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">County</span>
                    <span class="field-value">{{ $member->county }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Date</span>
                    <span class="field-value">{{ $member->date_of_birth ? \Carbon\Carbon::parse($member->date_of_birth)->format('d/m/Y') : '' }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Residence</span>
                    <span class="field-value">{{ $member->residence }}</span>
                    <div class="dotted-line"></div>
                </div>
            </div>
            
            <div class="cell">
                <div class="section-title">BAPTISM</div>
                
                <div class="field compact-field">
                    <span class="field-label">At</span>
                    <span class="field-value">{{ $member->baptism_location }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Date</span>
                    <span class="field-value">{{ $member->baptism_date ? \Carbon\Carbon::parse($member->baptism_date)->format('d/m/Y') : '' }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">By</span>
                    <span class="field-value">{{ $member->baptized_by }}</span>
                    <div class="dotted-line"></div>
                </div>
                
                <div class="field compact-field">
                    <span class="field-label">Sponsor</span>
                    <span class="field-value">{{ $member->sponsor }}</span>
                    <div class="dotted-line"></div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - 3 Cells -->
        <div class="right-column">
            <div class="right-section">
                <!-- Cell 3: Eucharist -->
                <div class="sacrament-cell">
                    <div class="section-title">EUCHARIST</div>
                    
                    <div class="field compact-field">
                        <span class="field-label">At</span>
                        <span class="field-value">{{ $member->eucharist_location }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="field compact-field">
                        <span class="field-label">Date</span>
                        <span class="field-value">{{ $member->eucharist_date ? \Carbon\Carbon::parse($member->eucharist_date)->format('d/m/Y') : '' }}</span>
                        <div class="dotted-line"></div>
                    </div>
                </div>
                
                <!-- Cell 4: Confirmation -->
                <div class="sacrament-cell">
                    <div class="section-title">CONFIRMATION</div>
                    
                    <div class="field compact-field">
                        <span class="field-label">At</span>
                        <span class="field-value">{{ $member->confirmation_location }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="field compact-field">
                        <span class="field-label">Date</span>
                        <span class="field-value">{{ $member->confirmation_date ? \Carbon\Carbon::parse($member->confirmation_date)->format('d/m/Y') : '' }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="confirmation-fields">
                        <div class="field">
                            <span class="field-label">Reg.No.</span>
                            <span class="field-value">{{ $member->confirmation_register_number }}</span>
                            <div class="dotted-line"></div>
                        </div>
                        
                        <div class="field">
                            <span class="field-label">Conf.No.</span>
                            <span class="field-value">{{ $member->confirmation_number }}</span>
                            <div class="dotted-line"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Cell 5: Marriage -->
                <div class="sacrament-cell">
                    <div class="section-title">MARRIAGE</div>
                    
                    <div class="field compact-field">
                        <span class="field-label">With</span>
                        <span class="field-value">{{ $member->marriage_spouse }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="field compact-field">
                        <span class="field-label">At</span>
                        <span class="field-value">{{ $member->marriage_location }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="field compact-field">
                        <span class="field-label">Date</span>
                        <span class="field-value">{{ $member->marriage_date ? \Carbon\Carbon::parse($member->marriage_date)->format('d/m/Y') : '' }}</span>
                        <div class="dotted-line"></div>
                    </div>
                    
                    <div class="confirmation-fields">
                        <div class="field">
                            <span class="field-label">Reg.No.</span>
                            <span class="field-value">{{ $member->marriage_register_number }}</span>
                            <div class="dotted-line"></div>
                        </div>
                        
                        <div class="field">
                            <span class="field-label">Mar.No.</span>
                            <span class="field-value">{{ $member->marriage_number }}</span>
                            <div class="dotted-line"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>