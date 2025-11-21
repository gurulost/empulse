<template>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Team Management</h1>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'members' }" href="#" @click.prevent="activeTab = 'members'">
                    <i class="bi bi-people me-2"></i>Team Members
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'departments' }" href="#" @click.prevent="activeTab = 'departments'">
                    <i class="bi bi-building me-2"></i>Departments
                </a>
            </li>
        </ul>

        <Transition name="fade" mode="out-in">
            <TeamMemberTable
                v-if="activeTab === 'members'"
                key="members"
                :user-role="userRole"
                :departments="departments"
                @refresh-departments="loadDepartments"
            />
            <DepartmentManager
                v-else
                key="departments"
                :departments="departments"
                @refresh="loadDepartments"
            />
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTeamApi } from '../../composables/useTeamApi';
import TeamMemberTable from './TeamMemberTable.vue';
import DepartmentManager from './DepartmentManager.vue';

const props = defineProps({
    userRole: { type: Number, required: true }
});

const activeTab = ref('members');
const departments = ref([]);
const api = useTeamApi();

const loadDepartments = async () => {
    try {
        const response = await api.getDepartments();
        const list = response.data || response;
        departments.value = list.map(d => ({ title: d.title }));
    } catch (error) {
        console.error('Failed to load departments:', error);
        departments.value = [];
    }
};

onMounted(() => {
    loadDepartments();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.nav-link {
    color: #64748b;
    font-weight: 500;
    transition: all 0.2s;
}

.nav-link:hover {
    color: #4f46e5;
}

.nav-link.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}
</style>
