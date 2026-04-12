<?php
include 'db.php';

// Handle Form Submission
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $watts = $_POST['watts'];
    $hours = $_POST['hours'];
    $qty = $_POST['qty'];

    $sql = "INSERT INTO appliances (name, watts, hours, quantity) VALUES ('$name', '$watts', '$hours', '$qty')";
    $conn->query($sql);
    header("Location: individual_dashboard.php"); // Refresh page
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM appliances WHERE id=$id");
    header("Location: individual_dashboard.php");
}

// Quick stats logic for dashboard
$stats = ['count' => 0, 'total_load' => 0];
$result = $conn->query("SELECT COUNT(*) as count, SUM(watts*quantity) as total_load FROM appliances");
if ($result) {
    $stats = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Phosphor Icons for modern icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

<div class="container py-5 animate-slide-down">
    
    <div class="app-header mb-4 border-0 pb-0 d-flex justify-content-between align-items-center text-start">
        <div>
            <h1 class="m-0" style="font-size: 2rem;"><i class="ph-fill ph-lightning text-accent"></i> Nexus <span class="text-accent">Individual</span></h1>
            <p class="text-muted mt-2">Monitor your home footprint, optimize appliance usage, and reduce your electrical bill.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="ph ph-swap"></i> Switch Mode</a>
    </div>
    
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-3">
            <div class="px-3 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px; border: 1px solid var(--border-color);">
                <span class="text-muted small">Total Devices:</span> <strong class="text-white ml-2"><?php echo $stats['count'] ?? 0; ?></strong>
            </div>
            <div class="px-3 py-2" style="background: rgba(255,255,255,0.05); border-radius: 8px; border: 1px solid var(--border-color);">
                <span class="text-muted small">Active Load:</span> <strong class="text-accent ml-2"><?php echo number_format(($stats['total_load'] ?? 0)/1000, 2); ?> kW</strong>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="individual_upload.php" class="btn btn-secondary"><i class="ph ph-upload-simple"></i> Bulk Import</a>
            <a href="individual_analysis.php" class="btn btn-primary"><i class="ph ph-chart-pie-slice"></i> Generate Insight Report</a>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- Add Appliance Form -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ph ph-plus-circle text-accent"></i> Add Device
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Appliance Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Inverter AC, LG Refrigerator" required>
                        </div>
                        <div class="mb-3">
                            <label>Power Draw (Watts)</label>
                            <div class="input-group">
                                <input type="number" name="watts" class="form-control" placeholder="1500" required>
                                <span class="input-group-text bg-transparent text-muted" style="border-color: var(--border-color);">W</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Daily Usage</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="hours" class="form-control" placeholder="6" required>
                                <span class="input-group-text bg-transparent text-muted" style="border-color: var(--border-color);">Hours</span>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label>Quantity</label>
                            <input type="number" name="qty" class="form-control" value="1" required>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary w-100">Save Configuration</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Inventory List -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ph ph-plugs text-accent"></i> Active Inventory
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th class="ps-4">Appliance</th>
                                    <th>Rating</th>
                                    <th>Duration</th>
                                    <th>Qty</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT * FROM appliances ORDER BY id DESC");
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                            <td class='ps-4 fw-bold'>{$row['name']}</td>
                                            <td class='mono'>{$row['watts']}W</td>
                                            <td class='mono'>{$row['hours']}h</td>
                                            <td><span class='badge bg-secondary text-white'>x{$row['quantity']}</span></td>
                                            <td class='text-end pe-4'>
                                                <a href='individual_dashboard.php?delete={$row['id']}' class='btn btn-danger btn-sm px-3' style='padding:6px; border-radius:6px;' title='Remove'>
                                                    <i class='ph ph-trash p-0 m-0'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No devices added yet. Add a device or import data.</td></tr>";
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