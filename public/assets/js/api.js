/**
 * Smart Enclosure — API Service
 * ================================
 * Reusable API layer for frontend-backend communication.
 * All API calls return JSON and handle errors consistently.
 */

const API = {
    baseUrl: '/api',

    /**
     * Generic fetch wrapper with error handling.
     */
    async request(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...options.headers,
                },
                ...options,
            });

            const data = await response.json();

            if (!response.ok) {
                console.error(`API Error [${response.status}]:`, data);
                return { success: false, error: data.message || 'Request failed' };
            }

            return data;
        } catch (error) {
            console.error('Network Error:', error);
            return { success: false, error: 'Tidak dapat terhubung ke server' };
        }
    },

    /**
     * GET /api/enclosures/{id}/latest
     * Telemetry terbaru untuk dashboard cards.
     */
    async getLatest(enclosureId) {
        return this.request(`/enclosures/${enclosureId}/latest`);
    },

    /**
     * GET /api/enclosures/{id}/history?period=24h|7d|30d
     * Historical telemetry untuk chart.
     */
    async getHistory(enclosureId, period = '24h') {
        return this.request(`/enclosures/${enclosureId}/history?period=${period}`);
    },

    /**
     * GET /api/enclosures/{id}/dashboard
     * Single endpoint gabungan untuk dashboard.
     */
    async getDashboard(enclosureId) {
        return this.request(`/enclosures/${enclosureId}/dashboard`);
    },

    /**
     * GET /api/enclosures/{id}/analytics?period=24h|7d|30d
     * Data untuk halaman Analitik.
     */
    async getAnalytics(enclosureId, period = '24h') {
        return this.request(`/enclosures/${enclosureId}/analytics?period=${period}`);
    },

    /**
     * GET /api/enclosures/{id}/stability
     * Data untuk halaman Stabilitas.
     */
    async getStability(enclosureId, period = '4w') {
        return this.request(`/enclosures/${enclosureId}/stability?period=${period}`);
    },



    /**
     * GET /api/enclosures/{id}/control-config
     * Parameter yang akan dipakai ESP32 untuk rule-based misting.
     */
    async getControlConfig(enclosureId) {
        return this.request(`/enclosures/${enclosureId}/control-config`);
    },

    /**
     * PUT /api/enclosures/{id}/parameters
     * Update bottom humidity, top humidity, dan durasi misting.
     */
    async updateParameters(enclosureId, data) {
        return this.request(`/enclosures/${enclosureId}/parameters`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * POST /api/recommendations/{id}/apply
     * Apply rekomendasi AI sebagai DSS action.
     */
    async applyRecommendation(recommendationId) {
        return this.request(`/recommendations/${recommendationId}/apply`, {
            method: 'POST'
        });
    },

    /**
     * POST /api/recommendations/{id}/reject
     */
    async rejectRecommendation(recommendationId) {
        return this.request(`/recommendations/${recommendationId}/reject`, {
            method: 'POST'
        });
    },

    /**
     * POST /api/enclosures/{id}/analyze
     * Picu analisis DSS (Stability Score + Insight + Recommendation).
     * Hasilnya disimpan dengan status 'pending' — belum memengaruhi ESP32.
     *
     * @param {number} enclosureId
     * @param {number} [hours=24] Jendela analisis dalam jam
     */
    async runDssAnalysis(enclosureId, hours = 24) {
        return this.request(`/enclosures/${enclosureId}/analyze`, {
            method: 'POST',
            body: JSON.stringify({ hours })
        });
    },

    // --- Settings Endpoints ---
    async triggerManualMist(enclosureId) {
        return this.request(`/enclosures/${enclosureId}/mist/trigger`, {
            method: 'POST'
        });
    },
    async updateEnclosure(enclosureId, data) {
        return this.request(`/enclosures/${enclosureId}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async updateUserSettings(data) {
        // Simulasi request ke backend
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({ success: true, data: data });
            }, 500);
        });
    }
};
