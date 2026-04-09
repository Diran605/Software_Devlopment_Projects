<template>
    <div class="min-h-screen bg-gray-50 selection:bg-indigo-500 selection:text-white">
        <!-- Only show top navigation if authenticated -->
        <header v-if="authStore.isAuthenticated" class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <!-- Brand icon -->
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
                        W
                    </div>
                    <span class="text-xl font-bold text-gray-900 tracking-tight">WorkTrack <span class="text-indigo-600">Pro</span></span>
                </div>
                
                <nav class="hidden md:flex space-x-8">
                    <router-link :to="{ name: 'Dashboard' }" active-class="border-indigo-500 text-gray-900" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </router-link>
                    <router-link :to="{ name: 'DailyPlans' }" active-class="border-indigo-500 text-gray-900" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Daily Plans
                    </router-link>
                    <router-link :to="{ name: 'ActivityLogs' }" active-class="border-indigo-500 text-gray-900" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Activity Logs
                    </router-link>
                </nav>
            </div>
        </header>

        <!-- Dynamic router content injects here based on URL -->
        <main :class="authStore.isAuthenticated ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8' : ''">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();
</script>
