# Marriage Certificate System - Implementation Guide

## Overview
The Parish Management System now includes a comprehensive marriage certificate generation system that produces official Kenya Marriage Act 2014 compliant certificates.

## Features Implemented

### 1. **Certificate Template**
- **File**: `resources/views/certificates/marriage-certificate.blade.php`
- **Format**: Official Kenya FORM MA1 (Marriage Act 2014)
- **Layout**: A4 Portrait format
- **Design**: Professional table-based layout with official headers

### 2. **Controller Methods**
The `MarriageRecordController` includes several methods for certificate generation:

#### Primary Methods:
- `generateCertificate(MarriageRecord $marriageRecord)` - Generate PDF from existing record
- `downloadMarriageCertificate($marriageRecordId)` - Download by record ID  
- `findMemberMarriageCertificate($memberId)` - Find and download member's certificate

#### Helper Methods:
- `enhanceMarriageRecordForCertificate()` - Maps database fields to template fields
- `createBasicMarriageRecordFromMember()` - Creates fallback record for married members

### 3. **Available Routes**

#### For Members:
```php
GET /members/{member}/marriage-certificate
```
**Purpose**: Download marriage certificate for a specific member
**Access**: Available through member detail page for married members

#### For Marriage Records:
```php
GET /marriage-records/{marriageRecord}/certificate
GET /marriage-records/download/{marriageRecordId}  
GET /marriage-records/member/{memberId}/certificate
```

## How to Use

### 1. **From Member Profile**
1. Navigate to a member's profile page
2. If member has `matrimony_status = 'married'`, a "Download Marriage Certificate" button appears
3. Click the button to automatically generate and download the certificate

### 2. **From Marriage Records**
1. Go to Marriage Records section
2. View any marriage record
3. Click "Generate Certificate" to download the PDF

### 3. **Direct URL Access**
```bash
# Replace {memberId} with actual member ID
https://yourparishsite.com/members/{memberId}/marriage-certificate

# Replace {marriageRecordId} with actual record ID  
https://yourparishsite.com/marriage-records/download/{marriageRecordId}
```

## Certificate Data Mapping

### Template Fields → Database Fields:

| Template Field | Database Source | Fallback |
|---|---|---|
| `certificate_number` | `certificate_number` | Auto-generated from ID |
| `marriage_location` | `marriage_church` | Parish name |
| `sub_county` | `district` | Empty |
| `county` | `province` | Empty |
| `marriage_date` | `marriage_date` | Current date |
| `entry_number` | `record_number` | Auto-generated |
| `husband_name` | `husband_name` | From member data |
| `wife_name` | `wife_name` | From member data |
| `husband_age` | Calculated from DOB | Empty |
| `wife_age` | Calculated from DOB | Empty |
| `witness1_name` | `male_witness_full_name` | Empty |
| `witness2_name` | `female_witness_full_name` | Empty |
| `officiant_name` | `presence_of` | "Parish Priest" |

## Automatic Fallback System

### When No Marriage Record Exists:
If a member is marked as `matrimony_status = 'married'` but has no marriage record:

1. System automatically creates a basic marriage record
2. Populates essential fields from member data  
3. Uses parish defaults for missing information
4. Generates certificate with available data

### Fallback Data Used:
- **Marriage Date**: Current date (since no record exists)
- **Location**: Member's local church or parish default
- **Officiant**: "Parish Priest"
- **District**: "Kandara" 
- **Province**: "Murang'a"

## Technical Implementation

### PDF Generation:
```php
// Uses DomPDF with A4 Portrait orientation
$pdf = Pdf::loadView('certificates.marriage-certificate', $data);
$pdf->setPaper('A4', 'portrait');
return $pdf->download($filename);
```

### Error Handling:
- Missing marriage records → Creates fallback record
- Invalid member IDs → Returns 404 error
- PDF generation errors → Returns 500 with error message

### File Naming:
```php
'marriage-certificate-{husband-name}-{wife-name}-{date}.pdf'
```

## Frontend Integration

### Member Profile Page:
- Marriage certificate download appears for married members
- Button links to `/members/{id}/marriage-certificate`
- Opens in new tab for immediate download

### Marriage Records Page:
- Certificate generation button on each record
- Direct access to PDF generation

## Database Requirements

### Essential Fields in `marriage_records` table:
- `husband_name`, `wife_name`
- `marriage_date`, `marriage_church`
- `husband_id`, `wife_id` (relationships)
- `district`, `province` (location)
- `presence_of` (officiant)

### Member Requirements:
- `matrimony_status = 'married'` for certificate eligibility
- Member relationships for enhanced data

## Security & Permissions

### Access Control:
- Must be authenticated user
- Requires `manage members` permission (configurable)
- No public access to certificates

### Data Privacy:
- Only generates certificates for legitimate marriage records
- Validates member access rights
- Secure PDF generation process

## Future Enhancements

### Planned Features:
1. **Batch Certificate Generation** - Multiple certificates at once
2. **Certificate Templates** - Different formats (civil, church, etc.)  
3. **Digital Signatures** - Electronic signing capability
4. **Certificate Registry** - Track issued certificates
5. **Email Delivery** - Send certificates directly to members

### Integration Opportunities:
1. **Government Systems** - Connect to civil registration
2. **Church Hierarchy** - Bishop approval workflows
3. **Archive System** - Long-term certificate storage
4. **Audit Trail** - Certificate generation logging

## Troubleshooting

### Common Issues:

#### 1. "No marriage record found"
**Cause**: Member not marked as married OR no marriage record exists
**Solution**: 
- Check member's `matrimony_status` 
- Create marriage record manually if needed

#### 2. "Failed to generate certificate"
**Cause**: Missing template file or PDF generation error
**Solution**:
- Verify template exists: `resources/views/certificates/marriage-certificate.blade.php`
- Check storage permissions for PDF generation
- Review error logs for specific issues

#### 3. "Permission denied" 
**Cause**: User lacks required permissions
**Solution**: Ensure user has `manage members` permission

### Debug Commands:
```bash
# Check married members count
php artisan tinker --execute="echo App\Models\Member::where('matrimony_status', 'married')->count();"

# Check marriage records count  
php artisan tinker --execute="echo App\Models\MarriageRecord::count();"

# Test certificate generation for specific member
php artisan tinker --execute="echo route('members.marriage-certificate', 1);"
```

## Configuration

### Parish Settings:
Update `config/app.php`:
```php
'parish_name' => 'Sacred Heart Kandara Parish',
'parish_phone' => '+254 XXX XXX XXX',
```

### PDF Settings:
Configure `config/dompdf.php` for optimal certificate generation:
- Paper size: A4
- Orientation: Portrait  
- Font: Times New Roman (serif)

---

## Contact & Support

For technical support or feature requests regarding the marriage certificate system:
- Review this documentation first
- Check system logs for errors
- Test with sample data before production use

**System Status**: ✅ Fully Implemented and Ready for Use
**Last Updated**: September 27, 2025