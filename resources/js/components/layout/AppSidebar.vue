<template>
    <div>
        <!-- Mobile Toggle Button -->
        <button class="sidebar-mobile-toggle d-md-none" @click="toggleSidebar" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- Sidebar Backdrop (Mobile) -->
        <Transition name="fade">
            <div v-if="isOpen" class="sidebar-backdrop d-md-none" @click="closeSidebar"></div>
        </Transition>

        <!-- Sidebar -->
        <div class="app-sidebar d-flex flex-column flex-shrink-0 text-white h-100"
             :class="{ 'show': isOpen }">

            <!-- Logo -->
            <div class="sidebar-header">
                <a href="/" class="sidebar-logo">
                    <img :src="empulseLogo" alt="Empulse" height="36">
                </a>
                <button class="sidebar-close d-md-none" @click="closeSidebar" aria-label="Close sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a :href="dashboardHref" class="nav-link" :class="{ active: dashboardActive }">
                            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                            <span class="nav-label">Dashboard</span>
                        </a>
                    </li>
                    <li v-if="canAccessCompanyReports" class="nav-item">
                        <a href="/reports" class="nav-link" :class="{ active: routeStartsWith('reports') }">
                            <span class="nav-icon"><i class="bi bi-graph-up"></i></span>
                            <span class="nav-label">Analytics & Reports</span>
                        </a>
                    </li>
                    <li v-if="canManageSurveys" class="nav-item">
                        <a href="/surveys/manage" class="nav-link" :class="{ active: currentRouteName === 'surveys.manage' }">
                            <span class="nav-icon"><i class="bi bi-ui-checks-grid"></i></span>
                            <span class="nav-label">Survey Management</span>
                        </a>
                    </li>
                    <li v-if="canManageWaves" class="nav-item">
                        <a href="/survey-waves" class="nav-link" :class="{ active: routeStartsWith('survey-waves.') }">
                            <span class="nav-icon"><i class="bi bi-calendar2-week"></i></span>
                            <span class="nav-label">Survey Waves</span>
                        </a>
                    </li>
                    <li v-if="canManageTeam" class="nav-item">
                        <a href="/team/manage" class="nav-link" :class="{ active: routeStartsWith('team.') }">
                            <span class="nav-icon"><i class="bi bi-people"></i></span>
                            <span class="nav-label">Team</span>
                        </a>
                    </li>
                    <li v-if="canAccessBilling" class="nav-item">
                        <a href="/account/billing" class="nav-link" :class="{ active: routeStartsWith('billing.') || routeStartsWith('plans.') }">
                            <span class="nav-icon"><i class="bi bi-credit-card"></i></span>
                            <span class="nav-label">Billing</span>
                        </a>
                    </li>
                    <li v-if="isWorkfitAdmin" class="nav-item">
                        <a href="/admin" class="nav-link" :class="{ active: routeStartsWith('admin') }">
                            <span class="nav-icon"><i class="bi bi-shield-lock"></i></span>
                            <span class="nav-label">Admin Panel</span>
                        </a>
                    </li>
                    <li v-if="isWorkfitAdmin" class="nav-item">
                        <a href="/admin/builder" class="nav-link" :class="{ active: routeStartsWith('admin.builder') }">
                            <span class="nav-icon"><i class="bi bi-ui-checks"></i></span>
                            <span class="nav-label">Survey Builder</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="sidebar-footer">
                <div class="dropdown">
                    <a href="#" class="sidebar-user dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
                        <img :src="userAvatar" alt="" width="34" height="34" class="sidebar-avatar">
                        <div class="sidebar-user-info">
                            <span class="sidebar-user-name">{{ userName }}</span>
                            <span class="sidebar-user-role">{{ roleName }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow-lg sidebar-dropdown" aria-labelledby="dropdownUser2">
                        <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="/contact"><i class="bi bi-chat-dots me-2"></i>Contact Us</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item sidebar-signout" href="#" @click.prevent="logout"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
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
const currentRouteName = computed(() => String(props.currentRoute ?? ''));
const isWorkfitAdmin = computed(() => Number(props.user?.is_admin ?? 0) === 1 || Number(props.user?.role ?? 0) === 0);
const isEmployee = computed(() => role.value === 4);
const isManager = computed(() => role.value === 1);
const hasCompanyContext = computed(() => Number(props.user?.company_id ?? 0) > 0);

const userName = computed(() => props.user?.name ?? '');
const userAvatar = computed(() => (props.user?.image ? `/upload/${props.user.image}` : '/upload/no_image.jpg'));
const roleName = computed(() => {
    if (isWorkfitAdmin.value) return 'Administrator';
    if (isManager.value) return 'Manager';
    if (isEmployee.value) return 'Employee';
    return 'Member';
});
const canAccessCompanyReports = computed(() => !isEmployee.value && hasCompanyContext.value);
const canManageTeam = computed(() => !isEmployee.value && hasCompanyContext.value);
const canManageSurveys = computed(() => isManager.value);
const canManageWaves = computed(() => isManager.value);
const canAccessBilling = computed(() => isManager.value);
const dashboardHref = computed(() => (isEmployee.value ? '/employee' : '/home'));
const dashboardActive = computed(() => {
    if (isEmployee.value) {
        return routeStartsWith('employee');
    }

    return currentRouteName.value === 'home' || currentRouteName.value === 'dashboard.analytics';
});
const isOpen = ref(false);

const routeStartsWith = (prefix) => currentRouteName.value.startsWith(prefix);

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
/* ── Sidebar Shell ── */
.app-sidebar {
    width: 272px;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 1040;
    background: linear-gradient(180deg, #0c1222 0%, #151d30 50%, #1a2540 100%);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 4px 0 32px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
}

/* ── Header / Logo ── */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1.25rem 1rem;
    flex-shrink: 0;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    opacity: 0.95;
    transition: opacity 0.2s;
}

.sidebar-logo:hover {
    opacity: 1;
}

.sidebar-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.125rem;
    padding: 0.25rem;
    cursor: pointer;
    border-radius: 0.375rem;
    transition: all 0.15s;
}

.sidebar-close:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}

/* ── Navigation ── */
.sidebar-nav {
    flex: 1 1 auto;
    padding: 0.5rem 0.75rem;
    overflow-y: auto;
}

.sidebar-nav .nav {
    gap: 2px;
}

.nav-link {
    color: rgba(148, 163, 184, 0.9);
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.625rem 0.875rem;
    border-radius: 0.625rem;
    transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
    display: flex;
    align-items: center;
    gap: 0;
    text-decoration: none;
    position: relative;
}

.nav-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    margin-right: 0.75rem;
    flex-shrink: 0;
    transition: all 0.2s;
    font-size: 1rem;
}

.nav-label {
    font-family: 'Outfit', 'DM Sans', sans-serif;
    font-weight: 500;
    letter-spacing: -0.01em;
}

.nav-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.06);
}

.nav-link:hover .nav-icon {
    background: rgba(255, 255, 255, 0.08);
    color: #a5b4fc;
}

.nav-link.active {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    color: #fff;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.nav-link.active .nav-icon {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

/* ── Footer / User ── */
.sidebar-footer {
    flex-shrink: 0;
    padding: 0.75rem;
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar-user {
    display: flex;
    align-items: center;
    color: #fff;
    text-decoration: none;
    padding: 0.625rem 0.75rem;
    border-radius: 0.75rem;
    transition: background 0.2s;
    gap: 0.75rem;
}

.sidebar-user::after {
    /* override Bootstrap dropdown caret */
    display: none;
}

.sidebar-user:hover {
    background: rgba(255, 255, 255, 0.06);
}

.sidebar-avatar {
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.15);
    object-fit: cover;
    flex-shrink: 0;
}

.sidebar-user-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.sidebar-user-name {
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    font-size: 0.8125rem;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: -0.01em;
}

.sidebar-user-role {
    font-size: 0.6875rem;
    color: rgba(148, 163, 184, 0.7);
    font-weight: 500;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

/* ── Dropdown ── */
.sidebar-dropdown {
    background: #1a2540;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 0.75rem !important;
    padding: 0.375rem !important;
    min-width: 200px;
    backdrop-filter: blur(16px);
}

.sidebar-dropdown .dropdown-item {
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    transition: all 0.15s;
}

.sidebar-dropdown .dropdown-item:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.sidebar-dropdown .dropdown-divider {
    border-color: rgba(255, 255, 255, 0.06);
    margin: 0.25rem 0;
}

.sidebar-signout {
    color: #f87171 !important;
}

.sidebar-signout:hover {
    background: rgba(248, 113, 113, 0.1) !important;
    color: #fca5a5 !important;
}

/* ── Mobile Toggle ── */
.sidebar-mobile-toggle {
    position: fixed;
    top: 0.875rem;
    left: 0.875rem;
    z-index: 1050;
    width: 42px;
    height: 42px;
    border-radius: 0.625rem;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0c1222;
    font-size: 1.25rem;
    cursor: pointer;
    transition: all 0.2s;
}

.sidebar-mobile-toggle:hover {
    background: #f8fafc;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* ── Backdrop ── */
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(12, 18, 34, 0.6);
    backdrop-filter: blur(6px);
    z-index: 1030;
}

/* ── Transitions ── */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ── Responsive ── */
@media (max-width: 767.98px) {
    .app-sidebar {
        transform: translateX(-100%);
    }

    .app-sidebar.show {
        transform: translateX(0);
    }
}

/* ── Scrollbar ── */
.app-sidebar::-webkit-scrollbar {
    width: 4px;
}

.app-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.app-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.app-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
