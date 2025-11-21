import axios from 'axios';

export function useAnalyticsApi() {
    const getDashboardData = async (params = {}) => {
        const { data } = await axios.get('/analytics/api/dashboard', { params });
        return data; // Returns { data: {...}, filters: {...} }
    };

    return {
        getDashboardData
    };
}
