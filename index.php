<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Energy Analyzer - Mode Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .mode-card {
            border: 2px solid transparent;
            background: var(--glass-bg);
            transition: all 0.4s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .mode-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-neon);
            box-shadow: 0 12px 40px rgba(0, 229, 160, 0.2);
            color: inherit;
        }

        .mode-card.building:hover {
            border-color: var(--accent-blue);
            box-shadow: 0 12px 40px rgba(0, 212, 255, 0.2);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
        }

        .individual-icon {
            background: rgba(0, 229, 160, 0.1);
            color: var(--accent-neon);
        }

        .building-icon {
            background: rgba(0, 212, 255, 0.1);
            color: var(--accent-blue);
        }
    </style>
</head>

<body>

    <div class="container text-center animate-slide-down">

        <div class="app-header mb-5 border-0 pb-0">
            <h1><i class="ph-fill ph-lightning text-accent"></i> Nexus Energy</h1>
            <p class="fs-5 mt-2 text-muted">Select an Environment Context to begin Analysis.</p>
        </div>

        <div class="row justify-content-center g-4 max-w-100" style="max-width: 1200px; margin: 0 auto;">

            <!-- Individual Unit Card -->
            <div class="col-md-4">
                <a href="individual_dashboard.php" class="card mode-card h-100 p-4 text-center">
                    <div class="icon-circle individual-icon">
                        <i class="ph-fill ph-house-line"></i>
                    </div>
                    <h3 class="mb-3 text-white">Individual Unit</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Analyze power consumption for a single home or
                        apartment. Upload appliance
                        data and generate insights.</p>
                    <div class="mt-auto pt-4"><span class="btn btn-outline-primary w-100">Enter Individual Mode</span>
                    </div>
                </a>
            </div>

            <!-- Residential Building Card -->
            <div class="col-md-4">
                <a href="building_dashboard.php" class="card mode-card building h-100 p-4 text-center">
                    <div class="icon-circle building-icon">
                        <i class="ph-fill ph-buildings"></i>
                    </div>
                    <h3 class="mb-3 text-white">Residential Building</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Manage massive power grids across multiple
                        buildings and apartments. Compare eco-scores macro-level.</p>
                    <div class="mt-auto pt-4"><span class="btn w-100"
                            style="background: transparent; color:var(--accent-blue); border: 2px solid var(--accent-blue);">Enter
                            Building Mode</span></div>
                </a>
            </div>

            <!-- Live NOC Card -->
            <div class="col-md-4">
                <a href="live_dashboard.php" class="card mode-card h-100 p-4 text-center"
                    style="border-color: rgba(255, 51, 102, 0.2);">
                    <div class="icon-circle" style="background: rgba(255, 51, 102, 0.1); color: #ff3366;">
                        <i class="ph-fill ph-activity"></i>
                    </div>
                    <h3 class="mb-3 text-white">Live Grid Operations</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Watch active power flow across dynamic residential
                        towers. See live spikes, pie charts, and real-time telemetry drops.</p>
                    <div class="mt-auto pt-4"><span class="btn w-100"
                            style="background: transparent; color:#ff3366; border: 2px solid #ff3366; box-shadow: 0 0 10px rgba(255,51,102,0.2);">Enter
                            Live NOC</span></div>
                </a>
            </div>

        </div>
    </div>

</body>

</html>