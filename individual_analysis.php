<?php
include 'db.php';

$result = $conn->query("SELECT * FROM appliances");
$appliances = [];
$total_units = 0;

// Calculate Units per Appliance
while ($row = $result->fetch_assoc()) {
    $monthly_units = ($row['watts'] * $row['hours'] * $row['quantity'] * 30) / 1000;
    $row['monthly_units'] = $monthly_units;
    $total_units += $monthly_units;
    $appliances[] = $row;
}

// Calculate Bill (Slab Tariff Logic)
// 0-100 units: ₹3 | 101-300 units: ₹5 | >300 units: ₹8
$bill = 0;
$temp_units = $total_units;

if ($temp_units > 300) {
    $bill += ($temp_units - 300) * 8;
    $temp_units = 300;
}
if ($temp_units > 100) {
    $bill += ($temp_units - 100) * 5;
    $temp_units = 100;
}
$bill += $temp_units * 3;

// Find Highest Consumer
$max_appliance = "None";
$max_val = 0;
foreach ($appliances as $app) {
    if ($app['monthly_units'] > $max_val) {
        $max_val = $app['monthly_units'];
        $max_appliance = $app['name'];
    }
}

// Eco-Score & Badges
$eco_score = 100;
$badge_css = "badge-great";
$badge_text = "🌱 Eco Warrior";

if ($total_units > 400) {
    $eco_score = max(0, 100 - (($total_units - 400) * 0.2));
    $badge_css = "badge-danger";
    $badge_text = "⚠️ Heavy Consumer";
} elseif ($total_units > 200) {
    $eco_score = 100 - (($total_units - 200) * 0.1);
    $badge_css = "badge-warning";
    $badge_text = "⚡ Average User";
}

// Smart Recommendations Engine
$recommendations = [];
foreach ($appliances as $app) {
    $name = strtolower($app['name']);
    $units = $app['monthly_units'];

    if (strpos($name, 'ac') !== false || strpos($name, 'conditioner') !== false) {
        $recommendations[] = "<strong>{$app['name']}</strong>: Frequent AC usage detected ($units units). Upgrade to a 5-Star Inverter AC to save up to 25% on this appliance's energy cost.";
    }
    if (strpos($name, 'heater') !== false || strpos($name, 'geyser') !== false) {
        $recommendations[] = "<strong>{$app['name']}</strong>: Heating appliances draw massive power. Lowering the thermostat by a few degrees or using solar heaters can cut costs.";
    }
    if (strpos($name, 'fridge') !== false || strpos($name, 'refrigerator') !== false) {
        if ($units > 50) {
            $recommendations[] = "<strong>{$app['name']}</strong>: Refrigerator consumption is high. Ensure it's away from direct sunlight and coils are clean.";
        }
    }
    if ($units > 100 && strpos($name, 'ac') === false && strpos($name, 'heater') === false) {
        $recommendations[] = "<strong>{$app['name']}</strong> is consuming a massive $units units alone. Consider limiting usage or finding an energy-efficient alternative.";
    }
}
if (empty($recommendations)) {
    $recommendations[] = "Your consumption looks optimized based on current appliances. Keep unplugging devices on standby!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analysis Report | Nexus Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

<div class="container py-5 animate-slide-down">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="individual_dashboard.php" class="btn btn-outline-primary"><i class="ph ph-arrow-left"></i> Dashboard</a>
        <button id="download-pdf" class="btn btn-primary export-btn"><i class="ph ph-file-pdf"></i> Export PDF Report</button>
    </div>

    <!-- The container that will be exported to PDF -->
    <div id="report-content" class="p-4" style="background: var(--bg-main); border-radius: 12px;">
        
        <div class="text-center mb-5">
            <h2 class="text-white"><i class="ph-fill ph-chart-bar text-accent"></i> Energy Performance Report</h2>
            <p class="text-muted">Generated on <?php echo date("F j, Y"); ?></p>
        </div>

        <!-- Top Metrics -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(0, 212, 255, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-lightning text-blue" style="font-size: 2rem;"></i>
                        <div class="stat-value text-blue"><?php echo number_format($total_units, 1); ?></div>
                        <div class="stat-label">Total Units (30 Days)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(0, 229, 160, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-currency-inr text-accent" style="font-size: 2rem;"></i>
                        <div class="stat-value text-accent">₹<?php echo number_format($bill, 2); ?></div>
                        <div class="stat-label">Estimated Bill</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(255, 94, 94, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-fire text-danger" style="font-size: 2rem;"></i>
                        <div class="stat-value text-danger" style="font-size: 1.8rem; margin-top: 10px;"><?php echo strlen($max_appliance) > 12 ? substr($max_appliance,0,10).".." : $max_appliance; ?></div>
                        <div class="stat-label">Highest Consumer</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="ph ph-leaf text-white" style="font-size: 2rem;"></i>
                        <div class="stat-value text-white"><?php echo round($eco_score); ?><span style="font-size: 1rem;">/100</span></div>
                        <div class="mt-2"><span class="eco-badge <?php echo $badge_css; ?>"><?php echo $badge_text; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Breakdown Table & Recommendations -->
            <div class="col-lg-7">
                <div class="card mb-4 shadow">
                    <div class="card-header"><i class="ph ph-list-numbers text-accent"></i> Appliance Breakdown</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Appliance</th>
                                        <th>Units / Month</th>
                                        <th style="width: 40%;">Contribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appliances as $app): 
                                        $percent = ($total_units > 0) ? ($app['monthly_units'] / $total_units) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $app['name']; ?></td>
                                        <td class="mono"><?php echo number_format($app['monthly_units'], 1); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1">
                                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
                                                </div>
                                                <span class="mono small text-muted"><?php echo round($percent, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($appliances)) echo "<tr><td colspan='3' class='text-center text-muted py-4'>No data available.</td></tr>"; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- AI Recommendations -->
                <div class="card shadow" style="background: rgba(0, 212, 255, 0.05); border-color: rgba(0, 212, 255, 0.2);">
                    <div class="card-header border-0 pb-0 text-blue">
                        <i class="ph ph-lightbulb"></i> Smart Insights & Recommendations
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled m-0" style="color: var(--text-main);">
                            <?php foreach($recommendations as $rec): ?>
                                <li class="mb-3 d-flex gap-2">
                                    <i class="ph-fill ph-check-circle text-accent mt-1"></i>
                                    <span><?php echo $rec; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="col-lg-5">
                <div class="card shadow h-100">
                    <div class="card-header"><i class="ph ph-chart-donut text-accent"></i> Consumption Chart</div>
                    <div class="card-body d-flex flex-column align-items-center">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="energyChart"></canvas>
                        </div>
                        <!-- Limitless Scrollable Custom Legend -->
                        <div id="custom-legend" class="mt-4 w-100" style="max-height: 200px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--border-color);"></div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End report content -->
</div>

<script>
    // Chart initialization
    const ctx = document.getElementById('energyChart');
    if (ctx) {
        const energyChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($appliances as $a) {
                    $percent = ($total_units > 0) ? round(($a['monthly_units'] / $total_units) * 100, 1) : 0;
                    echo '"'.$a['name'].' ('.$percent.'%)",'; 
                } ?>],
                datasets: [{
                    data: [<?php foreach($appliances as $a) echo $a['monthly_units'].','; ?>],
                    backgroundColor: [
                        '#00e5a0', '#00d4ff', '#ff5e5e', '#ffab00', 
                        '#9b51e0', '#2f80ed', '#eb5757', '#f2c94c'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false // Disabled native legend so it doesn't crush the pie chart
                    }
                }
            }
        });

        // Build Custom HTML Legend
        const labels = energyChartInstance.data.labels;
        const colors = energyChartInstance.data.datasets[0].backgroundColor;
        let legendHTML = '<div class="d-flex flex-column gap-2">';
        labels.forEach((label, i) => {
            legendHTML += `
                <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.95rem;">
                    <span class="d-flex align-items-center">
                        <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:${colors[i]}; margin-right:10px;"></span>
                        ${label}
                    </span>
                </div>`;
        });
        legendHTML += '</div>';
        document.getElementById('custom-legend').innerHTML = legendHTML;
    }

    // PDF Export function
    document.getElementById('download-pdf').addEventListener('click', function() {
        const element = document.getElementById('report-content');
        
        // Temporarily adjust styles for better PDF look
        const opt = {
            margin:       [15, 10, 15, 10], // Top, Right, Bottom, Left margins
            filename:     'Nexus_Energy_Report.pdf',
            image:        { type: 'jpeg', quality: 1.0 },
            html2canvas:  { 
                scale: 2, 
                backgroundColor: '#0b0f19',
                scrollY: 0,
                windowWidth: 1400, // Forces the layout to render as if on a desktop monitor
                windowHeight: element.scrollHeight + 100 // Ensures the lowest bottom bounds are fully captured
            },
            jsPDF:        { unit: 'mm', format: 'a3', orientation: 'landscape' },
            pagebreak:    { mode: ['css', 'legacy'] }
        };

        // We temporarily hide the download button to prevent it showing up weird contextually, 
        // but it's outside 'report-content' anyway.
        html2pdf().set(opt).from(element).save();
    });
</script>

</body>
</html>