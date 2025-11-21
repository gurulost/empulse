<template>
    <div class="admin-layout d-flex min-vh-100">
        <!-- Sidebar -->
        <aside :class="['sidebar bg-white border-end', { 'collapsed': isSidebarCollapsed }]">
            <div class="sidebar-header d-flex align-items-center justify-content-center py-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
                    <span class="fw-bold fs-5 text-dark" v-if="!isSidebarCollapsed">Empulse</span>
                </div>
            </div>

            <nav class="p-3">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a href="#" 
                           class="nav-link d-flex align-items-center gap-3 rounded-3"
                           :class="{ 'active': activeTab === 'companies', 'text-primary bg-primary-subtle': activeTab === 'companies', 'text-secondary': activeTab !== 'companies' }"
                           @click.prevent="activeTab = 'companies'">
                            <i class="bi bi-building fs-5"></i>
                            <span v-if="!isSidebarCollapsed">Companies</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" 
                           class="nav-link d-flex align-items-center gap-3 rounded-3"
                           :class="{ 'active': activeTab === 'users', 'text-primary bg-primary-subtle': activeTab === 'users', 'text-secondary': activeTab !== 'users' }"
                           @click.prevent="activeTab = 'users'">
                            <i class="bi bi-people fs-5"></i>
                            <span v-if="!isSidebarCollapsed">Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" 
                           class="nav-link d-flex align-items-center gap-3 rounded-3"
                           :class="{ 'active': activeTab === 'subscriptions', 'text-primary bg-primary-subtle': activeTab === 'subscriptions', 'text-secondary': activeTab !== 'subscriptions' }"
                           @click.prevent="activeTab = 'subscriptions'">
                            <i class="bi bi-credit-card fs-5"></i>
                            <span v-if="!isSidebarCollapsed">Subscriptions</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <div class="text-uppercase small text-muted fw-bold px-3 mb-2" v-if="!isSidebarCollapsed">System</div>
                        <a href="/home" class="nav-link d-flex align-items-center gap-3 rounded-3 text-secondary">
                            <i class="bi bi-box-arrow-left fs-5"></i>
                            <span v-if="!isSidebarCollapsed">Back to App</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-grow-1 d-flex flex-column bg-light">
            <!-- Topbar -->
            <header class="bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center sticky-top shadow-sm">
                <button class="btn btn-link text-secondary p-0" @click="isSidebarCollapsed = !isSidebarCollapsed">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-dark" data-bs-toggle="dropdown">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold">
                                {{ userInitials }}
                            </div>
                            <span class="d-none d-md-block fw-medium">{{ user?.name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 mt-2">
                            <li><h6 class="dropdown-header">Signed in as <br><strong>{{ user?.email }}</strong></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="/logout" method="POST">
                                    <input type="hidden" name="_token" :value="csrfToken">
                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h4 fw-bold text-dark mb-0">{{ pageTitle }}</h2>
                        <div>
                            <button class="btn btn-primary d-flex align-items-center gap-2 rounded-pill shadow-sm" @click="refreshCurrentTab">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div v-else>
                        <company-list 
                            v-if="activeTab === 'companies'" 
                            :companies="companies" 
                            @view-company="viewCompany" 
                            @page-change="fetchCompanies"
                        />

                        <user-list 
                            v-else-if="activeTab === 'users'" 
                            :users="users" 
                            v-model:searchQuery="searchQuery"
                            :companyFilter="companyFilter"
                            @search="fetchUsers(1)"
                            @clear-filter="clearCompanyFilter"
                            @page-change="fetchUsers"
                            @impersonate="impersonateUser"
                            @delete="deleteUser"
                        />

                        <subscription-list 
                            v-else-if="activeTab === 'subscriptions'" 
                            :subscriptions="subscriptions" 
                            @page-change="fetchSubscriptions"
                        />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import CompanyList from './CompanyList.vue';
import UserList from './UserList.vue';
import SubscriptionList from './SubscriptionList.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const activeTab = ref('companies');
const companies = ref({ data: [], current_page: 1, last_page: 1 });
const users = ref({ data: [], current_page: 1, last_page: 1 });
const subscriptions = ref({ data: [], current_page: 1, last_page: 1 });
const loading = ref(false);
const searchQuery = ref('');
const companyFilter = ref(null);
const isSidebarCollapsed = ref(false);
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const userInitials = computed(() => {
    if (!props.user || !props.user.name) return 'U';
    return props.user.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
});

const pageTitle = computed(() => {
    const titles = {
        companies: 'Company Management',
        users: 'User Management',
        subscriptions: 'Subscription Management'
    };
    return titles[activeTab.value] ?? 'Dashboard';
});

const fetchCompanies = async (page = 1) => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/admin/api/companies?page=${page}`);
        companies.value = data;
    } catch (e) {
        console.error(e);
        alert('Failed to load companies');
    } finally {
        loading.value = false;
    }
};

const fetchUsers = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, search: searchQuery.value };
        if (companyFilter.value) {
            params.company_id = companyFilter.value;
        }
        const { data } = await axios.get('/admin/api/users', { params });
        users.value = data;
    } catch (e) {
        console.error(e);
        alert('Failed to load users');
    } finally {
        loading.value = false;
    }
};

const fetchSubscriptions = async (page = 1) => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/admin/subscription/list?page=${page}`);
        subscriptions.value = data;
    } catch (e) {
        console.error(e);
        alert('Failed to load subscriptions');
    } finally {
        loading.value = false;
    }
};

const deleteUser = async (id) => {
    if (!confirm('Are you sure you want to delete this user?')) return;
    try {
        await axios.delete(`/admin/api/users/${id}`);
        fetchUsers(users.value.current_page);
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Failed to delete user');
    }
};

const impersonateUser = async (id) => {
    if (!confirm('Login as this user?')) return;
    try {
        const { data } = await axios.post(`/admin/api/users/${id}/impersonate`);
        window.location.href = data.redirect;
    } catch (e) {
        console.error(e);
        alert('Failed to impersonate user');
    }
};

const viewCompany = (company) => {
    companyFilter.value = company.id;
    searchQuery.value = ''; // Clear search to see all company users
    activeTab.value = 'users';
    fetchUsers(1);
};

const clearCompanyFilter = () => {
    companyFilter.value = null;
    fetchUsers(1);
};

const refreshCurrentTab = () => {
    if (activeTab.value === 'companies') fetchCompanies(companies.value.current_page);
    else if (activeTab.value === 'users') fetchUsers(users.value.current_page);
    else if (activeTab.value === 'subscriptions') fetchSubscriptions(subscriptions.value.current_page);
};

watch(activeTab, (newTab) => {
    if (newTab === 'companies' && companies.value.data.length === 0) fetchCompanies();
    if (newTab === 'users' && users.value.data.length === 0) fetchUsers();
    if (newTab === 'subscriptions' && subscriptions.value.data.length === 0) fetchSubscriptions();
});

onMounted(() => {
    fetchCompanies();
});
</script>

<style scoped>
.sidebar {
    width: 260px;
    transition: all 0.3s ease;
}
.sidebar.collapsed {
    width: 80px;
}
.avatar {
    width: 40px;
    height: 40px;
}
.nav-link {
    transition: all 0.2s;
}
.nav-link:hover {
    background-color: #f8f9fa;
}
.nav-link.active {
    background-color: #e0e7ff; /* Light indigo */
    color: #4f46e5; /* Indigo 600 */
}
</style>
