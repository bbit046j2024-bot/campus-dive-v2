import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import DashboardLayout from './components/layout/DashboardLayout';

// Pages
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';
import ResetPasswordPage from './pages/auth/ResetPasswordPage';
import StudentDashboard from './pages/student/StudentDashboard';
import AdminDashboard from './pages/admin/AdminDashboard';
import StudentsPage from './pages/admin/StudentsPage';
import RolesPage from './pages/admin/RolesPage';
import AnalyticsPage from './pages/admin/AnalyticsPage';
import SocialGroupsAdminPage from './pages/admin/SocialGroupsAdminPage';
import MessagesPage from './pages/messages/MessagesPage';
import DocumentsPage from './pages/student/DocumentsPage';
import SettingsPage from './pages/student/SettingsPage';
import SocialLayout from './components/layout/SocialLayout';
import SocialFeedPage from './pages/social/SocialFeedPage';
import GroupsPage from './pages/social/GroupsPage';
import GroupProfilePage from './pages/social/GroupProfilePage';
import SinglePostPage from './pages/social/SinglePostPage';
import SocialProfilePage from './pages/social/SocialProfilePage';
import GroupManagerDashboard from './pages/social/GroupManagerDashboard';
import NotificationsPage from './pages/social/NotificationsPage';
import BroadcastPage from './pages/admin/BroadcastPage';

// Public Pages
import HomePage from './pages/public/HomePage';
import AboutPage from './pages/public/AboutPage';
import PublicLayout from './components/layout/PublicLayout';

// Helper for protected routes
function RouteGuard({ children, roles = [] }) {
  const { user, loading, isAdmin, isManager } = useAuth();

  if (loading) return (
    <div className="min-h-screen flex items-center justify-center bg-surface-50 dark:bg-surface-950">
      <div className="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin" />
    </div>
  );

  if (!user) return <Navigate to="/login" replace />;

  if (roles.length > 0) {
    const userRole = user.role || user.role_name;
    const hasRole = roles.some(r =>
      userRole === r ||
      (r === 'Admin' && isAdmin) ||
      (r === 'Manager' && isManager)
    );
    if (!hasRole) return <Navigate to={isAdmin || isManager ? "/admin" : "/dashboard"} replace />;
  }

  return children;
}

export default function App() {
  const { user, isAdmin, isManager } = useAuth();

  return (
    <Routes>
      {/* Public Pages Layout */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/about" element={<AboutPage />} />

        {/* Public Auth Routes */}
        <Route path="/login" element={!user ? <LoginPage /> : <Navigate to={isAdmin || isManager ? "/admin" : "/dashboard"} replace />} />
        <Route path="/register" element={!user ? <RegisterPage /> : <Navigate to={isAdmin || isManager ? "/admin" : "/dashboard"} replace />} />
        <Route path="/forgot-password" element={!user ? <ForgotPasswordPage /> : <Navigate to={isAdmin || isManager ? "/admin" : "/dashboard"} replace />} />
        <Route path="/reset-password" element={!user ? <ResetPasswordPage /> : <Navigate to={isAdmin || isManager ? "/admin" : "/dashboard"} replace />} />
      </Route>

      {/* Protected Routes */}
      <Route element={<DashboardLayout />}>
        {/* Student Routes */}
        <Route path="/dashboard" element={
          <RouteGuard roles={['user', 'Student']}>
            <StudentDashboard />
          </RouteGuard>
        } />
        <Route path="/documents" element={
          <RouteGuard roles={['user', 'Student']}>
            <DocumentsPage />
          </RouteGuard>
        } />

        {/* Admin/Manager Routes */}
        <Route path="/admin" element={
          <RouteGuard roles={['admin', 'Admin', 'manager', 'Manager']}>
            <AdminDashboard />
          </RouteGuard>
        } />
        <Route path="/admin/students" element={
          <RouteGuard roles={['admin', 'Admin', 'manager', 'Manager', 'interviewer', 'Interviewer']}>
            <StudentsPage />
          </RouteGuard>
        } />
        <Route path="/admin/roles" element={
          <RouteGuard roles={['admin', 'Admin']}>
            <RolesPage />
          </RouteGuard>
        } />
        <Route path="/admin/analytics" element={
          <RouteGuard roles={['admin', 'Admin', 'manager', 'Manager']}>
            <AnalyticsPage />
          </RouteGuard>
        } />
        <Route path="/admin/social" element={
          <RouteGuard roles={['admin', 'Admin']}>
            <SocialGroupsAdminPage />
          </RouteGuard>
        } />
        <Route path="/admin/broadcast" element={
          <RouteGuard roles={['admin', 'Admin', 'manager', 'Manager', 'interviewer', 'Interviewer']}>
            <BroadcastPage />
          </RouteGuard>
        } />

        {/* Shared Protected Routes */}
        <Route path="/messages" element={
          <RouteGuard>
            <MessagesPage />
          </RouteGuard>
        } />
        <Route path="/settings" element={
          <RouteGuard>
            <SettingsPage />
          </RouteGuard>
        } />
        <Route path="/notifications" element={
          <RouteGuard>
            <NotificationsPage />
          </RouteGuard>
        } />

      </Route>

      {/* Social Hub Routes */}
      <Route path="/social" element={<SocialLayout />}>
        <Route index element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <SocialFeedPage />
          </RouteGuard>
        } />
        <Route path="groups" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <GroupsPage />
          </RouteGuard>
        } />
        <Route path="groups/:slug" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <GroupProfilePage />
          </RouteGuard>
        } />
        <Route path="manager/:slug" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <GroupManagerDashboard />
          </RouteGuard>
        } />
        <Route path="posts/:id" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <SinglePostPage />
          </RouteGuard>
        } />
        <Route path="profile" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <SocialProfilePage />
          </RouteGuard>
        } />
        <Route path="profile/:id" element={
          <RouteGuard roles={['user', 'Student', 'admin', 'Admin', 'manager', 'Manager']}>
            <SocialProfilePage />
          </RouteGuard>
        } />
      </Route>

      {/* 404 Redirect */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
