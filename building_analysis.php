<?php
include 'db.php';

// If table doesn't exist yet, we avoid crashing
$val = $conn->query("SHOW TABLES LIKE 'building_appliances'");
if ($val->num_rows == 0) {
    die("<div style='color:white; font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Table Missing</h2><p>Please run the provided SQL to create 'building_appliances' first!</p></div>");
}

$result = $conn->query("SELECT * FROM building_appliances");
$appliances = [];
$total_units = 0;
$building_units = [];
$apt_units = [];

while ($row = $result->fetch_assoc()) {
    $monthly_units = ($row['watts'] * $row['hours'] * $row['quantity'] * 30) / 1000;
    $row['monthly_units'] = $monthly_units;
    $total_units += $monthly_units;
    $appliances[] = $row;

    // Aggregate by Building
    $b_name = $row['building_name'];
    if (!isset($building_units[$b_name])) $building_units[$b_name] = 0;
    $building_units[$b_name] += $monthly_units;

    // Aggregate by Apartment
    $apt_key = "Tower " . $b_name . " Apt " . $row['apartment_number'];
    if (!isset($apt_units[$apt_key])) $apt_units[$apt_key] = 0;
    $apt_units[$apt_key] += $monthly_units;
}

// Find Highest Consuming Building
arsort($building_units);
$max_building = key($building_units) ?? "None";
$max_b_units = current($building_units) ? current($building_units) : 0;

// Find Highest Consuming Apartment
arsort($apt_units);
$max_apt = key($apt_units) ?? "None";
$max_apt_units = current($apt_units) ? current($apt_units) : 0;

// Grid Cost Estimation (Blended Rate e.g., ₹6/unit commercial approximation)
$grid_cost = $total_units * 6;

// Insights Generation
$insights = [];
if (!empty($building_units)) {
    $avg_b = $total_units / count($building_units);
    foreach ($building_units as $b => $u) {
        if ($u > $avg_b * 1.5) {
            $insights[] = "<strong>Building $b</strong> is consuming 50% more power than the grid average. Needs immediate infrastructure audit.";
        }
    }
}
if (!empty($apt_units)) {
    $insights[] = "<strong>$max_apt</strong> is the highest consuming unit ($max_apt_units units). Send energy efficiency notice.";
}
if ($total_units == 0) {
    $insights[] = "Grid is inactive or no data has been populated.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grid Analysis | Nexus Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

<div class="container py-5 animate-slide-down">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="building_dashboard.php" class="btn btn-outline-primary" style="border-color:var(--accent-blue); color:var(--accent-blue);"><i class="ph ph-arrow-left"></i> Grid Dashboard</a>
        <button id="download-pdf" class="btn btn-primary export-btn"><i class="ph ph-file-pdf"></i> Export Grid Report</button>
    </div>

    <div id="report-content" class="p-4" style="background: var(--bg-main); border-radius: 12px; border: 1px solid var(--border-color);">
        
        <div class="text-center mb-5">
            <h2 class="text-white"><i class="ph-fill ph-chart-polar text-blue"></i> Grid Performance Report</h2>
            <p class="text-muted">Generated on <?php echo date("F j, Y"); ?></p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(0, 212, 255, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-lightning text-blue" style="font-size: 2rem;"></i>
                        <div class="stat-value text-blue"><?php echo number_format($total_units, 1); ?></div>
                        <div class="stat-label">Total Units Driven</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(255, 171, 0, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-currency-inr text-warning" style="font-size: 2rem;"></i>
                        <div class="stat-value text-warning">₹<?php echo number_format($grid_cost, 2); ?></div>
                        <div class="stat-label">Est. Commercial Cost</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(255, 94, 94, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-buildings text-danger" style="font-size: 2rem;"></i>
                        <div class="stat-value text-danger" style="font-size: 1.4rem; margin-top: 15px;"><?php echo $max_building; ?></div>
                        <div class="stat-label">Highest Load Building</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center" style="border-color: rgba(0, 229, 160, 0.3);">
                    <div class="card-body">
                        <i class="ph ph-house-line text-accent" style="font-size: 2rem;"></i>
                        <div class="stat-value text-accent" style="font-size: 1.4rem; margin-top: 15px;"><?php echo explode(" - Apt ", $max_apt)[1] ?? $max_apt; ?></div>
                        <div class="stat-label">Highest Load Unit</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-12">
                <!-- AI Insights -->
                <div class="card shadow mb-4" style="background: rgba(0, 212, 255, 0.05); border-color: rgba(0, 212, 255, 0.2);">
                    <div class="card-header border-0 pb-0 text-blue">
                        <i class="ph ph-lightbulb"></i> Grid Analyst Insights
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled m-0" style="color: var(--text-main);">
                            <?php foreach($insights as $rec): ?>
                                <li class="mb-3 d-flex gap-2">
                                    <i class="ph-fill ph-check-circle text-blue mt-1"></i>
                                    <span><?php echo $rec; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <!-- Chart 1: Units per apartment in buildings -->
            <div class="col-lg-6">
                <div class="card shadow h-100">
                    <div class="card-header"><i class="ph ph-chart-pie-slice text-blue"></i> Units per Apartment</div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <p class="text-muted small text-center mb-0">Total % split among apartments across the Grid</p>
                        <div style="position: relative; height:300px; width:100%">
                            <canvas id="aptChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Appliances per apartment in buildings -->
            <div class="col-lg-6">
                <div class="card shadow h-100">
                    <div class="card-header"><i class="ph ph-chart-donut text-accent"></i> Units for Appliances per Apartment</div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <p class="text-muted small text-center mb-0">Appliance load distributions globally</p>
                        <div style="position: relative; height:300px; width:100%">
                            <canvas id="applianceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Chart 1: Apartment Pie Chart
    const ctxApt = document.getElementById('aptChart');
    if (ctxApt) {
        new Chart(ctxApt, {
            type: 'pie',
            data: {
                labels: [<?php foreach($apt_units as $a => $u) {
                    $pct = ($total_units > 0) ? round(($u / $total_units) * 100, 1) : 0;
                    echo '"'.$a.' ('.$pct.'%)",';
                } ?>],
                datasets: [{
                    data: [<?php foreach($apt_units as $a => $u) echo $u.','; ?>],
                    backgroundColor: [
                        '#00d4ff', '#00e5a0', '#ff5e5e', '#ffab00', 
                        '#9b51e0', '#2f80ed', '#eb5757', '#f2c94c'
                    ],
                    borderWidth: 1,
                    borderColor: '#0b0f19',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e2e8f0', padding: 15, font: { family: "'Inter', sans-serif", size: 11 } }
                    }
                }
            }
        });
    }

    // Chart 2: Appliance Doughnut Chart
    const ctxApp = document.getElementById('applianceChart');
    if (ctxApp) {
        new Chart(ctxApp, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($appliances as $app) {
                    $label = $app['building_name'] . " Apt " . $app['apartment_number'] . " : " . $app['name'];
                    $pct = ($total_units > 0) ? round(($app['monthly_units'] / $total_units) * 100, 1) : 0;
                    echo '"'.$label.' ('.$pct.'%)",';
                } ?>],
                datasets: [{
                    data: [<?php foreach($appliances as $app) echo $app['monthly_units'].','; ?>],
                    backgroundColor: [
                        '#ff5e5e', '#ffab00', '#9b51e0', '#2f80ed', 
                        '#00d4ff', '#00e5a0', '#eb5757', '#f2c94c',
                        '#f0932b', '#22a6b3', '#be2edd', '#4834d4'
                    ],
                    borderWidth: 1,
                    borderColor: '#0b0f19',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right', // Put on the right to fit more distinct appliance rows
                        labels: { color: '#e2e8f0', padding: 10, boxWidth: 12, font: { family: "'Inter', sans-serif", size: 10 } }
                    }
                }
            }
        });
    }

    document.getElementById('download-pdf').addEventListener('click', function() {
        const element = document.getElementById('report-content');
        const opt = {
            margin:       10,
            filename:     'Nexus_Grid_Report.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, backgroundColor: '#0b0f19' },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
</script>

</body>
</html>
