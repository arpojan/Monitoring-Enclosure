import axios from 'axios';

// Konfigurasi standar Axios untuk Laravel backend
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Contoh fungsi helper jika ingin memisahkan method API
export const updateEnclosureApi = (id, payload) => {
    return api.put(`/enclosures/${id}`, payload);
};

export default api;


export const updateParametersApi = (id, payload) => {
    return api.put(`/enclosures/${id}/parameters`, payload);
};

export const getControlConfigApi = (id) => {
    return api.get(`/enclosures/${id}/control-config`);
};

export const applyRecommendationApi = (id) => {
    return api.post(`/recommendations/${id}/apply`);
};

export const rejectRecommendationApi = (id) => {
    return api.post(`/recommendations/${id}/reject`);
};
