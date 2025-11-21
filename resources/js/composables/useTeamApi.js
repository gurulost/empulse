import axios from 'axios';

export function useTeamApi() {
    const getTeamMembers = async (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `/team/api/members?${queryString}` : '/team/api/members';
        const { data } = await axios.get(url);
        return data; // Returns { data: [...], current_page: 1, ... }
    };

    const addTeamMember = async (memberData) => {
        const { data } = await axios.post('/team/api/members', memberData);
        return data;
    };

    const updateTeamMember = async (email, memberData) => {
        const { data } = await axios.put(`/team/api/members/${email}`, memberData);
        return data;
    };

    const deleteTeamMember = async (email) => {
        const { data } = await axios.delete(`/team/api/members/${email}`);
        return data;
    };

    const importUsers = async (file) => {
        const formData = new FormData();
        formData.append('file', file);
        const { data } = await axios.post('/team/api/members/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return data;
    };

    const exportTemplate = async (role) => {
        window.location.href = `/users/export/${role}`;
    };

    const getDepartments = async () => {
        const { data } = await axios.get('/team/api/departments');
        return data; // Returns { data: [...], ... }
    };

    const addDepartment = async (title) => {
        const { data } = await axios.post('/team/api/departments', { title });
        return data;
    };

    const updateDepartment = async (oldTitle, newTitle) => {
        const { data } = await axios.put(`/team/api/departments/${oldTitle}`, { newTitle });
        return data;
    };

    const deleteDepartment = async (title) => {
        const { data } = await axios.delete(`/team/api/departments/${title}`);
        return data;
    };

    return {
        getTeamMembers,
        addTeamMember,
        updateTeamMember,
        deleteTeamMember,
        importUsers,
        exportTemplate,
        getDepartments,
        addDepartment,
        updateDepartment,
        deleteDepartment
    };
}
