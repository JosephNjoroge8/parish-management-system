// Add this to your browser console to test permissions and routes

async function debugExportSystem() {
    console.log('🔍 DEBUGGING PARISH EXPORT SYSTEM');
    console.log('=================================');
    
    // 1. Check user permissions
    console.log('\n1. User Permissions Check:');
    const userElement = document.querySelector('[data-page]');
    if (userElement) {
        try {
            const pageData = JSON.parse(userElement.getAttribute('data-page'));
            const user = pageData.props?.auth?.user;
            console.log('User:', user?.name || 'Unknown');
            console.log('Permissions:', user?.permissions || 'None found');
            console.log('Can export reports:', user?.permissions?.can_export_reports || 'Not set');
        } catch (e) {
            console.log('Could not parse user data:', e.message);
        }
    }
    
    // 2. Check CSRF token
    console.log('\n2. CSRF Token Check:');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('CSRF Token:', csrfToken ? '✅ Found' : '❌ Missing');
    
    // 3. Test basic route accessibility
    console.log('\n3. Route Accessibility Test:');
    const testRoutes = [
        '/reports',
        '/reports/export-by-gender?value=Male&format=excel'
    ];
    
    for (const route of testRoutes) {
        try {
            const response = await fetch(route, {
                method: 'HEAD', // Just check if route exists
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                }
            });
            console.log(`${route}: ${response.status} ${response.statusText}`);
        } catch (error) {
            console.log(`${route}: ❌ ${error.message}`);
        }
    }
    
    // 4. Test actual export request
    console.log('\n4. Export Request Test:');
    try {
        const response = await fetch('/reports/export-by-gender?value=Male&format=excel', {
            method: 'GET',
            headers: {
                'Accept': 'application/octet-stream',
                'X-CSRF-TOKEN': csrfToken || '',
            }
        });
        
        console.log(`Status: ${response.status} ${response.statusText}`);
        console.log('Headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            const errorText = await response.text();
            console.log('Error response preview:', errorText.substring(0, 500) + '...');
        } else {
            const blob = await response.blob();
            console.log(`✅ Success! File size: ${blob.size} bytes`);
        }
    } catch (error) {
        console.log('❌ Request failed:', error.message);
    }
    
    console.log('\n🏁 Debug complete!');
}

// Run the debug function
debugExportSystem();
