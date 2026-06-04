document.addEventListener('DOMContentLoaded', () => {

    // --- State & Navigation ---
    const loginForm = document.getElementById('login-form');
    const loginScreen = document.getElementById('login-screen');
    const enclosureScreen = document.getElementById('enclosure-screen');
    const enclosureCards = document.querySelectorAll('.enclosure-card:not(.add-new)');
    const appScreen = document.getElementById('app-screen');
    const logoutBtn = document.getElementById('logout-btn');

    const navLinks = document.querySelectorAll('.nav-links li');
    const pageViews = document.querySelectorAll('.page-view');
    const pageTitle = document.getElementById('page-title');

    const menuToggles = document.querySelectorAll('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    // Chart instances
    let charts = {};

    // Prevent dashboard polling from overwriting parameter inputs while the user is editing.
    // The dashboard fetch runs every few seconds, so without this guard the form can
    // visually revert to the previous DB value before the user presses Save.
    let controlParametersDirty = false;
    let controlParametersSaving = false;

    // Pengecekan agar tidak error jika tidak ada di halaman login
    if (loginForm) {
        // Login Handle
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loginScreen.classList.remove('active');
            setTimeout(() => {
                if (enclosureScreen) enclosureScreen.classList.add('active');
            }, 300);
        });
    }

    // Enclosure Selection
    if (enclosureCards.length > 0) {
        enclosureCards.forEach(card => {
            card.addEventListener('click', () => {
                if (enclosureScreen) enclosureScreen.classList.remove('active');
                const enclosureId = card.getAttribute('data-enclosure');

                setTimeout(() => {
                    if (appScreen) appScreen.classList.add('active');
                    initCharts();

                    if (enclosureId === '2') {
                        setTimeout(simulateScoreDrop, 3000);
                    } else {
                        setStableState();
                    }
                }, 300);
            });
        });
    }

    // Logout Handle (Untuk versi HTML lama, versi Laravel sudah pakai Form di Blade)
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (appScreen) appScreen.classList.remove('active');
            setTimeout(() => {
                if (loginScreen) loginScreen.classList.add('active');
                destroyAllCharts();
            }, 300);
        });
    }

    // Enclosure Screen - Logout Button
    const enclosureLogoutBtn = document.getElementById('enclosure-logout-btn');
    if (enclosureLogoutBtn) {
        enclosureLogoutBtn.addEventListener('click', () => {
            if (enclosureScreen) enclosureScreen.classList.remove('active');
            setTimeout(() => {
                if (loginScreen) loginScreen.classList.add('active');
            }, 300);
        });
    }

    // Enclosure Screen - Theme Toggle
    const enclosureThemeToggle = document.getElementById('enclosure-theme-toggle');
    if (enclosureThemeToggle) {
        enclosureThemeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }

    // Navigation Handle
    if (navLinks.length > 0) {
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // PERBAIKAN: Jika li mengandung tag <a> (misal rute Laravel Pilih Kandang / Logout), 
                // biarkan browser yang melakukan perpindahan halaman secara bawaan.
                if (link.querySelector('a')) {
                    return;
                }

                if (link.id === 'back-enclosure') {
                    if (appScreen) appScreen.classList.remove('active');
                    setTimeout(() => {
                        if (enclosureScreen) enclosureScreen.classList.add('active');
                        destroyAllCharts();
                    }, 300);

                    if (window.innerWidth <= 768 && sidebar) {
                        sidebar.classList.remove('open');
                    }
                    return;
                }

                // Update active state in nav
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                // Update title
                const target = link.getAttribute('data-target');
                if (pageTitle && link.querySelector('span')) {
                    pageTitle.innerText = link.querySelector('span').innerText;
                }

                // Show target view
                pageViews.forEach(view => {
                    view.classList.remove('active');
                    if (view.id === `view-${target}`) {
                        view.classList.add('active');
                    }
                });

                // Re-init charts for the newly visible view
                // (Canvas elements need to be visible for Chart.js to render correctly)
                setTimeout(() => {
                    initCharts();
                }, 50);

                // Close mobile menu if open
                if (window.innerWidth <= 768 && sidebar) {
                    sidebar.classList.remove('open');
                }
            });
        });
    }

    // Mobile Menu Toggle
    if (menuToggles.length > 0 && sidebar) {
        menuToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
        });
    }

    // Range Sliders update values
    const sliders = [
        { id: 'bottomRhSlider', valId: 'bottomRhVal' },
        { id: 'topRhSlider', valId: 'topRhVal' }
    ];

    sliders.forEach(s => {
        const slider = document.getElementById(s.id);
        const val = document.getElementById(s.valId);
        if (slider && val) {
            slider.addEventListener('input', (e) => {
                val.innerText = e.target.value + '%';
            });
        }
    });

    // Toast Notification & Modal Simulation
    const toast = document.getElementById('notificationToast');
    const closeToast = document.querySelector('.close-toast');
    const modal = document.getElementById('recommendation-modal');
    const closeBtns = document.querySelectorAll('.close-modal');
    const applyBtn = document.getElementById('apply-recommendation-btn');

    function showNotificationToast(title, msg) {
        if (toast && title && msg) {
            toast.querySelector('.toast-content h4').innerText = title;
            toast.querySelector('.toast-content p').innerText = msg;
            setTimeout(() => {
                toast.classList.add('show');
            }, 500);
        }
    }

    if (closeToast && toast) {
        closeToast.addEventListener('click', () => {
            toast.classList.remove('show');
        });
    }

    if (closeBtns.length > 0) {
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (modal) modal.classList.remove('active');
            });
        });
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            if (modal) modal.classList.remove('active');
            restoreStabilityScore();
        });
    }

    function simulateScoreDrop() {
        const scoreVal = document.getElementById('stability-score-value');
        const statusText = document.getElementById('stability-status-text');
        const cardIcon = document.getElementById('stability-icon');

        if (scoreVal && statusText) {
            scoreVal.innerHTML = '65<span class="unit">/100</span>';
            scoreVal.className = 'metric-value text-red';
            statusText.innerText = 'Peringatan';
            statusText.className = 'text-red';
            if (cardIcon) {
                cardIcon.className = 'ph ph-warning-circle text-red';
            }
            if (modal) {
                modal.classList.add('active');
            }
        }
    }

    function setStableState() {
        const scoreVal = document.getElementById('stability-score-value');
        const statusText = document.getElementById('stability-status-text');
        const cardIcon = document.getElementById('stability-icon');

        if (scoreVal && statusText) {
            scoreVal.innerHTML = '92<span class="unit">/100</span>';
            scoreVal.className = 'metric-value text-green';
            statusText.innerText = 'Stabil';
            statusText.className = 'text-green';
            if (cardIcon) {
                cardIcon.className = 'ph ph-shield-check text-green';
            }
            if (modal) {
                modal.classList.remove('active');
            }
        }
    }

    function restoreStabilityScore() {
        const scoreVal = document.getElementById('stability-score-value');
        const statusText = document.getElementById('stability-status-text');
        const cardIcon = document.getElementById('stability-icon');

        if (scoreVal && statusText) {
            scoreVal.innerHTML = '94<span class="unit">/100</span>';
            scoreVal.className = 'metric-value text-green';
            statusText.innerText = 'Stabil (Diterapkan)';
            statusText.className = 'text-green';
            if (cardIcon) {
                cardIcon.className = 'ph ph-shield-check text-green';
            }
            showNotificationToast('Rekomendasi Diterapkan', 'Parameter kandang berhasil diperbarui.');
        }
    }

    // --- Helper: Destroy All Charts ---
    function destroyAllCharts() {
        Object.values(charts).forEach(c => {
            if (c && typeof c.destroy === 'function') c.destroy();
        });
        charts = {};
    }

    // --- Chart.js Configuration ---
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Outfit', sans-serif";
    }

    let commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                titleColor: '#1f2924',
                bodyColor: '#4a5d54',
                borderColor: 'rgba(42, 157, 143, 0.15)',
                borderWidth: 1,
                padding: 10,
                boxPadding: 4
            }
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            y: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            }
        }
    };

    // --- Theme Toggle ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = themeToggleBtn ? themeToggleBtn.querySelector('i') : null;

    function updateChartColors(theme) {
        if (typeof Chart === 'undefined') return;

        const isDark = theme === 'dark';
        Chart.defaults.color = isDark ? '#a4b8ad' : '#4a5d54';

        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
        const tooltipBg = isDark ? 'rgba(18, 28, 24, 0.95)' : 'rgba(255, 255, 255, 0.95)';
        const tooltipTitle = isDark ? '#f0f4f1' : '#1f2924';
        const tooltipBody = isDark ? '#a4b8ad' : '#4a5d54';

        if (commonOptions.plugins) {
            commonOptions.plugins.tooltip.backgroundColor = tooltipBg;
            commonOptions.plugins.tooltip.titleColor = tooltipTitle;
            commonOptions.plugins.tooltip.bodyColor = tooltipBody;
        }
        if (commonOptions.scales) {
            if (commonOptions.scales.x) commonOptions.scales.x.grid.color = gridColor;
            if (commonOptions.scales.y) commonOptions.scales.y.grid.color = gridColor;
        }

        Object.values(charts).forEach(chart => {
            if (chart.options.scales) {
                if (chart.options.scales.x) chart.options.scales.x.grid.color = gridColor;
                if (chart.options.scales.y) chart.options.scales.y.grid.color = gridColor;
                if (chart.options.scales.y1) chart.options.scales.y1.grid.color = gridColor;
            }
            if (chart.options.plugins && chart.options.plugins.tooltip) {
                chart.options.plugins.tooltip.backgroundColor = tooltipBg;
                chart.options.plugins.tooltip.titleColor = tooltipTitle;
                chart.options.plugins.tooltip.bodyColor = tooltipBody;
            }
            if (chart.config.type === 'doughnut' && chart.data.datasets.length > 0) {
                chart.data.datasets[0].backgroundColor[1] = gridColor;
            }
            chart.update();
        });
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'ph ph-sun' : 'ph ph-moon';
        }
        // Sync enclosure screen theme toggle icon
        const encThemeIcon = document.querySelector('#enclosure-theme-toggle i');
        if (encThemeIcon) {
            encThemeIcon.className = theme === 'dark' ? 'ph ph-sun' : 'ph ph-moon';
        }
        updateChartColors(theme);

        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#0c1411' : '#f0f4f1');
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }

    function initCharts() {
        if (typeof Chart === 'undefined') return; // Cek jika Chart.js belum di-load

        const zonePlugin = {
            id: 'zonePlugin',
            beforeDraw: (chart) => {
                if (!chart.options.zone) return;
                const ctx = chart.ctx;
                const yAxis = chart.scales.y;
                const xAxis = chart.scales.x;
                if (!yAxis || !xAxis) return; // Defensive check
                
                const maxVal = chart.options.zone.max;
                const minVal = chart.options.zone.min;
                
                // Cek apakah nilai ada dalam range skala saat ini
                if (typeof yAxis.min === 'undefined' || typeof yAxis.max === 'undefined') return;
                if (yAxis.min > maxVal || yAxis.max < minVal) return;

                const yTop = yAxis.getPixelForValue(Math.min(yAxis.max, maxVal));
                const yBottom = yAxis.getPixelForValue(Math.max(yAxis.min, minVal));
                
                ctx.save();
                ctx.fillStyle = chart.options.zone.color;
                ctx.fillRect(xAxis.left, yTop, xAxis.width, yBottom - yTop);
                ctx.restore();
            }
        };

        // 1. Dashboard RH Chart
        const ctxRh = document.getElementById('rhRealtimeChart');
        if (ctxRh && !charts.rhRealtime) {
            const bottomInput = document.getElementById('param-bottom-humidity');
            const topInput = document.getElementById('param-top-humidity');
            const initialMin = bottomInput ? parseFloat(bottomInput.value) || 80 : 80;
            const initialMax = topInput ? parseFloat(topInput.value) || 90 : 90;

            charts.rhRealtime = new Chart(ctxRh, {
                type: 'line',
                plugins: [zonePlugin],
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Kelembapan (%)',
                            data: [],
                            misting: [], // Custom array for misting status
                            borderColor: '#2a9d8f',
                            backgroundColor: 'rgba(42, 157, 143, 0.2)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: (ctx) => {
                                if (ctx.dataset && ctx.dataset.misting && ctx.dataset.misting[ctx.dataIndex]) return 4;
                                return 0;
                            },
                            pointBackgroundColor: (ctx) => {
                                if (ctx.dataset && ctx.dataset.misting && ctx.dataset.misting[ctx.dataIndex]) return '#ff9800'; // Orange for misting
                                return '#2a9d8f';
                            },
                            pointHitRadius: 10
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    zone: {
                        min: initialMin,
                        max: initialMax,
                        color: 'rgba(76, 175, 80, 0.1)'
                    },
                    scales: {
                        x: commonOptions.scales.x,
                        y: {
                            type: 'linear',
                            display: true,
                            title: { display: true, text: 'RH (%)' },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            min: 60,
                            max: 100
                        }
                    }
                }
            });
        }

        // 1b. Dashboard Temp Chart
        const ctxTemp = document.getElementById('tempRealtimeChart');
        if (ctxTemp && !charts.tempRealtime) {
            charts.tempRealtime = new Chart(ctxTemp, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Suhu (°C)',
                            data: [],
                            borderColor: '#e76f51',
                            backgroundColor: 'rgba(231, 111, 81, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHitRadius: 10
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: commonOptions.scales.x,
                        y: {
                            type: 'linear',
                            display: true,
                            title: { display: true, text: 'Suhu (°C)' },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        }
                    }
                }
            });
        }


        // 2. Historical Chart (Analytics View)
        const ctxHist = document.getElementById('historicalChart');
        if (ctxHist && !charts.historical) {
            charts.historical = new Chart(ctxHist, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [
                        {
                            label: 'Rata-rata Kelembapan (%)',
                            data: [84, 85, 83, 86, 88, 85, 84],
                            borderColor: '#457b9d',
                            backgroundColor: 'rgba(69, 123, 157, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2
                        },
                        {
                            label: 'Rata-rata Suhu (°C)',
                            data: [24.1, 24.5, 24.8, 24.4, 24.2, 24.5, 24.6],
                            borderColor: '#4caf50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    ...commonOptions
                }
            });
        }

        // 3. Stability Trend Chart (Analytics View)
        const ctxStabilityTrend = document.getElementById('stabilityTrendChart');
        if (ctxStabilityTrend && !charts.stabilityTrend) {
            charts.stabilityTrend = new Chart(ctxStabilityTrend, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Skor Stabilitas',
                        data: [75, 82, 80, 88, 95, 90, 92],
                        borderColor: '#e9c46a',
                        backgroundColor: 'rgba(233, 196, 106, 0.2)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: commonOptions.scales.x,
                        y: {
                            min: 0,
                            max: 100,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        }
                    }
                }
            });
        }

        // 4. Humidity Distribution Chart (Analytics View)
        const ctxHumDist = document.getElementById('humidityDistChart');
        if (ctxHumDist && !charts.humidityDist) {
            charts.humidityDist = new Chart(ctxHumDist, {
                type: 'bar',
                data: {
                    labels: ['<70%', '70-75%', '75-80%', '80-85%', '85-90%', '90-95%', '>95%'],
                    datasets: [{
                        label: 'Frekuensi Pembacaan',
                        data: [2, 5, 12, 35, 28, 14, 4],
                        backgroundColor: [
                            'rgba(231, 111, 81, 0.7)',
                            'rgba(233, 196, 106, 0.7)',
                            'rgba(233, 196, 106, 0.7)',
                            'rgba(42, 157, 143, 0.7)',
                            'rgba(42, 157, 143, 0.7)',
                            'rgba(69, 123, 157, 0.7)',
                            'rgba(69, 123, 157, 0.7)'
                        ],
                        borderColor: [
                            'rgba(231, 111, 81, 1)',
                            'rgba(233, 196, 106, 1)',
                            'rgba(233, 196, 106, 1)',
                            'rgba(42, 157, 143, 1)',
                            'rgba(42, 157, 143, 1)',
                            'rgba(69, 123, 157, 1)',
                            'rgba(69, 123, 157, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            ...commonOptions.scales.x,
                            title: { display: true, text: 'Rentang Kelembapan' }
                        },
                        y: {
                            ...commonOptions.scales.y,
                            title: { display: true, text: 'Jumlah Pembacaan' },
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 5. Misting Activity Chart (Analytics View)
        const ctxMisting = document.getElementById('mistingChart');
        if (ctxMisting && !charts.misting) {
            charts.misting = new Chart(ctxMisting, {
                type: 'bar',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Aktivasi Pengabutan',
                        data: [4, 3, 5, 2, 3, 4, 3],
                        backgroundColor: 'rgba(69, 123, 157, 0.6)',
                        borderColor: '#457b9d',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Durasi Total (menit)',
                        data: [8, 6, 10, 4, 6, 8, 6],
                        backgroundColor: 'rgba(42, 157, 143, 0.6)',
                        borderColor: '#2a9d8f',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: commonOptions.scales.x,
                        y: {
                            ...commonOptions.scales.y,
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 6. Score Gauge Chart (Stability View - Doughnut)
        const ctxGauge = document.getElementById('scoreGaugeChart');
        if (ctxGauge && !charts.gauge) {
            charts.gauge = new Chart(ctxGauge, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [92, 8],
                        backgroundColor: [
                            '#4caf50',
                            'rgba(0, 0, 0, 0.05)'
                        ],
                        borderWidth: 0,
                        cutout: '80%',
                        circumference: 270,
                        rotation: 225
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }

        // 7. Stability Component Radar (Stability View)
        const ctxRadar = document.getElementById('stabilityRadarChart');
        if (ctxRadar && !charts.radar) {
            charts.radar = new Chart(ctxRadar, {
                type: 'radar',
                data: {
                    labels: ['Kepatuhan Range', 'Variabilitas', 'Durasi Stabilitas', 'Konsistensi Suhu', 'Efisiensi Misting'],
                    datasets: [{
                        label: 'Skor Saat Ini',
                        data: [95, 85, 70, 92, 78],
                        backgroundColor: 'rgba(42, 157, 143, 0.2)',
                        borderColor: '#2a9d8f',
                        borderWidth: 2,
                        pointBackgroundColor: '#2a9d8f',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#2a9d8f'
                    },
                    {
                        label: 'Target Ideal',
                        data: [90, 90, 90, 90, 90],
                        backgroundColor: 'rgba(76, 175, 80, 0.05)',
                        borderColor: 'rgba(76, 175, 80, 0.4)',
                        borderWidth: 1,
                        borderDash: [5, 5],
                        pointBackgroundColor: 'rgba(76, 175, 80, 0.4)',
                        pointBorderColor: '#fff',
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                backdropColor: 'transparent'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            angleLines: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            pointLabels: {
                                font: { size: 11 }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        }
                    }
                }
            });
        }

        // 8. Stability History Chart (Stability View)
        const ctxStabHist = document.getElementById('stabilityHistoryChart');
        if (ctxStabHist && !charts.stabilityHistory) {
            charts.stabilityHistory = new Chart(ctxStabHist, {
                type: 'line',
                data: {
                    labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                    datasets: [{
                        label: 'Skor Rata-rata',
                        data: [78, 82, 88, 92],
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.15)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointBackgroundColor: '#4caf50',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Skor Minimum',
                        data: [65, 70, 75, 85],
                        borderColor: '#e9c46a',
                        backgroundColor: 'rgba(233, 196, 106, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 4
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: commonOptions.scales.x,
                        y: {
                            min: 0,
                            max: 100,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            title: { display: true, text: 'Skor Stabilitas' }
                        }
                    }
                }
            });
        }
    }

    // ─── Dashboard Data Integration ─────────────────────────────
    // Fetch real data from backend and update UI

    // appScreen already declared at top of DOMContentLoaded
    const ENCLOSURE_ID = appScreen ? appScreen.getAttribute('data-enclosure-id') : null;
    let pollInterval = null;
    let latestRecommendationId = null;

    /**
     * Update dashboard metric cards with real API data.
     */
    function updateDashboardCards(data) {
        if (!data) return;
        try {
        // Temperature card
        const tempVal = document.getElementById('temp-value');
        const tempTrend = document.getElementById('temp-trend');
        if (tempVal && data.telemetry) {
            tempVal.innerHTML = `${parseFloat(data.telemetry.temperature).toFixed(1)}<span class="unit">°C</span>`;
        }
        if (tempTrend && data.trend && data.trend.temperature !== null) {
            const t = data.trend.temperature;
            const icon = t >= 0 ? 'ph-trend-up' : 'ph-trend-down';
            const sign = t >= 0 ? '+' : '';
            tempTrend.className = `metric-trend ${t >= 0 ? 'positive' : 'negative'}`;
            tempTrend.innerHTML = `<i class="ph ${icon}"></i> ${sign}${t}°C dari jam lalu`;
        } else if (tempTrend) {
            tempTrend.innerHTML = `<i class="ph ph-minus"></i> Belum cukup data`;
        }

        // Humidity card
        const humVal = document.getElementById('humidity-value');
        const humTrend = document.getElementById('humidity-trend');
        if (humVal && data.telemetry) {
            humVal.innerHTML = `${parseFloat(data.telemetry.humidity).toFixed(1)}<span class="unit">%</span>`;
        }
        if (humTrend && data.trend && data.trend.humidity !== null) {
            const h = data.trend.humidity;
            const icon = h >= 0 ? 'ph-trend-up' : 'ph-trend-down';
            const sign = h >= 0 ? '+' : '';
            humTrend.className = `metric-trend ${h >= 0 ? 'positive' : 'negative'}`;
            humTrend.innerHTML = `<i class="ph ${icon}"></i> ${sign}${h}% dari jam lalu`;
        } else if (humTrend) {
            humTrend.innerHTML = `<i class="ph ph-minus"></i> Belum cukup data`;
        }

        // Misting card
        const mistVal = document.getElementById('misting-value');
        const mistTrend = document.getElementById('misting-trend');
        if (mistVal && data.telemetry) {
            const isOn = data.telemetry.misting_status;
            mistVal.textContent = isOn ? 'Aktif' : 'Siaga';
            mistVal.className = `metric-value ${isOn ? 'text-blue' : ''}`;
        }
        if (mistTrend && data.parameters) {
            const duration = data.parameters.misting_duration_seconds || '-';
            mistTrend.textContent = `${data.parameters.is_misting_auto ? 'Mode: Otomatis' : 'Mode: Manual'} • ${duration} detik`;
        }

        // Sync parameter control form with current backend config.
        // Do not overwrite the fields while the user is editing or while a save request is in progress.
        if (data.parameters && !controlParametersDirty && !controlParametersSaving) {
            const bottomInput = document.getElementById('param-bottom-humidity');
            const topInput = document.getElementById('param-top-humidity');
            const durationInput = document.getElementById('param-misting-duration');

            if (bottomInput) {
                bottomInput.value = parseFloat(data.parameters.misting_bottom_threshold).toFixed(1);
            }
            if (topInput) {
                topInput.value = parseFloat(data.parameters.misting_top_threshold).toFixed(1);
            }
            if (durationInput) {
                durationInput.value = data.parameters.misting_duration_seconds || 10;
            }
        }

        // Update Chart Zone dynamically based on active configuration
        if (data.parameters) {
            const idealMin = parseFloat(data.parameters.misting_bottom_threshold);
            const idealMax = parseFloat(data.parameters.misting_top_threshold);

            if (charts.rhRealtime && charts.rhRealtime.options.zone) {
                charts.rhRealtime.options.zone.min = idealMin;
                charts.rhRealtime.options.zone.max = idealMax;
            }

            const idealZoneLabel = document.getElementById('rh-ideal-zone-label');
            if (idealZoneLabel) {
                idealZoneLabel.innerHTML = `<span style="display:inline-block; width:12px; height:12px; background:rgba(76, 175, 80, 0.2); border:1px solid rgba(76, 175, 80, 1);"></span> Zona Ideal: ${Math.round(idealMin)}–${Math.round(idealMax)}%`;
            }
        }

        // Stability card
        const scoreVal = document.getElementById('stability-score-value');
        const statusText = document.getElementById('stability-status-text');
        const stabIcon = document.getElementById('stability-icon');
        if (data.stability && scoreVal && statusText) {
            const score = parseFloat(data.stability.final_score);
            const status = data.stability.status;
            scoreVal.innerHTML = `${Math.round(score)}<span class="unit">/100</span>`;

            const colorClass = score >= 85 ? 'text-green' : score >= 70 ? 'text-blue' : score >= 50 ? 'text-warning' : 'text-red';
            scoreVal.className = `metric-value ${colorClass}`;
            statusText.textContent = status;
            statusText.className = colorClass;
            if (stabIcon) {
                const iconName = score >= 70 ? 'ph-shield-check' : 'ph-warning-circle';
                stabIcon.className = `ph ${iconName} ${colorClass}`;
            }
        }

        // System status indicator
        const statusDot = document.getElementById('system-status-dot');
        const statusTxt = document.getElementById('system-status-text');
        if (statusDot && statusTxt && data.enclosure) {
            const isOnline = data.enclosure.system_status === 'online';
            statusDot.className = isOnline ? 'dot pulse-green' : 'dot pulse-red';
            statusTxt.textContent = isOnline ? 'Sistem Aktif' : 'Sistem Offline';
        }

        // Summary Insights & Badges
        const insightSum = document.getElementById('dashboard-insight-summary');
        const stabLargeStatus = document.getElementById('stability-status-text-large');
        const stabLargeIcon = document.getElementById('stability-icon-large');
        const stabScoreSub = document.getElementById('stability-score-sub');

        if (data.stability && stabLargeStatus) {
            const sc = parseFloat(data.stability.final_score);
            let summaryText = "";
            let iconEmoji = "🔵";
            let colorVar = "var(--primary-color)";

            if (sc >= 85) {
                iconEmoji = "🔵"; colorVar = "var(--primary-color)";
                summaryText = `Kondisi enclosure **sangat optimal** dalam 24 jam terakhir. Lingkungan mendukung pertumbuhan amfibi dengan baik.`;
            } else if (sc >= 70) {
                iconEmoji = "🟢"; colorVar = "#4caf50";
                summaryText = `Kondisi enclosure **cukup stabil**. Tidak ada fluktuasi ekstrem yang membahayakan.`;
            } else if (sc >= 50) {
                iconEmoji = "🟡"; colorVar = "#ff9800";
                summaryText = `Kondisi enclosure menjadi **perhatian**. Kelembapan mulai berfluktuasi atau keluar dari zona ideal dalam waktu yang cukup lama.`;
            } else {
                iconEmoji = "🔴"; colorVar = "#f44336";
                summaryText = `**Peringatan Kritis!** Lingkungan tidak stabil. Segera cek sistem pengabutan atau parameter ambang batas.`;
            }

            stabLargeStatus.textContent = data.stability.status;
            stabLargeIcon.textContent = iconEmoji;
            if(stabScoreSub) stabScoreSub.textContent = `Skor: ${Math.round(sc)}/100`;
            
            if(insightSum) {
                insightSum.innerHTML = summaryText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                insightSum.parentElement.style.borderLeftColor = colorVar;
            }
        }

        // AI Insight Cards
        const insightList = document.getElementById('dashboard-ai-insights');
        if (insightList && data.insight) {
            const ins = data.insight;
            const insIcon = ins.severity === 'critical' ? 'ph-warning' : ins.severity === 'warning' ? 'ph-warning-circle' : 'ph-info';
            const insClass = ins.severity === 'critical' ? 'warning' : ins.severity === 'warning' ? 'info' : 'success';
            
            insightList.innerHTML = `
                <div class="insight-item ${insClass}">
                    <div class="insight-icon"><i class="ph ${insIcon}"></i></div>
                    <div class="insight-content">
                        <h4>Interpretasi AI</h4>
                        <p>${ins.description}</p>
                    </div>
                </div>
            `;
        } else if (insightList) {
            insightList.innerHTML = `<p style="color:var(--text-muted); font-size:0.9rem;">Tidak ada temuan krusial saat ini.</p>`;
        }

        // Recommendation
        const recPanel = document.getElementById('dashboard-recommendation');
        if (recPanel && data.recommendation) {
            const rec = data.recommendation;
            latestRecommendationId = rec.id;
            const detail = [
                `Bottom: ${rec.current_bottom_rh ?? '-'}% → ${rec.recommended_bottom_rh ?? '-'}%`,
                `Top: ${rec.current_top_rh ?? '-'}% → ${rec.recommended_top_rh ?? '-'}%`,
                `Durasi: ${rec.current_duration ?? '-'}s → ${rec.recommended_duration ?? '-'}s`,
            ].join(' | ');
            recPanel.querySelector('.recommendation-text p').innerHTML = `${rec.description}<br><small style="color:var(--text-muted);">${detail}</small>`;
            recPanel.querySelector('.btn-primary').removeAttribute('disabled');
            recPanel.querySelector('.btn-primary').innerHTML = `<i class="ph ph-check"></i> Terapkan Rekomendasi AI`;
        } else if (recPanel) {
            latestRecommendationId = null;
            recPanel.querySelector('.recommendation-text p').textContent = "Tidak ada tindakan yang direkomendasikan saat ini.";
            recPanel.querySelector('.btn-primary').setAttribute('disabled', 'true');
            recPanel.querySelector('.btn-primary').innerHTML = `<i class="ph ph-check"></i> Menunggu Saran`;
        }

        // Event Timeline
        const timeline = document.getElementById('dashboard-timeline');
        if (timeline && data.events && data.events.length > 0) {
            timeline.innerHTML = data.events.map(e => {
                const dotClass = e.type.includes('offline') ? 'warning' : e.type.includes('online') ? 'success' : 'system';
                return `<div class="timeline-item">
                    <div class="timeline-dot ${dotClass}"></div>
                    <div class="timeline-time">${e.time}</div>
                    <div class="timeline-desc">${e.description}</div>
                </div>`;
            }).join('');
        } else if (timeline) {
            timeline.innerHTML = `<div class="timeline-item"><div class="timeline-time">-</div><div class="timeline-desc">Belum ada kejadian tercatat</div></div>`;
        }
        } catch (err) {
            console.error("Error in updateDashboardCards:", err);
        }
    }

    /**
     * Update realtime chart with live data from API.
     */
    function updateRealtimeChart(chartData) {
        if (!chartData || chartData.length === 0) return;

        const labels = chartData.map(d => d.time);
        const humData = chartData.map(d => d.humidity);
        const tempData = chartData.map(d => d.temperature);
        const mistData = chartData.map(d => d.misting);

        // Sub-sample to reduce noise (e.g. max 30 points if it's too dense)
        // tapi jika sudah 10 menitan, harusnya tidak terlalu padat

        if (charts.rhRealtime) {
            charts.rhRealtime.data.labels = labels;
            charts.rhRealtime.data.datasets[0].data = humData;
            charts.rhRealtime.data.datasets[0].misting = mistData;
            charts.rhRealtime.update('none');
        }

        if (charts.tempRealtime) {
            charts.tempRealtime.data.labels = labels;
            charts.tempRealtime.data.datasets[0].data = tempData;
            charts.tempRealtime.update('none');
        }
    }

    /**
     * Fetch dashboard data from API and update UI.
     */
    async function fetchDashboardData() {
        if (!ENCLOSURE_ID || typeof API === 'undefined') return;

        try {
            const response = await API.getDashboard(ENCLOSURE_ID);
            if (!response || !response.success || !response.data) {
                console.warn("fetchDashboardData failed or returned no data.");
                return;
            }

            const data = response.data;
            updateDashboardCards(data);
            updateRealtimeChart(data.chart);
        } catch (error) {
            console.error("fetchDashboardData error:", error);
        }
    }

    /**
     * Start polling dashboard data every 10 seconds.
     */
    function startPolling() {
        // Fetch immediately on start
        fetchDashboardData();

        // Then poll every 10 seconds (matches simulator interval)
        pollInterval = setInterval(fetchDashboardData, 10000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    // ─── Analytics View Integration ─────────────────────────────

    let analyticsLoaded = false;
    let cachedAnalyticsData = null;

    async function fetchAnalyticsData(period = '24h', updateInputs = true) {
        if (!ENCLOSURE_ID || typeof API === 'undefined') return;

        const response = await API.getAnalytics(ENCLOSURE_ID, period);
        if (!response.success) return;

        cachedAnalyticsData = response.data;
        analyticsLoaded = true;

        if (updateInputs) {
            const startDateInput = document.getElementById('date-start');
            const endDateInput = document.getElementById('date-end');
            if (startDateInput && endDateInput) {
                const endDate = new Date();
                let daysBack = 7;
                if (period === '24h') daysBack = 1;
                else if (period === '7d') daysBack = 7;
                else if (period === '30d') daysBack = 30;
                else if (period === '90d') daysBack = 90;

                const startDate = new Date();
                startDate.setDate(endDate.getDate() - daysBack);

                const formatDate = (date) => {
                    const yyyy = date.getFullYear();
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                };

                startDateInput.value = formatDate(startDate);
                endDateInput.value = formatDate(endDate);
            }
        }

        applyAnalyticsFilters();
    }

    function applyAnalyticsFilters() {
        if (!cachedAnalyticsData) return;

        const startDateInput = document.getElementById('date-start');
        const endDateInput = document.getElementById('date-end');
        if (!startDateInput || !endDateInput) return;

        const startDate = new Date(startDateInput.value + 'T00:00:00');
        const endDate = new Date(endDateInput.value + 'T23:59:59');

        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) return;

        if (startDate > endDate) {
            if (typeof showNotificationToast === 'function') {
                showNotificationToast('Rentang Tidak Valid', 'Tanggal mulai harus lebih awal dari tanggal selesai.');
            }
            return;
        }

        // Filter chart data
        const filteredChart = cachedAnalyticsData.chart.filter(log => {
            const logDate = new Date(log.datetime);
            return logDate >= startDate && logDate <= endDate;
        });

        // Compute summary metrics dynamically from filtered data
        const totalReadings = filteredChart.length;
        let avgHumidity = 0;
        let avgTemperature = 0;
        let mistingCycles = 0;
        let timeInRange = 0;

        if (totalReadings > 0) {
            let sumHum = 0;
            let sumTemp = 0;
            let prevMisting = false;
            let inRangeCount = 0;

            // Fetch current biologis range parameters from AppState if available
            const humMin = window.AppState?.enclosure?.parameters?.humidity_min || 80;
            const humMax = window.AppState?.enclosure?.parameters?.humidity_max || 95;

            filteredChart.forEach(log => {
                sumHum += log.humidity;
                sumTemp += log.temperature;
                
                if (log.misting && !prevMisting) {
                    mistingCycles++;
                }
                prevMisting = log.misting;

                if (log.humidity >= humMin && log.humidity <= humMax) {
                    inRangeCount++;
                }
            });

            avgHumidity = (sumHum / totalReadings).toFixed(1);
            avgTemperature = (sumTemp / totalReadings).toFixed(1);
            timeInRange = ((inRangeCount / totalReadings) * 100).toFixed(1);
        }

        // Update DOM summary cards
        const avgRh = document.getElementById('analytics-avg-rh');
        if (avgRh) avgRh.innerHTML = `${avgHumidity}<span class="unit">%</span>`;

        const avgRhSub = document.getElementById('analytics-avg-rh-sub');
        if (avgRhSub) avgRhSub.textContent = `${totalReadings} pembacaan`;

        const avgTemp = document.getElementById('analytics-avg-temp');
        if (avgTemp) avgTemp.innerHTML = `${avgTemperature}<span class="unit">°C</span>`;

        const avgTempSub = document.getElementById('analytics-avg-temp-sub');
        if (avgTempSub) avgTempSub.textContent = 'Rata-rata periode';

        const mistCycles = document.getElementById('analytics-misting-cycles');
        if (mistCycles) mistCycles.innerHTML = `${mistingCycles}<span class="unit">x</span>`;

        const mistSub = document.getElementById('analytics-misting-sub');
        if (mistSub) mistSub.textContent = 'Total siklus ON';

        const timeRange = document.getElementById('analytics-time-range');
        if (timeRange) timeRange.innerHTML = `${timeInRange}<span class="unit">%</span>`;

        const rangeSub = document.getElementById('analytics-range-sub');
        if (rangeSub) {
            const cls = timeInRange >= 80 ? 'positive' : timeInRange >= 50 ? 'text-neutral' : 'negative';
            rangeSub.className = `metric-trend ${cls}`;
            rangeSub.textContent = 'Waktu dalam range biologis';
        }

        // Update Historical line chart
        if (charts.historical) {
            charts.historical.data.labels = filteredChart.map(c => c.time);
            charts.historical.data.datasets[0].data = filteredChart.map(c => c.humidity);
            charts.historical.data.datasets[1].data = filteredChart.map(c => c.temperature);
            charts.historical.update('none');
        }

        // Update Humidity Distribution bar chart based on filtered values
        if (charts.humidityDist) {
            const bins = {'<70': 0, '70-75': 0, '75-80': 0, '80-85': 0, '85-90': 0, '90-95': 0, '>95': 0};
            filteredChart.forEach(log => {
                const h = log.humidity;
                if      (h < 70)  bins['<70']++;
                else if (h < 75)  bins['70-75']++;
                else if (h < 80)  bins['75-80']++;
                else if (h < 85)  bins['80-85']++;
                else if (h < 90)  bins['85-90']++;
                else if (h < 95)  bins['90-95']++;
                else               bins['>95']++;
            });
            charts.humidityDist.data.datasets[0].data = Object.values(bins);
            charts.humidityDist.update('none');
        }

        // Filter and update Misting activity chart based on date range
        if (charts.misting && cachedAnalyticsData.misting_activity) {
            const filteredMisting = cachedAnalyticsData.misting_activity.filter(item => {
                const parts = item.date.split('/');
                const logDay = parseInt(parts[0], 10);
                const logMonth = parseInt(parts[1], 10) - 1;
                const itemDate = new Date(2026, logMonth, logDay);
                return itemDate >= new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate()) &&
                       itemDate <= new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());
            });

            charts.misting.data.labels = filteredMisting.map(m => m.date);
            charts.misting.data.datasets[0].data = filteredMisting.map(m => m.cycles);
            charts.misting.data.datasets[1].data = filteredMisting.map(m => m.on_count);
            charts.misting.update('none');
        }

        // Filter and update Stability trend chart based on date range
        if (charts.stabilityTrend && cachedAnalyticsData.stability_trend) {
            const filteredStability = cachedAnalyticsData.stability_trend.filter(item => {
                const parts = item.date.split('/');
                const logDay = parseInt(parts[0], 10);
                const logMonth = parseInt(parts[1], 10) - 1;
                const itemDate = new Date(2026, logMonth, logDay);
                return itemDate >= new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate()) &&
                       itemDate <= new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());
            });

            charts.stabilityTrend.data.labels = filteredStability.map(s => s.date);
            charts.stabilityTrend.data.datasets[0].data = filteredStability.map(s => s.score);
            charts.stabilityTrend.update('none');
        }

        // Filter and update Event timeline
        const timeline = document.getElementById('analytics-timeline');
        if (timeline && cachedAnalyticsData.events) {
            const filteredEvents = cachedAnalyticsData.events.filter(e => {
                if (!e.datetime) return true;
                const eDate = new Date(e.datetime);
                return eDate >= startDate && eDate <= endDate;
            });

            if (filteredEvents.length > 0) {
                timeline.innerHTML = filteredEvents.map(e => {
                    const dotClass = e.type === 'warning' || e.type === 'alert' ? 'warning' :
                                     e.triggered_by === 'user' ? 'user' : 'system';
                    return `<div class="timeline-item">
                        <div class="timeline-dot ${dotClass}"></div>
                        <div class="timeline-time">${e.time}</div>
                        <div class="timeline-desc">${e.description}</div>
                    </div>`;
                }).join('');
            } else {
                timeline.innerHTML = `<div class="timeline-item">
                    <div class="timeline-dot system"></div>
                    <div class="timeline-time">-</div>
                    <div class="timeline-desc">Tidak ada kejadian dalam rentang ini</div>
                </div>`;
            }
        }
    }

    function applyMetricFilter() {
        const metricSelect = document.getElementById('metric-select');
        if (!metricSelect || !charts.historical) return;

        const val = metricSelect.value;
        if (val === 'humidity') {
            charts.historical.setDatasetVisibility(0, true);
            charts.historical.setDatasetVisibility(1, false);
        } else if (val === 'temperature') {
            charts.historical.setDatasetVisibility(0, false);
            charts.historical.setDatasetVisibility(1, true);
        } else if (val === 'stability') {
            charts.historical.setDatasetVisibility(0, false);
            charts.historical.setDatasetVisibility(1, false);
            const stabilityTrendChart = document.getElementById('stabilityTrendChart');
            if (stabilityTrendChart) {
                stabilityTrendChart.closest('.chart-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            charts.historical.setDatasetVisibility(0, true);
            charts.historical.setDatasetVisibility(1, true);
        }
        charts.historical.update();
    }

    // ─── Stability View Integration ─────────────────────────────

    let stabilityLoaded = false;

    async function fetchStabilityData(period = '4w') {
        if (!ENCLOSURE_ID || typeof API === 'undefined') return;

        const response = await API.getStability(ENCLOSURE_ID, period);
        if (!response.success) return;

        const d = response.data;
        stabilityLoaded = true;

        // Gauge center values
        const gaugeVal = document.getElementById('stab-gauge-value');
        const gaugeLbl = document.getElementById('stab-gauge-label');
        const statusBadge = document.getElementById('stab-status-badge');

        if (d.score) {
            const score = Math.round(d.score.final_score);
            const status = d.score.status;
            const colorClass = score >= 85 ? 'text-green' : score >= 70 ? 'text-blue' : score >= 50 ? 'text-warning' : 'text-red';
            const badgeClass = score >= 85 ? 'stable' : score >= 70 ? 'stable' : score >= 50 ? 'warning' : 'critical';

            if (gaugeVal) { gaugeVal.textContent = score; gaugeVal.className = `score-value ${colorClass}`; }
            if (gaugeLbl) {
                const label = score >= 85 ? 'Sangat Baik' : score >= 70 ? 'Baik' : score >= 50 ? 'Perhatian' : 'Kritis';
                gaugeLbl.textContent = label;
            }
            if (statusBadge) {
                const icon = score >= 70 ? 'ph-check-circle' : 'ph-warning-circle';
                statusBadge.className = `status-badge ${badgeClass}`;
                statusBadge.innerHTML = `<i class="ph ${icon}"></i> Status: ${status}`;
            }

            // Update gauge chart
            if (charts.gauge) {
                charts.gauge.data.datasets[0].data = [score, 100 - score];
                const gaugeColor = score >= 85 ? '#4caf50' : score >= 70 ? '#457b9d' : score >= 50 ? '#e9c46a' : '#e76f51';
                charts.gauge.data.datasets[0].backgroundColor[0] = gaugeColor;
                charts.gauge.update('none');
            }
        }

        // Component cards
        const comp = d.components;

        // Range Compliance
        const rcVal = document.getElementById('stab-rc-value');
        const rcSub = document.getElementById('stab-rc-sub');
        const rcBar = document.getElementById('stab-rc-bar');
        if (rcVal) rcVal.innerHTML = `${Math.round(comp.range_compliance.score)}<span class="unit">%</span>`;
        if (rcSub) rcSub.textContent = comp.range_compliance.label;
        if (rcBar) rcBar.style.width = `${Math.round(comp.range_compliance.score)}%`;

        // Variability
        const varVal = document.getElementById('stab-var-value');
        const varSub = document.getElementById('stab-var-sub');
        const varBar = document.getElementById('stab-var-bar');
        if (varVal) varVal.textContent = comp.variability.label;
        if (varSub) varSub.textContent = `Skor: ${Math.round(comp.variability.score)}/100`;
        if (varBar) varBar.style.width = `${100 - Math.round(comp.variability.score)}%`;  // Inverted: lower is better

        // Stability Duration
        const durVal = document.getElementById('stab-dur-value');
        const durSub = document.getElementById('stab-dur-sub');
        const durBar = document.getElementById('stab-dur-bar');
        if (durVal) durVal.innerHTML = `${comp.stability_duration.hours}<span class="unit">j</span>`;
        if (durSub) durSub.textContent = 'Jam stabil berturut-turut';
        if (durBar) durBar.style.width = `${Math.min(100, Math.round(comp.stability_duration.score))}%`;

        // Fluctuation Penalty
        const penVal = document.getElementById('stab-penalty-value');
        const penSub = document.getElementById('stab-penalty-sub');
        const penBar = document.getElementById('stab-penalty-bar');
        if (penVal) penVal.innerHTML = `-${Math.round(comp.fluctuation_penalty.score)}<span class="unit">pts</span>`;
        if (penSub) penSub.textContent = `${comp.fluctuation_penalty.events} lonjakan terdeteksi`;
        if (penBar) penBar.style.width = `${Math.min(100, Math.round(comp.fluctuation_penalty.score) * 5)}%`;

        // Radar chart
        if (charts.radar) {
            charts.radar.data.datasets[0].data = [
                Math.round(comp.range_compliance.score),
                Math.round(comp.variability.score),
                Math.min(100, Math.round(comp.stability_duration.score)),
                Math.max(0, 100 - Math.round(comp.variability.score) * 0.5), // Konsistensi Suhu (derived)
                Math.max(0, 100 - Math.round(comp.fluctuation_penalty.score) * 3), // Efisiensi Misting (derived)
            ];
            charts.radar.update('none');
        }

        // Stability history chart
        if (charts.stabilityHistory && d.history.length > 0) {
            charts.stabilityHistory.data.labels = d.history.map(h => h.date);
            charts.stabilityHistory.data.datasets[0].data = d.history.map(h => h.score);
            // Min scores (use same data for now, analytics engine will differentiate later)
            if (charts.stabilityHistory.data.datasets[1]) {
                charts.stabilityHistory.data.datasets[1].data = d.history.map(h => Math.max(0, h.score - 10));
            }
            charts.stabilityHistory.update('none');
        }
    }

    // ─── Navigation-Triggered Data Fetch ────────────────────────

    // Hook into the existing navigation system
    if (navLinks.length > 0) {
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                const target = link.getAttribute('data-target');

                if (target === 'analytics' && !analyticsLoaded) {
                    setTimeout(() => fetchAnalyticsData('7d'), 100);
                }
                if (target === 'stability' && !stabilityLoaded) {
                    setTimeout(() => fetchStabilityData('4w'), 100);
                }
            });
        });
    }

    // ─── Period Filter Listeners ─────────────────────────────────
    const historicalFilters = document.querySelectorAll('#historical-period-filters button');
    if (historicalFilters.length > 0) {
        historicalFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                historicalFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const period = btn.getAttribute('data-period');
                fetchAnalyticsData(period, true);
            });
        });
    }

    // Register custom date inputs, metric select, and report export listeners
    const dateStartInput = document.getElementById('date-start');
    const dateEndInput = document.getElementById('date-end');
    const metricSelectInput = document.getElementById('metric-select');
    const exportBtn = document.getElementById('export-report-btn');

    if (dateStartInput && dateEndInput) {
        const handleDateRangeChange = async () => {
            const startVal = dateStartInput.value;
            const endVal = dateEndInput.value;
            if (!startVal || !endVal) return;

            const startDate = new Date(startVal + 'T00:00:00');
            const endDate = new Date(endVal + 'T23:59:59');

            if (startDate > endDate) {
                if (typeof showNotificationToast === 'function') {
                    showNotificationToast('Rentang Tidak Valid', 'Tanggal mulai harus lebih awal dari tanggal selesai.');
                }
                return;
            }

            const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            let fetchPeriod = '24h';
            if (diffDays > 30) {
                fetchPeriod = '90d';
            } else if (diffDays > 7) {
                fetchPeriod = '30d';
            } else if (diffDays > 1) {
                fetchPeriod = '7d';
            }

            // De-activate the quick period filters styling since user is setting custom range
            if (historicalFilters.length > 0) {
                historicalFilters.forEach(btn => btn.classList.remove('active'));
                
                // Highlight matching button if any
                if (diffDays === 7) {
                    const btn7d = document.querySelector('#historical-period-filters button[data-period="7d"]');
                    if (btn7d) btn7d.classList.add('active');
                } else if (diffDays === 30) {
                    const btn30d = document.querySelector('#historical-period-filters button[data-period="30d"]');
                    if (btn30d) btn30d.classList.add('active');
                } else if (diffDays === 90) {
                    const btn90d = document.querySelector('#historical-period-filters button[data-period="90d"]');
                    if (btn90d) btn90d.classList.add('active');
                }
            }

            await fetchAnalyticsData(fetchPeriod, false);
        };

        dateStartInput.addEventListener('change', handleDateRangeChange);
        dateEndInput.addEventListener('change', handleDateRangeChange);
    }

    if (metricSelectInput) {
        metricSelectInput.addEventListener('change', () => {
            applyMetricFilter();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const originalHtml = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> <span>Mengekspor...</span>';
            exportBtn.disabled = true;

            setTimeout(() => {
                exportBtn.innerHTML = originalHtml;
                exportBtn.disabled = false;
                if (typeof showNotificationToast === 'function') {
                    showNotificationToast('Ekspor Berhasil', 'Laporan analitik enclosure berhasil diunduh sebagai PDF.');
                } else {
                    alert('Ekspor Berhasil! Laporan PDF berhasil diunduh.');
                }
            }, 1800);
        });
    }

    const stabilityFilters = document.querySelectorAll('#stability-period-filters button');
    if (stabilityFilters.length > 0) {
        stabilityFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                stabilityFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const period = btn.getAttribute('data-period');
                fetchStabilityData(period);
            });
        });
    }

    // ─── Global State & Synchronization ─────────────────────────
    window.AppState = {
        enclosure: null,
        user: null,
        subscribers: [],
        
        subscribe(callback) {
            this.subscribers.push(callback);
        },
        
        notify() {
            this.subscribers.forEach(cb => cb(this));
        },
        
        setEnclosure(data) {
            this.enclosure = { ...this.enclosure, ...data };
            this.notify();
        },

        setUser(data) {
            this.user = { ...this.user, ...data };
            this.notify();
        }
    };

    // Update DOM when state changes (Optimistic UI Update)
    window.AppState.subscribe((state) => {
        if (state.enclosure && state.enclosure.name) {
            // Update Dashboard top title
            const activeNav = document.querySelector('.nav-links li.active');
            if (activeNav && activeNav.getAttribute('data-target') === 'dashboard') {
                const titleEl = document.getElementById('page-title');
                if (titleEl) titleEl.innerText = state.enclosure.name;
            }
            // Update Sidebar nav text
            const dashboardNavSpan = document.querySelector('li[data-target="dashboard"] span');
            if (dashboardNavSpan) dashboardNavSpan.innerText = state.enclosure.name;
            
            // Update Select Enclosure Page cards if present
            if (state.enclosure.id) {
                const btn = document.querySelector(`.edit-enclosure-btn[data-id="${state.enclosure.id}"]`);
                if (btn) {
                    btn.setAttribute('data-name', state.enclosure.name);
                    const titleH3 = btn.parentElement.querySelector('h3');
                    if (titleH3) titleH3.innerText = state.enclosure.name;
                }
            }
        }
    });

    // ─── Settings Forms Handlers ────────────────────────────────
    
    // User Settings Form
    const userSettingsForm = document.getElementById('user-settings-form');
    if (userSettingsForm) {
        userSettingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('save-user-settings-btn');
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> <span>Menyimpan...</span>';
            btn.disabled = true;

            const inputs = userSettingsForm.querySelectorAll('input');
            const formData = {
                name: inputs[0].value,
                email: inputs[1].value
            };

            try {
                // 1. Save to Backend
                await API.updateUserSettings(formData);
                
                // 2. Optimistic State Update
                window.AppState.setUser(formData);
                
                // 3. UI Feedback
                showNotificationToast('Berhasil', 'Pengaturan akun berhasil disimpan.');
                document.getElementById('user-settings-modal').style.display = 'none';
                
                // Update Sidebar Profile Name
                const userNameEl = document.querySelector('.user-name');
                if (userNameEl) userNameEl.innerText = formData.name;
                
            } catch (err) {
                showNotificationToast('Gagal', 'Terjadi kesalahan saat menyimpan pengaturan.');
            } finally {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        });
    }


    // Apply AI Recommendation from dashboard DSS card
    const applyDashboardRecommendationBtn = document.getElementById('apply-dashboard-recommendation-btn');
    if (applyDashboardRecommendationBtn) {
        applyDashboardRecommendationBtn.addEventListener('click', async () => {
            if (!latestRecommendationId || typeof API === 'undefined') return;

            const originalBtnHtml = applyDashboardRecommendationBtn.innerHTML;
            applyDashboardRecommendationBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Menerapkan...';
            applyDashboardRecommendationBtn.disabled = true;

            try {
                const response = await API.applyRecommendation(latestRecommendationId);
                if (!response.success) throw new Error(response.error || response.message || 'Gagal menerapkan rekomendasi');

                showNotificationToast('Rekomendasi Diterapkan', 'Parameter bottom/top/durasi misting berhasil diperbarui.');
                await fetchDashboardData();
            } catch (err) {
                console.error(err);
                showNotificationToast('Gagal', err.message || 'Terjadi kesalahan saat menerapkan rekomendasi.');
            } finally {
                applyDashboardRecommendationBtn.innerHTML = originalBtnHtml;
                applyDashboardRecommendationBtn.disabled = false;
            }
        });
    }

    // Control Parameter Form: bottom humidity, top humidity, misting duration
    const controlParametersForm = document.getElementById('control-parameters-form');
    if (controlParametersForm) {
        const parameterInputs = [
            document.getElementById('param-bottom-humidity'),
            document.getElementById('param-top-humidity'),
            document.getElementById('param-misting-duration')
        ].filter(Boolean);

        parameterInputs.forEach((input) => {
            input.addEventListener('input', () => {
                controlParametersDirty = true;
            });
        });

        controlParametersForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!ENCLOSURE_ID || typeof API === 'undefined') return;

            const btn = document.getElementById('save-control-parameters-btn');
            const originalBtnHtml = btn.innerHTML;
            const bottomHumidity = parseFloat(document.getElementById('param-bottom-humidity').value);
            const topHumidity = parseFloat(document.getElementById('param-top-humidity').value);
            const duration = parseInt(document.getElementById('param-misting-duration').value, 10);

            if (Number.isNaN(bottomHumidity) || Number.isNaN(topHumidity) || Number.isNaN(duration)) {
                showNotificationToast('Input Tidak Valid', 'Bottom humidity, top humidity, dan durasi wajib diisi.');
                return;
            }

            if (bottomHumidity < 0 || bottomHumidity > 100 || topHumidity < 0 || topHumidity > 100) {
                showNotificationToast('Input Tidak Valid', 'Kelembapan minimum dan maksimum harus berada dalam rentang 0-100%.');
                return;
            }

            if (bottomHumidity >= topHumidity) {
                showNotificationToast('Input Tidak Valid', 'Bottom humidity harus lebih kecil dari top humidity.');
                return;
            }

            btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Menyimpan...';
            btn.disabled = true;
            controlParametersSaving = true;

            try {
                const response = await API.updateParameters(ENCLOSURE_ID, {
                    misting_bottom_threshold: bottomHumidity,
                    misting_top_threshold: topHumidity,
                    misting_duration_seconds: duration,
                    source: 'manual'
                });

                if (!response.success) throw new Error(response.error || response.message || 'Gagal menyimpan parameter');

                controlParametersDirty = false;

                if (response.data && response.data.parameters) {
                    const saved = response.data.parameters;
                    document.getElementById('param-bottom-humidity').value = parseFloat(saved.misting_bottom_threshold).toFixed(1);
                    document.getElementById('param-top-humidity').value = parseFloat(saved.misting_top_threshold).toFixed(1);
                    document.getElementById('param-misting-duration').value = saved.misting_duration_seconds || duration;
                }

                showNotificationToast('Parameter Tersimpan', 'Konfigurasi berhasil disimpan dan siap diambil ESP32.');
                await fetchDashboardData();
            } catch (err) {
                console.error(err);
                showNotificationToast('Gagal', err.message || 'Terjadi kesalahan saat menyimpan parameter.');
            } finally {
                controlParametersSaving = false;
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        });
    }

    // Enclosure Settings Form
    const encSettingsForm = document.getElementById('edit-enclosure-form');
    if (encSettingsForm) {
        encSettingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('save-enclosure-btn');
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> <span>Menyimpan...</span>';
            btn.disabled = true;

            const encId = document.getElementById('edit-enclosure-id').value;
            const encName = document.getElementById('edit-enclosure-name').value;

            try {
                // 1. Save to Backend
                await API.updateEnclosure(encId, { name: encName });
                
                // 2. Optimistic State Update
                window.AppState.setEnclosure({ id: encId, name: encName });
                
                // 3. Refetch Dashboard if active
                if (typeof fetchDashboardData === 'function' && ENCLOSURE_ID == encId) {
                    fetchDashboardData();
                }

                // 4. UI Feedback
                if (typeof showNotificationToast === 'function') {
                    showNotificationToast('Berhasil', 'Nama kandang berhasil diubah.');
                } else {
                    alert('Nama kandang berhasil diubah.');
                }
                document.getElementById('edit-enclosure-modal').style.display = 'none';
                
            } catch (err) {
                if (typeof showNotificationToast === 'function') {
                    showNotificationToast('Gagal', 'Terjadi kesalahan saat menyimpan pengaturan.');
                } else {
                    alert('Gagal menyimpan.');
                }
            } finally {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        });
    }

    // ─── Initialization ──────────────────────────────────────────

    // Init charts first (creates empty chart containers)
    if (document.getElementById('rhRealtimeChart')) {
        initCharts();
    }

    // Start data polling if we're on the dashboard page
    if (ENCLOSURE_ID && document.getElementById('rhRealtimeChart')) {
        fetchDashboardData();
        fetchAnalyticsData('7d');
        fetchStabilityData('4w');
        pollInterval = setInterval(fetchDashboardData, 5000);
    }
});
