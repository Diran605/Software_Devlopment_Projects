<template>
    <div>
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Welcome back, {{ authStore.user?.name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Here's your productivity overview for today.</p>
            </div>
            <button @click="handleLogout" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:text-red-600 transition-colors shadow-sm">
                Log out
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Overview Cards -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col h-full">
                <h3 class="text-lg font-medium text-gray-900 mb-2 border-b border-gray-100 pb-2">Today's Plan</h3>
                <div class="grow flex flex-col items-center justify-center py-6">
                    <p class="text-sm text-gray-500 mb-4 text-center">Manage your task list for the day and set priorities.</p>
                    <router-link :to="{ name: 'DailyPlans' }" class="text-sm text-indigo-600 bg-indigo-50 hover:bg-indigo-100 font-medium px-4 py-2 rounded-lg transition-colors inline-block text-center w-full">
                        Manage Daily Plans &rarr;
                    </router-link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col h-full">
                <h3 class="text-lg font-medium text-gray-900 mb-2 border-b border-gray-100 pb-2">Recent Logs</h3>
                <div class="grow flex flex-col items-center justify-center py-6">
                    <p class="text-sm text-gray-500 mb-4 text-center">Track your time and register deliverables.</p>
                    <router-link :to="{ name: 'ActivityLogs' }" class="text-sm text-indigo-600 bg-indigo-50 hover:bg-indigo-100 font-medium px-4 py-2 rounded-lg transition-colors inline-block text-center w-full">
                        Manage Activity Logs &rarr;
                    </router-link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2 border-b border-gray-100 pb-2">Weekly Summary</h3>
                <div class="text-sm text-gray-500 flex items-center justify-center h-24 italic bg-gray-50 rounded-lg">
                    Calculating metrics...
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: 'Login' });
};
</script>
