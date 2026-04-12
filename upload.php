<?php
include 'db.php';

$errors = [];
$success_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['datafile'])) {
    $file = $_FILES['datafile'];
    $filename = $file['name'];
    $tmp = $file['tmp_name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed. Please try again.";
    } elseif (!in_array($ext, ['csv', 'json'])) {
        $errors[] = "Only .csv and .json files are supported.";
    } else {
        $appliances = [];

        // ── Parse CSV ──────────────────────────────────────────────────────────
        if ($ext === 'csv') {
            if (($handle = fopen($tmp, 'r')) !== false) {
                $header = fgetcsv($handle); // skip header row
                // Normalise header names (lowercase, trim)
                $header = array_map(fn($h) => strtolower(trim($h)), $header);

                // Validate required columns
                $required = ['name', 'watts', 'hours', 'quantity'];
                foreach ($required as $col) {
                    if (!in_array($col, $header)) {
                        $errors[] = "CSV is missing required column: <strong>$col</strong>";
                    }
                }

                if (empty($errors)) {
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) < 4) continue; // skip malformed rows
                        $data = array_combine($header, $row);
                        $appliances[] = [
                            'name'     => trim($data['name']),
                            'watts'    => intval($data['watts']),
                            'hours'    => floatval($data['hours']),
                            'quantity' => intval($data['quantity']),
                        ];
                    }
                }
                fclose($handle);
            }

        // ── Parse JSON ─────────────────────────────────────────────────────────
        } elseif ($ext === 'json') {
            $json = file_get_contents($tmp);
            $parsed = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Invalid JSON file: " . json_last_error_msg();
            } else {
                // Support both a top-level array or {"appliances": [...]}
                $items = isset($parsed['appliances']) ? $parsed['appliances'] : $parsed;

                foreach ($items as $item) {
                    $required = ['name', 'watts', 'hours', 'quantity'];
                    $missing = [];
                    foreach ($required as $col) {
                        if (!array_key_exists($col, $item)) $missing[] = $col;
                    }
                    if (!empty($missing)) {
                        $errors[] = "A JSON entry is missing: " . implode(', ', $missing);
                        break;
                    }
                    $appliances[] = [
                        'name'     => trim($item['name']),
                        'watts'    => intval($item['watts']),
                        'hours'    => floatval($item['hours']),
                        'quantity' => intval($item['quantity']),
                    ];
                }
            }
        }

        // ── Insert into DB ─────────────────────────────────────────────────────
        if (empty($errors) && !empty($appliances)) {
            $stmt = $conn->prepare(
                "INSERT INTO appliances (name, watts, hours, quantity) VALUES (?, ?, ?, ?)"
            );
            foreach ($appliances as $app) {
                // Basic validation
                if (empty($app['name'])) { $errors[] = "Appliance name cannot be empty."; continue; }
                if ($app['watts'] <= 0)  { $errors[] = "Watts must be > 0 for '{$app['name']}'."; continue; }
                if ($app['hours'] <= 0)  { $errors[] = "Hours must be > 0 for '{$app['name']}'."; continue; }
                if ($app['quantity'] <= 0){ $errors[] = "Quantity must be > 0 for '{$app['name']}'."; continue; }

                $stmt->bind_param("sidi",
                    $app['name'], $app['watts'], $app['hours'], $app['quantity']
                );
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $errors[] = "DB error for '{$app['name']}': " . $stmt->error;
                }
            }
            $stmt->close();
        } elseif (empty($appliances) && empty($errors)) {
            $errors[] = "The file appears to be empty or has no valid data rows.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Import – Nexus Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all .25s;
            background: rgba(255, 255, 255, 0.02);
            margin-bottom: 20px;
        }
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--accent-neon);
            background: rgba(0, 229, 160, 0.05);
        }
        .drop-zone .icon { font-size: 3rem; margin-bottom: 1rem; color: var(--accent-neon); }
        .drop-zone p  { color: var(--text-muted); margin: 0; }
        .drop-zone strong { color: var(--accent-neon); }
        
        pre {
            background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);
            border-radius: 8px; padding: 1rem;
            font-size: .8rem; color: #a5d6a7;
            overflow-x: auto; margin: 0;
        }

        .result-box {
            border-radius: 10px; padding: 1.2rem 1.5rem;
            font-family: 'Space Mono', monospace; font-size: .9rem;
            margin-bottom: 20px;
        }
        .result-box.success { background: rgba(0,229,160,.1); border: 1px solid var(--accent-neon); color: var(--accent-neon); }
        .result-box.error   { background: rgba(255,94,94,.08); border: 1px solid var(--accent-warning); color: var(--accent-warning); }
    </style>
</head>
<body>

<div class="container py-5 animate-slide-down" style="max-width:1000px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn btn-outline-primary"><i class="ph ph-arrow-left"></i> Dashboard</a>
        <h2 class="m-0 text-white"><i class="ph-fill ph-database text-accent"></i> Bulk Import</h2>
    </div>

    <!-- Result messages -->
    <?php if ($success_count > 0): ?>
    <div class="result-box success d-flex justify-content-between align-items-center mb-4">
        <div><i class="ph-fill ph-check-circle"></i> <?php echo $success_count; ?> appliance(s) imported successfully!</div>
        <a href="analysis.php" class="btn btn-sm btn-outline-primary" style="margin: 0;">View Analysis</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="result-box error mb-4">
        <strong><i class="ph-fill ph-warning-circle"></i> Issues found:</strong>
        <ul style="margin:.5rem 0 0; padding-left:1.2rem;">
            <?php foreach($errors as $e): ?>
                <li><?php echo $e; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <!-- Upload card -->
        <div class="col-md-7">
            <div class="card h-100 shadow">
                <div class="card-header border-0"><i class="ph ph-upload-simple text-blue"></i> Upload Data File</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="drop-zone" id="dropZone">
                            <i class="ph ph-file-text icon"></i>
                            <p><strong>Drag & drop</strong> your file here</p>
                            <p style="font-size:.85rem; margin-top:.4rem;">or click to browse &nbsp;·&nbsp; CSV or JSON accepted</p>
                            <input type="file" name="datafile" id="fileInput" accept=".csv,.json" style="display:none;" required>
                        </div>

                        <div id="fileChosen" class="mt-3 mono" style="color:var(--text-muted); font-size:.85rem; display:none;">
                            <i class="ph ph-file pb-1"></i> <span id="fileName"></span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="ph ph-lightning"></i> Import to Database
                        </button>
                    </form>

                    <hr style="border-color: var(--border-color); margin: 2rem 0 1rem;">

                    <p class="small text-muted mb-2">Need a template? Download sample files:</p>
                    <div class="d-flex gap-2">
                        <a href="sample_appliances.csv" download class="btn btn-secondary btn-sm"><i class="ph ph-download-simple"></i> sample.csv</a>
                        <a href="sample_appliances.json" download class="btn btn-secondary btn-sm"><i class="ph ph-download-simple"></i> sample.json</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Format guide -->
        <div class="col-md-5">
            <div class="card h-100 shadow">
                <div class="card-header border-0"><i class="ph ph-code text-blue"></i> Required Format</div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="mono text-accent mb-2">CSV Format</h6>
                        <pre>name,watts,hours,quantity
Inverter AC,1500,6,1
Refrigerator,150,24,1
Ceiling Fan,75,8,3</pre>
                    </div>

                    <div class="mb-4">
                        <h6 class="mono text-blue mb-2">JSON Format</h6>
                        <pre>[
  {
   "name": "Heater",
   "watts": 2000,
   "hours": 2,
   "quantity": 1
  }
]</pre>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead style="border-bottom: 1px solid var(--border-color);">
                                <tr><th>Field</th><th>Required</th><th>Type</th></tr>
                            </thead>
                            <tbody class="mono text-muted small">
                                <tr><td>name</td><td class="text-accent">Yes</td><td>String</td></tr>
                                <tr><td>watts</td><td class="text-accent">Yes</td><td>Number</td></tr>
                                <tr><td>hours</td><td class="text-accent">Yes</td><td>Float</td></tr>
                                <tr><td>quantity</td><td class="text-accent">Yes</td><td>Integer</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileChosen= document.getElementById('fileChosen');
    const fileName  = document.getElementById('fileName');

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileName.textContent = fileInput.files[0].name;
            fileChosen.style.display = 'block';
            dropZone.style.borderColor = 'var(--accent-neon)';
        }
    });

    dropZone.addEventListener('dragover', e => { 
        e.preventDefault(); 
        dropZone.classList.add('drag-over'); 
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files; // assign to input
            fileName.textContent = files[0].name;
            fileChosen.style.display = 'block';
            dropZone.style.borderColor = 'var(--accent-neon)';
        }
    });
</script>
</body>
</html>
