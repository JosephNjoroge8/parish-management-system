// Code Splitting Recommendations for Parish System

// 1. Split by routes (lazy loading)
const AdminUsersIndex = React.lazy(() => import('./Pages/Admin/Users/Index'));
const AdminDashboard = React.lazy(() => import('./Pages/Admin/Dashboard'));

// 2. Split large components
const DataTable = React.lazy(() => import('./Components/DataTable'));
const RichTextEditor = React.lazy(() => import('./Components/RichTextEditor'));

// 3. Split vendor libraries
// Configure in vite.config.js manualChunks as shown above

// 4. Use Suspense for lazy-loaded components
function App() {
    return (
        <Suspense fallback={<div>Loading...</div>}>
            <Routes>
                <Route path="/admin/users" element={<AdminUsersIndex />} />
                <Route path="/admin" element={<AdminDashboard />} />
            </Routes>
        </Suspense>
    );
}