<?php
include 'db.php';

// Handle Form Submission for building_appliances
if (isset($_POST['add'])) {
    $b_name = $_POST['building_name'];
    $apt    = $_POST['apartment_number'];
    $name   = $_POST['name'];
    $watts  = $_POST['watts'];
    $hours  = $_POST['hours'];
    $qty    = $_POST['qty'];

    // If table doesn't exist yet, handle gracefully (Ideally the user runs the SQL schema provided, but using prepare stmt is safer)
    $stmt = $conn->prepare("INSERT INTO building_appliances (building_name, apartment_number, name, watts, hours, quantity) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssidi", $b_name, $apt, $name, $watts, $hours, $qty);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: building_dashboard.php");
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM building_appliances WHERE id=$id");
    header("Location: building_dashboard.php");
}

// Stats logic
$stats = ['count' => 0, 'total_load' => 0, 'buildings' => 0];
$res = $conn->query("SELECT COUNT(*) as count, SUM(watts*quantity) as total_load, COUNT(DISTINCT building_name) as buildings FROM building_appliances");
if ($res) {
    $stats = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Building Dashboard - Nexus Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

<div class="container py-5 animate-slide-down">
    
    <div class="app-header mb-4 border-0 pb-0 d-flex justify-content-between align-items-center text-start">
        <div>
            <h1 class="m-0" style="font-size: 2rem;"><i class="ph-fill ph-buildings text-blue"></i> Nexus <span class="text-blue">Building Grid</span></h1>
            <p class="text-muted mt-2">Manage infrastructure and electrical loads across multi-tenant properties.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="ph ph-swap"></i> Switch Mode</a>
    </div>
    
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-3 flex-wrap">
            <div class="px-3 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px; border: 1px solid var(--border-color);">
                <span class="text-muted small">Properties:</span> <strong class="text-white ml-2"><?php echo $stats['buildings'] ?? 0; ?></strong>
            </div>
            <div class="px-3 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px; border: 1px solid var(--border-color);">
                <span class="text-muted small">Devices:</span> <strong class="text-white ml-2"><?php echo $stats['count'] ?? 0; ?></strong>
            </div>
            <div class="px-3 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px; border: 1px solid var(--border-color);">
                <span class="text-muted small">Grid Load:</span> <strong class="text-blue ml-2"><?php echo number_format(($stats['total_load'] ?? 0)/1000, 2); ?> kW</strong>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="building_upload.php" class="btn btn-secondary"><i class="ph ph-upload-simple"></i> Bulk Import</a>
            <a href="building_analysis.php" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-blue), #0077ff);"><i class="ph ph-chart-pie-slice"></i> Grid Analysis</a>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- Add Appliance Form -->
        <div class="col-lg-4">
            <div class="card h-100" style="border-color: rgba(0, 212, 255, 0.2);">
                <div class="card-header d-flex align-items-center gap-2 text-blue">
                    <i class="ph ph-plus-circle"></i> Register Device to Grid
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>Building Name</label>
                                <input type="text" name="building_name" class="form-control" placeholder="A1, Block B" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>Apt / Unit #</label>
                                <input type="text" name="apartment_number" class="form-control" placeholder="101, 5B" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Appliance Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Master AC" required>
                        </div>
                        <div class="mb-3">
                            <label>Power Draw (Watts)</label>
                            <div class="input-group">
                                <input type="number" name="watts" class="form-control" placeholder="1500" required>
                                <span class="input-group-text bg-transparent text-muted" style="border-color: var(--border-color);">W</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-4">
                                <label>Daily Usage</label>
                                <input type="number" step="0.1" name="hours" class="form-control" placeholder="Hrs" required>
                            </div>
                            <div class="col-6 mb-4">
                                <label>Qty</label>
                                <input type="number" name="qty" class="form-control" value="1" required>
                            </div>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary w-100" style="background: var(--accent-blue); color:#000;">Commit to DB</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Inventory List -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ph ph-plugs text-blue"></i> Global Grid Inventory
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th class="ps-4">Location</th>
                                    <th>Appliance</th>
                                    <th>Load</th>
                                    <th>Hrs</th>
                                    <th>Qty</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // We check if table exists first (in case they haven't run the SQL yet)
                                $val = $conn->query("SHOW TABLES LIKE 'building_appliances'");
                                if ($val->num_rows > 0) {
                                    $result = $conn->query("SELECT * FROM building_appliances ORDER BY building_name, apartment_number");
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                <td class='ps-4'>
                                                    <span class='badge bg-dark border text-blue' style='border-color: var(--accent-blue)!important;'>{$row['building_name']}</span>
                                                    <span class='text-muted small'>#{$row['apartment_number']}</span>
                                                </td>
                                                <td class='fw-bold'>{$row['name']}</td>
                                                <td class='mono'>{$row['watts']}W</td>
                                                <td class='mono'>{$row['hours']}h</td>
                                                <td><span class='badge bg-secondary text-white'>x{$row['quantity']}</span></td>
                                                <td class='text-end pe-4'>
                                                    <a href='building_dashboard.php?delete={$row['id']}' class='btn btn-danger btn-sm px-3' style='padding:6px; border-radius:6px;'>
                                                        <i class='ph ph-trash p-0 m-0'></i>
                                                    </a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Grid empty. Add devices.</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-5 text-danger'><i class='ph ph-warning'></i> Please run the SQL command provided to create the `building_appliances` table first.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
