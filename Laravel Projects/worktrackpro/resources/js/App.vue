<template>
    <div class="h-screen w-full flex bg-gray-50 selection:bg-teal-500 selection:text-white overflow-hidden">
        
        <!-- Sidebar Navigation -->
        <aside v-if="authStore.isAuthenticated" class="w-64 shrink-0 bg-white border-r border-gray-200 flex-col h-full shadow-sm relative z-20 transition-all duration-300 hidden md:flex">
            <!-- Brand icon -->
            <div class="h-16 flex items-center px-6 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 bg-teal-600 rounded-lg flex items-center justify-center text-white font-bold shadow-sm mr-3">
                    W
                </div>
                <span class="text-xl font-bold text-gray-900 tracking-tight">WorkTrack <span class="text-teal-600">Pro</span></span>
            </div>
            
            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 px-3">Main Menu</div>
                
                <router-link :to="{ name: 'Dashboard' }" active-class="bg-teal-50 text-teal-700 font-semibold" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all">
                    <svg class="w-5 h-5 mr-3 shrink-0 transition-colors" :class="$route.name === 'Dashboard' ? 'text-teal-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </router-link>

                <router-link :to="{ name: 'DailyPlans' }" active-class="bg-teal-50 text-teal-700 font-semibold" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all">
                    <svg class="w-5 h-5 mr-3 shrink-0 transition-colors" :class="$route.name === 'DailyPlans' ? 'text-teal-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5v2m6-2v2"/>
                    </svg>
                    Daily Plans
                </router-link>

                <router-link :to="{ name: 'ActivityLogs' }" active-class="bg-teal-50 text-teal-700 font-semibold" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all">
                    <svg class="w-5 h-5 mr-3 shrink-0 transition-colors" :class="$route.name === 'ActivityLogs' ? 'text-teal-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Activity Logs
                </router-link>

                <div v-if="isAdmin">
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-6 mb-4 px-3">Management</div>

                    <router-link :to="{ name: 'Team' }" active-class="bg-blue-50 text-blue-700 font-semibold" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all">
                        <svg class="w-5 h-5 mr-3 shrink-0 transition-colors" :class="$route.name === 'Team' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Team Directory
                    </router-link>

                    <!-- Admin Command Center Link (External to SPA) -->
                    <a href="/admin" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all mt-1">
                        <svg class="w-5 h-5 mr-3 shrink-0 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Command Center
                    </a>
                </div>
            </div>

            <!-- Bottom User Profile Area -->
            <div class="shrink-0 p-4 border-t border-gray-100 flex items-center group">
                <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold border border-teal-200">
                    {{ authStore.user?.name?.charAt(0) || 'U' }}
                </div>
                <div class="ml-3 flex-1 overflow-hidden">
                    <p class="text-sm font-bold text-gray-900 truncate">{{ authStore.user?.name || 'User' }}</p>
                    <p class="text-xs text-gray-500 truncate capitalize">{{ authStore.user?.roles?.[0]?.name?.replace('_', ' ') || 'Worker' }}</p>
                </div>
                <button @click="handleLogout" class="ml-2 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Log out">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Mobile Header (Visible only on small screens) -->
        <header v-if="authStore.isAuthenticated" class="md:hidden bg-white border-b border-gray-200 w-full h-16 flex items-center justify-between px-4 fixed top-0 z-30">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-teal-600 rounded-lg flex items-center justify-center text-white font-bold shadow-sm mr-3">W</div>
                <span class="text-lg font-bold text-gray-900 tracking-tight">WorkTrack</span>
            </div>
            <!-- Standard top menu for mobile just wrapping around -->
             <nav class="flex items-center space-x-4">
                <router-link :to="{ name: 'Dashboard' }" class="text-gray-500 hover:text-teal-600 font-medium">Dash</router-link>
                <router-link :to="{ name: 'DailyPlans' }" class="text-gray-500 hover:text-teal-600 font-medium">Plans</router-link>
                <router-link :to="{ name: 'ActivityLogs' }" class="text-gray-500 hover:text-teal-600 font-medium">Logs</router-link>
                <router-link v-if="isAdmin" :to="{ name: 'Team' }" class="text-gray-500 hover:text-blue-600 font-medium">Team</router-link>
                <button @click="handleLogout" class="text-gray-400 hover:text-red-500 transition-colors ml-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
             </nav>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto w-full h-full bg-gray-50 relative" :class="authStore.isAuthenticated ? 'pt-16 md:pt-0' : ''">
            <div class="h-full w-full max-w-7xl mx-auto p-4 md:p-8">
                <router-view />
            </div>
        </main>

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from './stores/auth';
import { useRoute, useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const isAdmin = computed(() => {
    const roles = authStore.user?.roles || [];
    return roles.includes('super_admin') || roles.includes('admin') || 
           (Array.isArray(roles) && roles.some(r => r.name === 'super_admin' || r.name === 'admin' || r === 'super_admin' || r === 'admin'));
});

const handleLogout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>
