<template>
    <div>
        <!-- Mobile Toggle Button -->
        <button class="btn btn-light d-md-none position-fixed top-0 start-0 m-3 shadow-sm z-3" @click="toggleSidebar" style="z-index: 1050;">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Sidebar Backdrop (Mobile) -->
        <div v-if="isOpen" class="sidebar-backdrop d-md-none" @click="closeSidebar"></div>

        <!-- Sidebar -->
        <div class="app-sidebar d-flex flex-column flex-shrink-0 p-3 bg-white shadow-sm h-100" 
             :class="{ 'show': isOpen }">
            <div class="d-flex align-items-center justify-content-between mb-3 mb-md-0 me-md-auto">
                <a href="/" class="d-flex align-items-center link-dark text-decoration-none">
                    <img src="/materials/images/workfitdxr_logo_1.png" alt="Logo" height="32" class="me-2">
                    <span class="fs-5 fw-bold text-primary">Empulse</span>
                </a>
                <button class="btn btn-sm btn-light d-md-none" @click="closeSidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="/home" class="nav-link" :class="{ active: currentRoute === 'home' }">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/reports" class="nav-link" :class="{ active: currentRoute.startsWith('reports') }">
                        <i class="bi bi-graph-up me-2"></i>
                        Analytics & Reports
                    </a>
                </li>
                <li v-if="isAdmin">
                    <a href="/admin" class="nav-link" :class="{ active: currentRoute.startsWith('admin') }">
                        <i class="bi bi-shield-lock me-2"></i>
                        Admin Panel
                    </a>
                </li>
                <li v-if="isManager">
                    <a href="/builder" class="nav-link" :class="{ active: currentRoute.startsWith('builder') }">
                        <i class="bi bi-ui-checks me-2"></i>
                        Survey Builder
                    </a>
                </li>
                <li>
                    <a href="/users" class="nav-link" :class="{ active: currentRoute === 'users' }">
                        <i class="bi bi-people me-2"></i>
                        Team
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
                    <img :src="userAvatar" alt="" width="32" height="32" class="rounded-circle me-2">
                    <strong>{{ userName }}</strong>
                </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser2">
                    <li><a class="dropdown-item" href="/profile">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" @click.prevent="logout">Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    currentRoute: { type: String, default: '' }
});

const userName = ref(props.user.name);
const userAvatar = ref(props.user.image ? `/upload/${props.user.image}` : '/upload/no_image.jpg');
const isAdmin = ref(props.user.role === 0);
const isManager = ref(props.user.role === 1 || props.user.role === 0);
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
.nav-link {
    color: #64748b;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.nav-link:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.nav-link.active {
    background-color: #4f46e5;
    color: white;
}

.app-sidebar {
    width: 280px;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 1040;
    transition: transform 0.3s ease-in-out;
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
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
