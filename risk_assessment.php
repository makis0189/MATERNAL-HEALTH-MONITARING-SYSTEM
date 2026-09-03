<?php

function assessAncRisk($bloodPressure, $fetalHeartRate, $temperature) {
$reasons = [];
$levels = []; 

// Blood Pressure 
if ($bloodPressure) {
if (preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', (string) $bloodPressure, $m)) {
$systolic = (int) $m[1];
$diastolic = (int) $m[2];

if ($systolic >= 140 || $diastolic >= 90) {
$levels[] = 2;
$reasons[] = "BP {$systolic}/{$diastolic} - juu sana (Symptom ya pre-eclampsia)";
} elseif ($systolic >= 130 || $diastolic >= 85) {
$levels[] = 1;
$reasons[] = "BP {$systolic}/{$diastolic} - juu kiasi";
}
}
}

//  Fetal Heart Rate 
if ($fetalHeartRate !== null && $fetalHeartRate !== '') {
$fhr = (int) $fetalHeartRate;
if ($fhr > 0) {
if ($fhr < 100 || $fhr > 180) {
$levels[] = 2;
$reasons[] = "FHR {$fhr} bpm - Out of average (110-160)";
} elseif ($fhr < 110 || $fhr > 160) {
$levels[] = 1;
$reasons[] = "FHR {$fhr} bpm - Out of average (110-160)";
}
}
}

//  Temperature 
if ($temperature !== null && $temperature !== '') {
$temp = (float) $temperature;
if ($temp > 0) {
if ($temp >= 38.5) {
$levels[] = 2;
$reasons[] = "Joto {$temp}°C - homa kali";
} elseif ($temp >= 37.5) {
$levels[] = 1;
$reasons[] = "Joto {$temp}°C - homa kidogo";
}
}
}

$maxLevel = empty($levels) ? 0 : max($levels);
$labels = [0 => 'Low', 1 => 'Medium', 2 => 'High'];

return [
'level'   => $labels[$maxLevel],
'reasons' => $reasons,
];
}
