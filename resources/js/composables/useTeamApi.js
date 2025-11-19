import axios from 'axios';

export function useTeamApi() {
    const getTeamMembers = async (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `/users/list?${queryString}` : '/users/list';
        const { data } = await axios.get(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        return data;
    };

    const addTeamMember = async (memberData) => {
        const { data } = await axios.post('/users', memberData);
        return data;
    };

    const updateTeamMember = async (email, memberData) => {
        const { data } = await axios.put(`/users/${email}`, memberData);
        return data;
    };

    const deleteTeamMember = async (email) => {
        const { data } = await axios.get(`/users/delete/${email}`);
        return data;
    };

    const changeRole = async (email, roleType) => {
        const endpoints = {
            manager: `/users/manager_status/${email}`,
            chief: `/users/chief_status/${email}`,
            teamlead: `/users/teamlead_status/${email}`,
            employee: `/users/employee_status/${email}`
        };
        const { data } = await axios.post(endpoints[roleType], { email });
        return data;
    };

    const importUsers = async (file) => {
        const formData = new FormData();
        formData.append('file', file);
        const { data } = await axios.post('/users/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return data;
    };

    const exportTemplate = async (role) => {
        window.location.href = `/users/export/${role}`;
    };

    const getDepartments = async () => {
        const { data } = await axios.get('/departments/list', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        return data;
    };

    const addDepartment = async (title) => {
        const { data } = await axios.post('/departments', { title });
        return data;
    };

    const updateDepartment = async (oldTitle, newTitle) => {
        const { data } = await axios.post(`/departments/update/${oldTitle}`, { newTitle });
        return data;
    };

    const deleteDepartment = async (title) => {
        const { data } = await axios.get(`/departments/delete/${title}`);
        return data;
    };

    return {
        getTeamMembers,
        addTeamMember,
        updateTeamMember,
        deleteTeamMember,
        changeRole,
        importUsers,
        exportTemplate,
        getDepartments,
        addDepartment,
        updateDepartment,
        deleteDepartment
    };
}
