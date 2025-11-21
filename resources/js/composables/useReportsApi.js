import axios from 'axios';

export function useReportsApi() {
    const getTrends = async (metric = 'engagement') => {
        const { data } = await axios.get(`/reports/trends?metric=${metric}`);
        return data;
    };

    const getComparison = async (dimension = 'department', waveId = null) => {
        const params = { dimension };
        if (waveId) params.wave_id = waveId;

        const { data } = await axios.get('/reports/comparison', { params });
        return data;
    };

    return {
        getTrends,
        getComparison
    };
}
