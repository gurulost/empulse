import axios from 'axios';

export function useReportsApi() {
    const getTrends = async (metric = 'engagement', companyId = null) => {
        const params = { metric };
        if (companyId) {
            params.company_id = companyId;
        }

        const { data } = await axios.get('/reports/trends', { params });
        return data;
    };

    const getComparison = async (dimension = 'department', waveId = null, companyId = null) => {
        const params = { dimension };
        if (waveId) {
            params.wave_id = waveId;
        }
        if (companyId) {
            params.company_id = companyId;
        }

        const { data } = await axios.get('/reports/comparison', { params });
        return data;
    };

    return {
        getTrends,
        getComparison
    };
}
