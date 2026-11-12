import { Navigate, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './layout/AdminLayout';
import { CategoriesPage } from './pages/CategoriesPage';
import { CoreUpdatePage } from './pages/CoreUpdatePage';
import { DashboardPage } from './pages/DashboardPage';
import { LoginPage } from './pages/LoginPage';
import { ModulesPage } from './pages/ModulesPage';
import { OperationsPage } from './pages/OperationsPage';
import { PagesPage } from './pages/PagesPage';
import { PostEditorPage } from './pages/PostEditorPage';
import { PostHistoryPage } from './pages/PostHistoryPage';
import { PostsPage } from './pages/PostsPage';
import { RolesPage } from './pages/RolesPage';
import { SettingsPage } from './pages/SettingsPage';
import { SitesPage } from './pages/SitesPage';
import { UsersPage } from './pages/UsersPage';
import { BlogBindingPage } from './pages/BlogBindingPage';

export function App(): React.JSX.Element {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<AdminLayout />}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="posts" element={<PostsPage />} />
        <Route path="posts/new" element={<PostEditorPage />} />
        <Route path="posts/:postId/history" element={<PostHistoryPage />} />
        <Route path="posts/:postId" element={<PostEditorPage />} />
        <Route path="categories" element={<CategoriesPage />} />
        <Route path="pages" element={<PagesPage />} />
        <Route path="users" element={<UsersPage />} />
        <Route path="modules" element={<ModulesPage />} />
        <Route path="modules/blog/binding" element={<BlogBindingPage />} />
        <Route path="modules/blog/unbind" element={<BlogBindingPage />} />
        <Route path="modules/blog/unbind-ready" element={<BlogBindingPage />} />
        <Route path="modules/blog/conflict" element={<BlogBindingPage />} />
        <Route path="modules/blog/address-change" element={<BlogBindingPage />} />
        <Route path="modules/blog/settings" element={<BlogBindingPage />} />
        <Route path="sites" element={<SitesPage />} />
        <Route path="settings" element={<SettingsPage />} />
        <Route path="core-update" element={<CoreUpdatePage />} />
        <Route path="operations" element={<OperationsPage />} />
        <Route path="roles" element={<RolesPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}
