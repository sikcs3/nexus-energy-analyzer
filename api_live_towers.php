<?php
header('Content-Type: application/json');

// Get request parameters
$count = isset($_GET['count']) ? max(1, intval($_GET['count'])) : 4;
$scenario = isset($_GET['scenario']) ? $_GET['scenario'] : 'standard';

// Scenario constraints
$multiplier = 1.0;
$baseMin = 300;
$baseMax = 500;

if ($scenario === 'peak') {
    $multiplier = 1.8;
} else if ($scenario === 'night') {
    $multiplier = 0.4;
} else if ($scenario === 'eco') {
    $multiplier = 0.7;
}

$towers = [];
$total_kw = 0;

for ($i = 1; $i <= $count; $i++) {
    // Generate base load
    $kw = rand($baseMin * $multiplier, $baseMax * $multiplier);
    
    // Status
    $status = ($scenario === 'peak' && $kw > 800) ? 'warning' : 'stable';
    if ($kw > 900) $status = 'critical';

    // Randomize appliance distribution for Pie Chart
    // Ensure the total adds up closely to 100%
    $p_hvac = rand(30, 50);
    $p_lighting = rand(10, 20);
    $p_elevators = rand(10, 20);
    $p_flats = 100 - ($p_hvac + $p_lighting + $p_elevators);

    $appliance_breakdown = [
        "hvac" => round(($p_hvac / 100) * $kw),
        "lighting" => round(($p_lighting / 100) * $kw),
        "elevators" => round(($p_elevators / 100) * $kw),
        "flats" => round(($p_flats / 100) * $kw)
    ];

    $towers[] = [
        "name" => "Tower " . $i,
        "status" => $status,
        "kw" => $kw,
        "appliances" => $appliance_breakdown
    ];
    $total_kw += $kw;
}

// Generate a random event related to the simulation
$events = [
    "Grid stable. No anomalies detected.",
    "Grid stable. No anomalies detected.",
    "Grid stable. No anomalies detected.",
    sprintf("Tower %d HVAC array adjusting (+%dkW)", rand(1, $count), rand(50, 150)),
    sprintf("Tower %d Elevator active burst (+%dkW)", rand(1, $count), rand(20, 80)),
    sprintf("Tower %d Night lighting active (-%dkW)", rand(1, $count), rand(10, 30)),
];

$response = [
    "timestamp" => date("Y-m-d H:i:s"),
    "total_mw" => number_format($total_kw / 1000, 2),
    "towers" => $towers,
    "scenario" => $scenario,
    "event" => $events[array_rand($events)] 
];

echo json_encode($response);
