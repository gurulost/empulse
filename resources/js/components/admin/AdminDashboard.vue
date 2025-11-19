<template>
    <div class="container py-4">
        <h1 class="h3 mb-4">Super Admin Dashboard</h1>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'companies' }" href="#" @click.prevent="activeTab = 'companies'">Companies</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'users' }" href="#" @click.prevent="activeTab = 'users'">Users</a>
            </li>
        </ul>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div v-else-if="activeTab === 'companies'">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Companies</h5>
                    <button class="btn btn-sm btn-primary" @click="fetchCompanies(companies.current_page)">Refresh</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Manager</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="company in companies.data" :key="company.id">
                            <td>{{ company.id }}</td>
                            <td>{{ company.title }}</td>
                            <td>{{ company.manager }}</td>
                            <td>{{ company.manager_email }}</td>
                            <td>
                                <span class="badge" :class="company.tariff === 1 ? 'bg-success' : 'bg-secondary'">
                                    {{ company.tariff === 1 ? 'Premium' : 'Free' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" @click="viewCompany(company)">View Users</button>
                            </td>
                        </tr>
                        <tr v-if="companies.data.length === 0">
                            <td colspan="6" class="text-center text-muted py-3">No companies found.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center" v-if="companies.last_page > 1">
                    <button class="btn btn-sm btn-outline-secondary" :disabled="companies.current_page === 1" @click="fetchCompanies(companies.current_page - 1)">Previous</button>
                    <span class="small text-muted">Page {{ companies.current_page }} of {{ companies.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary" :disabled="companies.current_page === companies.last_page" @click="fetchCompanies(companies.current_page + 1)">Next</button>
                </div>
            </div>
        </div>

        <div v-else-if="activeTab === 'users'">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        All Users
                        <span v-if="companyFilter" class="badge bg-info ms-2">
                            Company ID: {{ companyFilter }}
                            <button type="button" class="btn-close btn-close-white ms-1" aria-label="Close" @click="clearCompanyFilter" style="font-size: 0.5em;"></button>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Search users..." v-model="searchQuery" @keyup.enter="fetchUsers(1)">
                        <button class="btn btn-outline-secondary" @click="fetchUsers(1)">Search</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Company</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="user in users.data" :key="user.id">
                                <td>{{ user.id }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ getRoleLabel(user.role) }}</td>
                                <td>{{ user.company_id }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" @click="impersonateUser(user.id)" title="Login as this user">Login</button>
                                        <button class="btn btn-outline-danger" @click="deleteUser(user.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="text-center text-muted py-3">No users found.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3" v-if="users.last_page > 1">
                        <button class="btn btn-sm btn-outline-secondary" :disabled="users.current_page === 1" @click="fetchUsers(users.current_page - 1)">Previous</button>
                        <span class="small text-muted">Page {{ users.current_page }} of {{ users.last_page }}</span>
                        <button class="btn btn-sm btn-outline-secondary" :disabled="users.current_page === users.last_page" @click="fetchUsers(users.current_page + 1)">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const activeTab = ref('companies');
const companies = ref({ data: [], current_page: 1, last_page: 1 });
const users = ref({ data: [], current_page: 1, last_page: 1 });
const loading = ref(false);
const searchQuery = ref('');
const companyFilter = ref(null);

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

const getRoleLabel = (role) => {
    const roles = { 0: 'Super Admin', 1: 'Manager', 3: 'Team Lead', 4: 'Employee' };
    return roles[role] ?? 'Unknown';
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

onMounted(() => {
    fetchCompanies();
    fetchUsers();
});
</script>
