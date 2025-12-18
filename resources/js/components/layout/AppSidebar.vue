<template>
    <div>
        <!-- Mobile Toggle Button -->
        <button class="btn btn-light d-md-none position-fixed top-0 start-0 m-3 shadow-sm z-3" @click="toggleSidebar" style="z-index: 1050;">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Sidebar Backdrop (Mobile) -->
        <div v-if="isOpen" class="sidebar-backdrop d-md-none" @click="closeSidebar"></div>

        <!-- Sidebar -->
        <div class="app-sidebar d-flex flex-column flex-shrink-0 p-4 text-white h-100" 
             :class="{ 'show': isOpen }">
            
            <!-- Logo -->
            <div class="d-flex align-items-center mb-4 px-2">
                <a href="/" class="d-flex align-items-center text-white text-decoration-none">
                    <img :src="empulseLogo" alt="Empulse" height="40">
                </a>
                <button class="btn btn-sm btn-link text-white-50 d-md-none ms-auto" @click="closeSidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="nav-spacer mb-4"></div>

            <!-- Navigation -->
            <ul class="nav nav-pills flex-column mb-auto gap-2">
                <li class="nav-item">
                    <a :href="dashboardHref" class="nav-link" :class="{ active: isEmployee ? currentRoute.startsWith('employee') : currentRoute === 'home' }">
                        <i class="bi bi-speedometer2 me-3"></i>
                        Dashboard
                    </a>
                </li>
                <li v-if="!isEmployee">
                    <a href="/reports" class="nav-link" :class="{ active: currentRoute.startsWith('reports') }">
                        <i class="bi bi-graph-up me-3"></i>
                        Analytics & Reports
                    </a>
                </li>
                <li v-if="isAdmin">
                    <a href="/admin" class="nav-link" :class="{ active: currentRoute.startsWith('admin') }">
                        <i class="bi bi-shield-lock me-3"></i>
                        Admin Panel
                    </a>
                </li>
                <li v-if="isManager">
                    <a href="/admin/builder" class="nav-link" :class="{ active: currentRoute.startsWith('admin.builder') }">
                        <i class="bi bi-ui-checks me-3"></i>
                        Survey Builder
                    </a>
                </li>
                <li v-if="!isEmployee">
                    <a href="/team/manage" class="nav-link" :class="{ active: currentRoute.startsWith('team.') }">
                        <i class="bi bi-people me-3"></i>
                        Team
                    </a>
                </li>
            </ul>

            <!-- User Profile -->
            <div class="mt-auto pt-4 border-top border-white-10">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle p-2 rounded hover-glass" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
                        <img :src="userAvatar" alt="" width="32" height="32" class="rounded-circle me-3 border border-2 border-white-20">
                        <div class="d-flex flex-column">
                            <strong class="fs-sm">{{ userName }}</strong>
                            <span class="text-white-50 fs-xs">View Profile</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow-lg border-0 glass-dark" aria-labelledby="dropdownUser2">
                        <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider border-white-10"></li>
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="logout"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import empulseLogo from '@assets/empulse-logo.png';

const props = defineProps({
    user: { type: Object, required: true },
    currentRoute: { type: String, default: '' }
});

const role = computed(() => Number(props.user?.role ?? 0));
const userName = computed(() => props.user?.name ?? '');
const userAvatar = computed(() => (props.user?.image ? `/upload/${props.user.image}` : '/upload/no_image.jpg'));
const isAdmin = computed(() => role.value === 0);
const isManager = computed(() => role.value === 1 || role.value === 0);
const isEmployee = computed(() => role.value === 4);
const dashboardHref = computed(() => (isEmployee.value ? '/employee' : '/home'));
const isOpen = ref(false);

const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
};

const closeSidebar = () => {
    isOpen.value = false;
};

const logout = () => {
    document.getElementById('logout-form').submit();
};
</script>

<style scoped>
.app-sidebar {
    width: 280px;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 1040;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);
}

.nav-link {
    color: #94a3b8;
    font-weight: 500;
    padding: 0.875rem 1rem;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.05);
    transform: translateX(4px);
}

.nav-link.active {
    background-color: #4f46e5;
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.nav-link i {
    font-size: 1.1rem;
    transition: transform 0.2s;
}

.nav-link:hover i {
    transform: scale(1.1);
}

.border-white-10 {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.border-white-20 {
    border-color: rgba(255, 255, 255, 0.2) !important;
}

.hover-glass:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.fs-sm { font-size: 0.875rem; }
.fs-xs { font-size: 0.75rem; }
.tracking-tight { letter-spacing: -0.025em; }

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1030;
}

@media (max-width: 767.98px) {
    .app-sidebar {
        transform: translateX(-100%);
    }
    
    .app-sidebar.show {
        transform: translateX(0);
    }
}
</style>
