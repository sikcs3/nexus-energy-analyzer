<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Grid NOC Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #050810; overflow-x: hidden; min-height: 100vh; color: #fff; }
        .noc-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.5); }
        .live-dot { width: 12px; height: 12px; background-color: #ff3366; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px #ff3366; animation: blink 1s infinite alternate; margin-right: 10px; }
        @keyframes blink { from { opacity: 1; box-shadow: 0 0 15px #ff3366; } to { opacity: 0.4; box-shadow: 0 0 0px #ff3366; } }
        .tower-card { padding: 15px; text-align: center; border-color: rgba(255,255,255,0.05); }
        .tower-value { font-family: 'Space Mono', monospace; font-size: 2rem; font-weight: bold; margin: 5px 0; }
        .status-stable { color: var(--accent-neon); text-shadow: 0 0 10px rgba(0,229,160,0.5); }
        .status-warning { color: #ffab00; text-shadow: 0 0 10px rgba(255,171,0,0.5); }
        .status-critical { color: #ff3366; text-shadow: 0 0 10px rgba(255,51,102,0.5); }
        .chart-container { height: 350px; padding: 20px; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 12px; margin-top: 20px; }
        .pie-chart-container { height: 180px; position: relative; margin-top: 15px;}
        .event-feed { font-family: 'Space Mono', monospace; font-size: 0.95rem; color: var(--accent-neon); padding: 10px; height: 150px; overflow-y: hidden; text-align: left; background: rgba(0,0,0,0.2); border-radius: 8px;}
        .feed-entry { margin-bottom: 8px; opacity: 0.9; }
        .feed-entry.alert { color: #ff3366; }
        .control-panel { background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 30px; display: flex; gap: 15px; align-items: center; }
        .control-panel select, .control-panel input { background: #0f172a; color: #fff; border: 1px solid #334155; padding: 8px 12px; border-radius: 6px; }
        .forecaster { text-align: right; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 20px; margin-left: 20px; }
        .forecast-value { font-family: 'Space Mono', monospace; font-size: 1.5rem; font-weight: bold; transition: color 0.3s ease; }
        .forecast-saving { color: #00e5a0; text-shadow: 0 0 10px rgba(0,229,160,0.4); }
        .forecast-costing { color: #ff3366; text-shadow: 0 0 10px rgba(255,51,102,0.4); }
    </style>
</head>
<body>
    <div class="noc-header max-w-100">
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary me-3"><i class="ph ph-arrow-left"></i> Back to Main</a>
            <h3 class="d-inline-block m-0"><i class="ph-fill ph-activity" style="color: #ff3366;"></i> NOC Dashboard</h3>
        </div>
        <div class="d-flex align-items-center">
            <span class="live-dot"></span> <span class="mono fw-bold" style="color: #ff3366; letter-spacing: 2px;">LIVE TELEMETRY</span>
            
            <div class="forecaster text-end ms-4">
                <span class="text-muted d-block" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Global Load</span>
                <span class="mono fw-bold fs-4 text-white" id="global-load">0.00 MW</span>
            </div>

            <div class="forecaster text-end">
                <span class="text-muted d-block" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Monthly Forecaster (Savings vs Base)</span>
                <span class="forecast-value" id="rupee-forecast">₹ 0</span>
            </div>
        </div>
    </div>

    <!-- Simulation Control Panel -->
    <div class="control-panel">
        <label class="text-muted small text-uppercase fw-bold"><i class="ph ph-buildings"></i> Live Towers Count:</label>
        <input type="number" id="tower-count" value="4" min="1" max="12" style="width: 80px;">
        
        <label class="text-muted small text-uppercase fw-bold ms-3"><i class="ph ph-sun-horizon"></i> Scenario Simulation:</label>
        <select id="scenario-selector">
            <option value="standard">Standard Load Condition</option>
            <option value="peak">Peak Summer (Heavy Load)</option>
            <option value="night">Winter Night (Low Load)</option>
            <option value="eco">Eco Mode (Managed Load)</option>
        </select>
        
        <button class="btn btn-primary ms-3 btn-sm fw-bold" onclick="applySettings()"><i class="ph ph-arrows-clockwise"></i> Apply Variables</button>
    </div>

    <div class="container-fluid p-4 max-w-100 mb-5">
        <!-- Towers Row -->
        <div class="row g-4 mb-4 justify-content-center" id="towers-container">
            <!-- Rendered by JS -->
        </div>

        <!-- Main Chart -->
        <div class="chart-container">
            <canvas id="liveChart"></canvas>
        </div>

        <!-- Live Events -->
        <div class="mt-4 card p-4 border-0" style="background: rgba(0, 0, 0, 0.4);">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="text-uppercase m-0" style="color: var(--text-muted); letter-spacing: 1px;"><i class="ph ph-terminal"></i> Live Incident Feed</h5>
            </div>
            <div class="event-feed" id="event-feed">
                <div class="text-muted p-2">Waiting for telemetry connection...</div>
            </div>
        </div>
    </div>

    <!-- Deep-Dive Tower Modal -->
    <div class="modal fade" id="towerModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--bg-main); border: 1px solid var(--border-color);">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title text-white" id="modalTowerName"><i class="ph-fill ph-buildings text-accent"></i> Tower Name</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body mt-2">
            <div class="text-center mb-4">
                <div class="mono fs-1 fw-bold text-accent" id="modalTowerLoad">0 kW</div>
                <div class="text-muted small" style="letter-spacing: 1px;">CURRENT LOAD SNAPSHOT</div>
            </div>
            
            <h6 class="text-muted text-uppercase small fw-bold mb-3 border-bottom border-secondary pb-2">Deep-Dive Appliance Matrix</h6>
            <div id="modalApplianceMatrix">
                <!-- Injected via JS -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
        const ctx = document.getElementById('liveChart').getContext('2d');
        const maxDataPoints = 40; 
        
        let liveChart;
        let pieCharts = [];
        
        // Colors array to handle dynamic N towers
        const towerColors = [
            '#00e5a0', '#ffab00', '#00d4ff', '#b366ff', '#ff3366', 
            '#ffda44', '#38ff93', '#4a8dff', '#ff8433', '#cd41ff',
            '#ffffff', '#808080'
        ];

        let state = {
            towers: 4,
            scenario: 'standard'
        };

        function initLineChart() {
            if(liveChart) liveChart.destroy();

            let datasets = [];
            for(let i=0; i<state.towers; i++) {
                datasets.push({
                    label: `Tower ${i+1}`,
                    borderColor: towerColors[i % towerColors.length],
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    data: Array(maxDataPoints).fill(0)
                });
            }

            liveChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Array(maxDataPoints).fill(''),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 }, 
                    plugins: { legend: { labels: { color: '#e2e8f0', font: { family: 'Inter', size: 12 } } } },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.02)' }, ticks: { display: false } },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8', font: { family: 'Space Mono' } },
                            suggestedMin: 100, suggestedMax: 1000,
                            title: { display: true, text: 'Kilowatts (kW)', color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        const feedEl = document.getElementById('event-feed');
        const globalLoadEl = document.getElementById('global-load');
        const towersEl = document.getElementById('towers-container');
        const forecastEl = document.getElementById('rupee-forecast');
        let feedInit = false;

        function logEvent(eventString, timestamp) {
            if(eventString.includes("No anomalies")) return; 
            if(!feedInit) { feedEl.innerHTML = ''; feedInit = true; }

            const isAlert = eventString.includes("(+");
            const colorClass = isAlert ? "alert" : "";
            const el = document.createElement("div");
            el.className = `feed-entry ${colorClass}`;
            el.innerHTML = `[${timestamp}] <i class="ph ph-caret-right"></i> ${eventString}`;
            
            feedEl.prepend(el);
            if(feedEl.children.length > 5) feedEl.lastChild.remove();
        }

        function calculateForecaster(totalKw, scenario) {
            // Assume base load is around 400 kW per tower
            let baselineKw = 400 * state.towers; 
            let diff = baselineKw - totalKw; // Positive if saving, negative if costing more
            
            // Rate is ₹8.5 per kwh. Calculate monthly projection
            // Monthly projection = diff kW * 8.5 INR * 24 hours * 30 days
            let monthlyDiff = diff * 8.5 * 24 * 30;
            
            if (monthlyDiff >= 0) {
                // SAVING
                forecastEl.className = "forecast-value forecast-saving";
                forecastEl.innerText = "+ ₹" + Math.abs(monthlyDiff).toLocaleString('en-IN', {maximumFractionDigits:0});
            } else {
                // COSTING EXTRA
                forecastEl.className = "forecast-value forecast-costing";
                forecastEl.innerText = "- ₹" + Math.abs(monthlyDiff).toLocaleString('en-IN', {maximumFractionDigits:0});
            }
        }

        function renderTowersUI(towersData) {
            // Ensure DOM elements match tower count. Re-render only if count doesn't match
            if(towersEl.children.length !== towersData.length) {
                // Destroy old pie charts
                pieCharts.forEach(c => c.destroy());
                pieCharts = [];

                // Adjust column class based on how many towers to fit properly across screen
                let colClass = 'col-md-3';
                if (towersData.length > 4) colClass = 'col-lg-2 col-md-3 col-sm-6';
                if (towersData.length > 8) colClass = 'col-lg-2 col-md-4 col-sm-6';

                towersEl.innerHTML = towersData.map((t, index) => {
                    return `
                    <div class="${colClass}">
                        <div class="card tower-card h-100" id="card-t${index}" style="background: rgba(0,229,160,0.05); cursor: pointer; transition: transform 0.2s;" onclick="openTowerReport(${index})" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            <h6 class="text-uppercase m-0" style="color:var(--text-muted);">${t.name}</h6>
                            <div class="tower-value status-stable" id="val-t${index}">0 <span style="font-size:0.9rem;color:var(--text-muted);">kW</span></div>
                            <div class="text-muted mb-2" style="font-size:0.75rem; letter-spacing:1px;">STATUS: <span id="stat-t${index}" class="text-uppercase fw-bold" style="color:var(--accent-neon)">STABLE</span></div>
                            <div class="pie-chart-container">
                                <canvas id="pie-t${index}"></canvas>
                            </div>
                        </div>
                    </div>`;
                }).join('');

                // Initialize Pie Charts newly
                towersData.forEach((t, index) => {
                    const canvas = document.getElementById(`pie-t${index}`);
                    const ctxPie = canvas.getContext('2d');
                    
                    const chart = new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: ['HVAC', 'Lighting', 'Elevators', 'Flats'],
                            datasets: [{
                                data: [t.appliances.hvac, t.appliances.lighting, t.appliances.elevators, t.appliances.flats],
                                backgroundColor: ['#00d4ff', '#ffda44', '#b366ff', '#00e5a0'],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            animation: { duration: 500 }, // Smooth pie animation
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: { label: function(context) { return context.label + ': ' + context.parsed + ' kW'; } },
                                    intersect: false
                                }
                            },
                            cutout: '65%'
                        }
                    });
                    pieCharts.push(chart);
                });
            }

            // Update Values dynamically without rebuilding DOM
            towersData.forEach((t, index) => {
                const card = document.getElementById(`card-t${index}`);
                const val = document.getElementById(`val-t${index}`);
                const stat = document.getElementById(`stat-t${index}`);
                
                let classCol = 'status-stable';
                let shadowColor = 'rgba(0,229,160,0.05)';
                let statColor = 'var(--accent-neon)';
                
                if(t.status === 'warning') { classCol = 'status-warning'; shadowColor = 'rgba(255,171,0,0.1)'; statColor = '#ffab00'; }
                if(t.status === 'critical') { classCol = 'status-critical'; shadowColor = 'rgba(255,51,102,0.1)'; statColor = '#ff3366'; }

                card.style.background = shadowColor;
                val.className = `tower-value ${classCol}`;
                val.innerHTML = `${t.kw} <span style="font-size:0.9rem;color:var(--text-muted);">kW</span>`;
                stat.style.color = statColor;
                stat.innerText = t.status;

                // Update Pie Chart Data
                if(pieCharts[index]) {
                    pieCharts[index].data.datasets[0].data = [t.appliances.hvac, t.appliances.lighting, t.appliances.elevators, t.appliances.flats];
                    pieCharts[index].update();
                }
            });
        }

        function updateDashboard() {
            fetch(`api_live_towers.php?count=${state.towers}&scenario=${state.scenario}`)
                .then(res => res.json())
                .then(data => {
                    state.latestData = data.towers;
                    globalLoadEl.innerText = `${data.total_mw} MW`;

                    // Handle Towers Render & Values
                    renderTowersUI(data.towers);

                    // Update Line Chart dynamically
                    if (liveChart && data.towers.length === state.towers) {
                        liveChart.data.datasets.forEach((dataset, index) => {
                            if (data.towers[index]) {
                                dataset.data.push(data.towers[index].kw);
                                dataset.data.shift();
                            }
                        });
                        liveChart.update();
                    }

                    // Compute Forecaster
                    let totalKw = data.towers.reduce((sum, t) => sum + parseInt(t.kw), 0);
                    calculateForecaster(totalKw, state.scenario);

                    // Log Feed Event
                    if(data.event) logEvent(data.event, data.timestamp.split(" ")[1]);
                })
                .catch(err => console.error("API error", err));
        }

        function applySettings() {
            let numTowers = parseInt(document.getElementById('tower-count').value) || 4;
            if(numTowers < 1) numTowers = 1;
            if(numTowers > 12) numTowers = 12; // Cap visual limitations
            document.getElementById('tower-count').value = numTowers;

            state.towers = numTowers;
            state.scenario = document.getElementById('scenario-selector').value;
            
            // Re-init the chart for new towers array length
            initLineChart();

            // Clear old UI to force re-render
            towersEl.innerHTML = '<div class="text-center w-100 mt-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted">Re-synchronizing grid telemetry for new parameters...</p></div>';
            
            // Re-fetch immediately
            updateDashboard();
        }

        let towerModalInstance;
        function openTowerReport(index) {
            if(!state.latestData || !state.latestData[index]) return;
            const tower = state.latestData[index];
            
            document.getElementById('modalTowerName').innerText = tower.name;
            document.getElementById('modalTowerLoad').innerText = tower.kw + " kW";

            // Appliance Matrix array
            const matrixContainer = document.getElementById('modalApplianceMatrix');
            const apps = [
                { name: 'HVAC Systems', kw: tower.appliances.hvac },
                { name: 'Lighting Arrays', kw: tower.appliances.lighting },
                { name: 'Elevator Banks', kw: tower.appliances.elevators },
                { name: 'Individual Flats', kw: tower.appliances.flats }
            ];

            let html = '';
            apps.forEach(a => {
                const percent = (a.kw / tower.kw) * 100;
                
                // Normal UI parameters
                let barColor = "bg-primary";
                let textCol = "text-white";
                let warningIcon = "";
                
                // Anomaly checking logic (simulated thresholds)
                if (a.name === 'HVAC Systems' && percent > 45) { 
                    barColor = "bg-danger"; textCol = "text-danger fw-bold"; 
                    warningIcon = '<i class="ph-fill ph-warning"></i> Anomaly: Sustained Overdraw'; 
                } else if (a.name === 'Lighting Arrays' && percent > 25) { 
                    barColor = "bg-warning"; textCol = "text-warning fw-bold"; 
                     warningIcon = '<i class="ph-fill ph-warning"></i> Daylighting Inefficient'; 
                } else if (a.name === 'Elevator Banks' && percent > 25) { 
                    barColor = "bg-warning"; textCol = "text-warning fw-bold"; 
                    warningIcon = '<i class="ph-fill ph-warning"></i> Motor Bearing Load High'; 
                }

                html += `
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="${textCol}">${a.name}</span>
                        <span class="mono">${a.kw} kW (<span class="${textCol}">${Math.round(percent)}%</span>)</span>
                    </div>
                    <div class="progress mb-1" style="height: 6px; background-color: rgba(255,255,255,0.1);">
                        <div class="progress-bar ${barColor}" role="progressbar" style="width: ${percent}%;"></div>
                    </div>
                    ${warningIcon ? `<div class="small text-end ${textCol}" style="font-size: 0.75rem; letter-spacing: 0.5px;">${warningIcon}</div>` : ''}
                </div>`;
            });

            matrixContainer.innerHTML = html;

            if(!towerModalInstance) {
                towerModalInstance = new bootstrap.Modal(document.getElementById('towerModal'));
            }
            towerModalInstance.show();
        }

        // Init routine
        initLineChart();
        setInterval(updateDashboard, 1500);
        updateDashboard(); 
    </script>
</body>
</html>
