import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('./views/auth/LoginView.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/',
        name: 'Dashboard',
        component: () => import('./views/DashboardView.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/plans',
        name: 'DailyPlans',
        component: () => import('./views/plans/DailyPlansView.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/logs',
        name: 'ActivityLogs',
        component: () => import('./views/logs/ActivityLogsView.vue'),
        meta: { requiresAuth: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global authentication navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // If we have a token but no user object, fetch it
    if (authStore.token && !authStore.user) {
        await authStore.fetchUser();
    }
    
    const isAuthenticated = authStore.isAuthenticated;
    
    // Check if route requires auth but user isn't logged in
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'Login' });
    } 
    // Check if route is guest-only (like login) but user IS authenticated
    else if (to.meta.guestOnly && isAuthenticated) {
        next({ name: 'Dashboard' });
    } 
    // Otherwise allow navigation
    else {
        next();
    }
});

export default router;
