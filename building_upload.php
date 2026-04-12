<?php
include 'db.php';

$errors = [];
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['datafile'])) {
    $file = $_FILES['datafile'];
    $filename = $file['name'];
    $tmp = $file['tmp_name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv'])) {
        $errors[] = "Currently only .csv files are supported for Grid Imports.";
    } else {
        $appliances = [];
        if (($handle = fopen($tmp, 'r')) !== false) {
            $header = fgetcsv($handle);
            $header = array_map(fn($h) => strtolower(trim($h)), $header);
            
            $required = ['building_name', 'apartment_number', 'name', 'watts', 'hours', 'quantity'];
            foreach ($required as $col) {
                if (!in_array($col, $header)) {
                    $errors[] = "CSV is missing required column: <strong>$col</strong>";
                }
            }
            if (empty($errors)) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 6) continue;
                    $data = array_combine($header, $row);
                    $appliances[] = [
                        'building_name'    => trim($data['building_name']),
                        'apartment_number' => trim($data['apartment_number']),
                        'name'     => trim($data['name']),
                        'watts'    => intval($data['watts']),
                        'hours'    => floatval($data['hours']),
                        'quantity' => intval($data['quantity']),
                    ];
                }
            }
            fclose($handle);
        }

        if (empty($errors) && !empty($appliances)) {
            $stmt = $conn->prepare("INSERT INTO building_appliances (building_name, apartment_number, name, watts, hours, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($appliances as $app) {
                if(empty($app['building_name']) || empty($app['name'])) continue;
                $stmt->bind_param("sssidi", 
                    $app['building_name'], $app['apartment_number'], 
                    $app['name'], $app['watts'], $app['hours'], $app['quantity']
                );
                if ($stmt->execute()) { $success_count++; } 
                else { $errors[] = "DB error: " . $stmt->error; }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Building Import – Nexus Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .drop-zone { border: 2px dashed var(--border-color); border-radius: 12px; padding: 3rem 2rem; text-align: center; cursor: pointer; transition: all .25s; }
        .drop-zone:hover, .drop-zone.drag-over { border-color: var(--accent-blue); background: rgba(0, 212, 255, 0.05); }
        .drop-zone .icon { font-size: 3rem; margin-bottom: 1rem; color: var(--accent-blue); }
        pre { background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; font-size: .8rem; color: #a5d6a7; margin: 0; }
        .result-box { border-radius: 10px; padding: 1.2rem 1.5rem; font-family: 'Space Mono', monospace; font-size: .9rem; margin-bottom: 20px; }
        .result-box.success { background: rgba(0,212,255,.1); border: 1px solid var(--accent-blue); color: var(--accent-blue); }
        .result-box.error   { background: rgba(255,94,94,.08); border: 1px solid var(--accent-warning); color: var(--accent-warning); }
    </style>
</head>
<body>

<div class="container py-5 animate-slide-down" style="max-width:1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="building_dashboard.php" class="btn btn-outline-primary" style="border-color:var(--accent-blue); color:var(--accent-blue);"><i class="ph ph-arrow-left"></i> Grid Dashboard</a>
        <h2 class="m-0 text-white"><i class="ph-fill ph-database text-blue"></i> Bulk Grid Import</h2>
    </div>

    <?php if ($success_count > 0): ?>
    <div class="result-box success d-flex justify-content-between align-items-center mb-4">
        <div><i class="ph-fill ph-check-circle"></i> <?php echo $success_count; ?> grid appliance(s) imported seamlessly!</div>
        <a href="building_analysis.php" class="btn btn-sm btn-outline-primary" style="margin: 0; border-color:var(--accent-blue); color:var(--accent-blue);">View Analysis</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="result-box error mb-4">
        <strong><i class="ph-fill ph-warning-circle"></i> Pipeline Issues:</strong>
        <ul style="margin:.5rem 0 0; padding-left:1.2rem;">
            <?php foreach($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <div class="col-md-7">
            <div class="card h-100 shadow border-0" style="border-top: 3px solid var(--accent-blue)!important;">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="drop-zone" id="dropZone">
                            <i class="ph ph-file-csv icon"></i>
                            <p><strong>Drag & drop</strong> your Grid CSV file here</p>
                            <input type="file" name="datafile" id="fileInput" accept=".csv" style="display:none;" required>
                        </div>
                        <div id="fileChosen" class="mt-3 mono" style="color:var(--text-muted); display:none;">
                            <i class="ph ph-file pb-1"></i> <span id="fileName"></span>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-4" style="background: var(--accent-blue); color:#000;">
                            <i class="ph ph-lightning"></i> Deploy to Grid
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card h-100 shadow">
                <div class="card-header border-0"><i class="ph ph-code text-blue"></i> CSV Schema</div>
                <div class="card-body">
                    <div class="mb-4">
                        <pre>building_name,apartment_number,name,watts,hours,quantity
Tower A,101,Master AC,1500,8,1
Tower A,101,Ceiling Fan,75,12,4
Tower A,101,Refrigerator,250,24,1
Tower B,2B,Heater,2000,4,1
Tower B,2B,Living Room AC,1500,6,1
Tower B,2B,TV,100,5,2
Tower C,Penthouse,Central AC,3500,10,1
Tower C,Penthouse,Jacuzzi,2500,2,1
Tower C,Penthouse,LED Lights,15,10,20</pre>
                    </div>
                    <a href="data:text/csv;charset=utf-8,building_name%2Capartment_number%2Cname%2Cwatts%2Chours%2Cquantity%0ATower%20A%2C101%2CMaster%20AC%2C1500%2C8%2C1%0ATower%20A%2C101%2CCeiling%20Fan%2C75%2C12%2C4%0ATower%20A%2C101%2CRefrigerator%2C250%2C24%2C1%0ATower%20B%2C2B%2CHeater%2C2000%2C4%2C1%0ATower%20B%2C2B%2CLiving%20Room%20AC%2C1500%2C6%2C1%0ATower%20B%2C2B%2CTV%2C100%2C5%2C2%0ATower%20C%2CPenthouse%2CCentral%20AC%2C3500%2C10%2C1%0ATower%20C%2CPenthouse%2CJacuzzi%2C2500%2C2%2C1%0ATower%20C%2CPenthouse%2CLED%20Lights%2C15%2C10%2C20" download="grid_sample.csv" class="btn btn-outline-secondary btn-sm w-100"><i class="ph ph-download-simple"></i> Download Sample CSV</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileName  = document.getElementById('fileName');

    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileName.textContent = fileInput.files[0].name;
            document.getElementById('fileChosen').style.display = 'block';
            dropZone.style.borderColor = 'var(--accent-blue)';
        }
    });

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files; 
            fileName.textContent = fileInput.files[0].name;
            document.getElementById('fileChosen').style.display = 'block';
            dropZone.style.borderColor = 'var(--accent-blue)';
        }
    });
</script>
</body>
</html>
