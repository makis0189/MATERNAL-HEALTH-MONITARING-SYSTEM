<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
header("Location: index.php");
exit();
}

//  Role-Based Access Control (RBAC) 
$MODULE_WRITE_ROLES = [
'patient'      => ['Nurse'],
'appointment'  => [],           // hakuna mwandishi kwa sasa
'antenatal'    => ['Nurse'],
'postnatal'    => ['Nurse'],
'immunization' => [],           // hakuna mwandishi kwa sasa
'high'         => ['Nurse'],
'message'      => [],           // hakuna mwandishi kwa sasa
'education'    => [],           // hakuna mwandishi kwa sasa
'users'        => ['Admin'],    // Admin anabaki na uwezo kamili hapa
];

function canWrite($module) {
global $MODULE_WRITE_ROLES;
if (!isset($MODULE_WRITE_ROLES[$module])) return false;
return in_array(currentRole(), $MODULE_WRITE_ROLES[$module], true);
}

$patient_count = $conn->query("SELECT COUNT(*) AS total FROM patients")->fetch_assoc()['total'];
$appointment_count = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'];
$anc_query = $conn-> query("SELECT COUNT(*) AS total FROM antenatal_care");
$anc_row =$anc_query -> fetch_assoc();
$anc_count = $anc_row['total'];
$post_query =$conn-> query("SELECT COUNT(*) AS total FROM postnatal_care");
$post_row = $post_query-> fetch_assoc();
$Postnatal_count= $post_row['total'];
$high_query= $conn-> query("SELECT COUNT(*) AS total FROM high_risk_cases");
$high_row = $high_query->fetch_assoc();
$Highrisk_count= $high_row['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard | MHS</title>
<link rel="icon" type="image" href="ICON.jpeg">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<button class="toggle-btn" id="toggleBtn">
<i class="fa-solid fa-bars"></i>
</button>
<div class="wrapper">
<div class="dash" id="sidebar">
<span>
<img src="LOGO MHS.jpeg" alt="Logo">
<h2>Maternal Health<br> System</h2>
</span>

<aside class="home active" data-target="dashboardContent">
<i class="fa-solid fa-house"></i>
<a href="#">Dashboard</a>
</aside>

<?php if (canSee('patient')): ?>
<aside class="home" data-target="patientContent">
<i class="fa-solid fa-bed-pulse"></i>
<a href="#">Patients</a>
</aside>
<?php endif; ?>

<?php if (canSee('appointment')): ?>
<aside class="home" data-target="appointmentContent">
<i class="fa-solid fa-calendar"></i>
<a href="#">Appointment</a>
</aside>
<?php endif; ?>

<?php if (canSee('antenatal')): ?>
<aside class="home" id="antenatalBtn" data-target="antenatalContent">
<i class="fa-solid fa-person-pregnant"></i>
<a href="#">Antenatal Care</a>
</aside>
<?php endif; ?>

<?php if (canSee('postnatal')): ?>
<aside class="home" id="postnatalBtn" data-target="postnatalContent">
<i class="fa-solid fa-baby"></i>
<a href="#">Postnatal Care</a>
</aside>
<?php endif; ?>

<?php if (canSee('immunization')): ?>
<aside class="home" id="immunazationBtn" data-target="immunizationContent">
<i class="fa-solid fa-syringe"></i>
<a href="#">Immunization</a>
</aside>
<?php endif; ?>

<?php if (canSee('high')): ?>
<aside class="home" id="highBtn" data-target="highContent">
<i class="fa-solid fa-gauge-simple-high"></i>
<a href="#">High Risk Cases</a>
</aside>
<?php endif; ?>

<?php if (canSee('report')): ?>
<aside class="home" id="reportBtn" data-target="reportContent">
<i class="fa-solid fa-newspaper"></i>
<a href="#">Reports</a>
</aside>
<?php endif; ?>

<?php if (canSee('message')): ?>
<aside class="home" id="messageBtn" data-target="messageContent">
<i class="fa-regular fa-message"></i>
<a href="#">Messages</a>
</aside>
<?php endif; ?>

<?php if (canSee('education')): ?>
<aside class="home" id="educationBtn" data-target="educationContent">
<i class="fa-solid fa-prescription-bottle-medical"></i>
<a href="#">Health Education</a>
</aside>
<?php endif; ?>

<?php if (canSee('users')): ?>
<aside class="home" id="usersBtn" data-target="usersContent">
<i class="fa-solid fa-users-gear"></i>
<a href="#">Manage Users</a>
</aside>
<?php endif; ?>

<aside class="home logout-btn">
<i class="fa-solid fa-right-from-bracket"></i>
<a href="logout.php">Logout</a>
</aside>
</div>

<main class="main-content" id="dashboardContent" style="display: block;">
<h1>Dashboard</h1>
<p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! <span style="color:#888; font-size:0.85em;">(<?php echo htmlspecialchars(currentRole()); ?>)</span></p>
<div class="cards">
<div class="card">
<i class="fa-solid fa-bed-pulse"></i>
<h3>Total Patients</h3>
<h2><?php echo $patient_count; ?></h2>
</div>
<div class="card">
<i class="fa-solid fa-calendar"></i>
<h3>Appointments</h3>
<h2><?php echo $appointment_count; ?></h2>
</div>
<div class="card">
<i class="fa-solid fa-person-pregnant"></i>
<h3>Antenatal Care</h3>
<h2><?php echo $anc_count; ?></h2>
</div>
<div class="card">
<i class="fa-solid fa-baby"></i>
<h3>Postnatal Care</h3>
<h2><?php echo $Postnatal_count; ?></h2>

</div>
<div class="card">
<i class="fa-solid fa-gauge-simple-high"></i>
<h3>High Risk Cases</h3>
<h2><?php echo $Highrisk_count; ?></h2>
</div>
</div>
</main>

<?php if (canSee('patient')): ?>
<main class="main-content" id="patientContent">
<div class="page-header">
<div>
<h1>Patients</h1>
<p>Manage maternal and patient information</p>
</div>
<?php if (canWrite('patient')): ?>
<button class="add-btn" id="addPatientBtn">
<i class="fa-solid fa-plus"></i> Add New Patient
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="patientSearch" placeholder="Search patient by name or ID...">
</div>
</div>

<div class="patient-form-container" id="patientForm">
<h2>Patient Registration</h2>
<form id="patientRegistrationForm">
<div class="form-grid">
<input type="hidden" name="id">
<div class="form-group">
<label>Patient ID</label>
<input type="text" id="patientId" name="patient_id" placeholder="e.g. MHS001" required>
</div>
<div class="form-group">
<label>Full Name</label>
<input type="text" id="patientName" name="full_name" placeholder="Enter full name" required>
</div>
<div class="form-group">
<label>Date of Birth</label>
<input type="date" id="patientDob" name="dob" required>
</div>
<div class="form-group">
<label>Phone Number</label>
<input type="tel" id="patientPhone" name="phone" placeholder="Enter phone number" required>
</div>
<div class="form-group">
<label>Address</label>
<input type="text" id="patientAddress" name="address" placeholder="Enter address" required>
</div>
<div class="form-group">
<label>Blood Group</label>
<select id="bloodGroup" name="blood_group">
<option value="">Select blood group</option>
<option value="A+">A+</option>
<option value="A-">A-</option>
<option value="B+">B+</option>
<option value="B-">B-</option>
<option value="AB+">AB+</option>
<option value="AB-">AB-</option>
<option value="O+">O+</option>
<option value="O-">O-</option>
</select>
</div>
<div class="form-group">
<label>Pregnancy Status</label>
<select id="pregnancyStatus" name="pregnancy_status">
<option value="">Select status</option>
<option value="Pregnant">Pregnant</option>
<option value="Not Pregnant">Not Pregnant</option>
<option value="Postnatal">Postnatal</option>
</select>
</div>
<div class="form-group">
<label>Emergency Contact</label>
<input type="tel" id="emergencyContact" name="emergency_contact" placeholder="Emergency contact">
</div>
</div>
<div class="form-buttons">
<button type="button" class="cancel-btn" id="cancelPatientBtn">Cancel</button>
<button type="submit" class="save-btn">
<i class="fa-solid fa-floppy-disk"></i> Save Patient
</button>
</div>
</form>
</div>

<div class="patient-table-container">
<div class="table-header">
<h2>Patient Records</h2>
<span id="patientCount">0 Patients</span>
</div>
<div class="table-wrapper">
<table>
<thead>
<tr>
<th>Patient ID</th>
<th>Full Name</th>
<th>Phone</th>
<th>Blood Group</th>
<th>Pregnancy Status</th>
<th>Action</th>
</tr>
</thead>
<tbody id="patientTableBody"></tbody>
</table>
</div>
</div>
</main>
<?php endif; ?>

<?php if (canSee('appointment')): ?>
<main class="main-content" id="appointmentContent">

<div class="page-header">
<div>
<h1>Appointments</h1>
<p>Manage patient appointments</p>
</div>
<?php if (canWrite('appointment')): ?>
<button class="add-btn" id="addAppointmentBtn">
<i class="fa-solid fa-plus"></i>
New Appointment
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="appointmentSearch" placeholder="Search appointment by patient name...">
</div>
</div>

<div class="patient-form-container" id="appointmentForm">

<h2>New Appointment</h2>

<form id="appointmentFormData">

<div class="form-grid">

<div class="form-group">
<label>Patient Name</label>
<input type="text" id="appPatient" name="patient_name" required>
</div>

<div class="form-group">
<label>Date</label>
<input type="date" id="appDate" name="appointment_date" required>
</div>

<div class="form-group">
<label>Time</label>
<input type="time" id="appTime" name="appointment_time" required>
</div>

<div class="form-group">
<label>Service</label>
<select id="appService" name="service">
<option>Antenatal Care</option>
<option>Postnatal Care</option>
<option>Immunization</option>
<option>General Checkup</option>
</select>
</div>

<div class="form-group">
<label>Status</label>
<select id="appStatus" name="status">
<option>Pending</option>
<option>Completed</option>
<option>Cancelled</option>
</select>
</div>

</div>

<div class="form-buttons">
<button type="button" class="cancel-btn" id="cancelAppointmentBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save Appointment
</button>
</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>Appointment Records</h2>
<span id="appointmentCount">0 Appointments</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Patient</th>
<th>Date</th>
<th>Time</th>
<th>Service</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="appointmentTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('antenatal')): ?>
<main class="main-content" id="antenatalContent">

<div class="page-header">

<div>
<h1>Antenatal Care</h1>
<p>Manage pregnancy and ANC visits</p>
</div>
<?php if (canWrite('antenatal care')): ?>
<button class="add-btn" id="addAncBtn">
<i class="fa-solid fa-plus"></i>
New ANC Visit
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="ancSearch" placeholder="Search ANC record by patient name...">
</div>
</div>

<div class="patient-form-container" id="ancForm">

<h2>ANC Visit</h2>

<form id="ancFormData">

<div class="form-grid">

<div class="form-group">
<label>Patient Name</label>
<input type="text" id="ancPatient" name="patient_name" required>
</div>

<div class="form-group">
<label>LMP (Last Menstrual Period)</label>
<input type="date" id="ancLmp" name="lmp_date" required>
</div>

<div class="form-group">
<label>EDD (Expected Delivery Date)</label>
<input type="date" id="ancEdd" name="edd_date" required>
</div>

<div class="form-group">
<label>Gestational Age (Weeks)</label>
<input type="number" id="ancWeeks" name="gestational_weeks" placeholder="e.g. 20">
</div>

<div class="form-group">
<label>Blood Pressure</label>
<input type="text" id="ancBp" name="blood_pressure" placeholder="e.g. 120/80">
</div>

<div class="form-group">
<label>Weight (kg)</label>
<input type="number" id="ancWeight" name="weight">
</div>

<div class="form-group">
<label>Temperature (°C)</label>
<input type="number" id="ancTemp" name="temperature">
</div>

<div class="form-group">
<label>Fetal Heart Rate</label>
<input type="number" id="ancFhr" name="fetal_heart_rate">
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelAncBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save ANC Visit
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>ANC Records</h2>
<span id="ancCount">0 Records</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Patient</th>
<th>LMP</th>
<th>EDD</th>
<th>Weeks</th>
<th>BP</th>
<th>Weight</th>
<th>FHR</th>
<th>Risk</th>
<th>Action</th>
</tr>
</thead>

<tbody id="ancTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('postnatal')): ?>
<main class="main-content" id="postnatalContent">

<div class="page-header">

<div>
<h1>Postnatal Care</h1>
<p>Manage mother and baby after delivery</p>
</div>
<?php if (canWrite('postnatal care')): ?>
<button class="add-btn" id="addPncBtn">
<i class="fa-solid fa-plus"></i>
New PNC Record
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="pncSearch" placeholder="Search PNC record by mother or baby name...">
</div>
</div>

<div class="patient-form-container" id="pncForm">

<h2>Postnatal Record</h2>

<form id="pncFormData">

<div class="form-grid">

<div class="form-group">
<label>Mother Name</label>
<input type="text" id="pncMother" name="mother_name" required>
</div>

<div class="form-group">
<label>Father Name</label>
<input type="text" id="pncFather" name="father_name" required>
</div>

<div class="form-group">
<label>Delivery Date</label>
<input type="date" id="pncDate" name="delivery_date" required>
</div>

<div class="form-group">
<label>Delivery Method</label>
<select id="pncMethod" name="delivery_method">
<option>Normal</option>
<option>Cesarean Section</option>
</select>
</div>

<div class="form-group">
<label>Mother Condition</label>
<select id="pncCondition" name="mother_condition">
<option>Stable</option>
<option>Critical</option>
</select>
</div>

<div class="form-group">
<label>Blood Pressure</label>
<input type="text" id="pncBp" name="blood_pressure" placeholder="120/80">
</div>

<div class="form-group">
<label>Temperature (°C)</label>
<input type="number" id="pncTemp" name="temperature">
</div>

<div class="form-group">
<label>Bleeding</label>
<select id="pncBleeding" name="bleeding">
<option>Normal</option>
<option>Excessive</option>
</select>
</div>

<div class="form-group">
<label>Baby Name</label>
<input type="text" id="pncBaby" name="baby_name">
</div>

<div class="form-group">
<label>Sex</label>
<select id="pncSex" name="baby_sex">
<option>Male</option>
<option>Female</option>
</select>
</div>

<div class="form-group">
<label>Birth Weight (kg)</label>
<input type="number" id="pncWeight" name="birth_weight">
</div>

<div class="form-group">
<label>Feeding</label>
<select id="pncFeeding" name="feeding">
<option>Breastfeeding</option>
<option>Formula</option>
</select>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelPncBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save Record
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>PNC Records</h2>
<span id="pncCount">0 Records</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Mother</th>
<th>Father</th>
<th>Date</th>
<th>Method</th>
<th>Condition</th>
<th>Baby</th>
<th>Weight</th>
<th>Feeding</th>
<th>Action</th>
</tr>
</thead>

<tbody id="pncTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('immunization')): ?>
<main class="main-content" id="immunizationContent">

<div class="page-header">

<div>
<h1>Immunization</h1>
<p>Manage vaccinations for mothers and babies</p>
</div>
<?php if(canWrite('immunazation')): ?>
<button class="add-btn" id="addImmBtn">
<i class="fa-solid fa-plus"></i>
New Vaccination
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="immSearch" placeholder="Search vaccination record by name...">
</div>
</div>

<div class="patient-form-container" id="immForm">

<h2>Vaccination Record</h2>

<form id="immFormData">

<div class="form-grid">

<div class="form-group">
<label>Patient / Baby Name</label>
<input type="text" id="immName" name="patient_name" required>
</div>

<div class="form-group">
<label>Vaccine</label>
<select id="immVaccine" name="vaccine">
<option>BCG</option>
<option>OPV</option>
<option>Pentavalent</option>
<option>Measles</option>
<option>Tetanus</option>
</select>
</div>

<div class="form-group">
<label>Dose</label>
<input type="number" id="immDose" name="dose" placeholder="e.g. 1">
</div>

<div class="form-group">
<label>Date Given</label>
<input type="date" id="immDate" name="date_given" required>
</div>

<div class="form-group">
<label>Next Due Date</label>
<input type="date" id="immNext" name="next_due_date">
</div>

<div class="form-group">
<label>Status</label>
<select id="immStatus" name="status">
<option>Given</option>
<option>Due</option>
<option>Overdue</option>
</select>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelImmBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save Record
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>Vaccination Records</h2>
<span id="immCount">0 Records</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Name</th>
<th>Vaccine</th>
<th>Dose</th>
<th>Date</th>
<th>Next Due</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="immTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('high')): ?>
<main class="main-content" id="highContent">

<div class="page-header">

<div>
<h1>High Risk Cases</h1>
<p>Monitor and manage high-risk maternal patients</p>
</div>
<?php if(canWrite('high risk cases')): ?>
<button class="add-btn" id="addHighBtn">
<i class="fa-solid fa-plus"></i>
Add High Risk Case
</button>
<?php endif; ?>
</div>

<div class="patient-tools">
<div class="search-box">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" id="highSearch" placeholder="Search high risk case by patient name...">
</div>
</div>

<div class="patient-form-container" id="highForm">

<h2>High Risk Patient</h2>

<form id="highFormData">

<div class="form-grid">

<div class="form-group">
<label>Patient Name</label>
<input type="text" id="highPatient" name="patient_name" required>
</div>

<div class="form-group">
<label>Gestational Age (Weeks)</label>
<input type="number" id="highWeeks" name="gestational_weeks">
</div>

<div class="form-group">
<label>Risk Level</label>
<select id="highLevel" name="risk_level">
<option>Low</option>
<option>Medium</option>
<option>High</option>
</select>
</div>

<div class="form-group">
<label>Risk Factor</label>
<input type="text" id="highFactor" name="risk_factor" placeholder="e.g. High BP, Diabetes">
</div>

<div class="form-group">
<label>Follow-up Date</label>
<input type="date" id="highFollow" name="followup_date">
</div>

<div class="form-group">
<label>Status</label>
<select id="highStatus" name="status">
<option>Monitoring</option>
<option>Critical</option>
<option>Recovered</option>
</select>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelHighBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save Case
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>High Risk Records</h2>
<span id="highCount">0 Records</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Patient</th>
<th>Weeks</th>
<th>Risk Level</th>
<th>Risk Factor</th>
<th>Follow-up</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="highTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('report')): ?>
<main class="main-content" id="reportContent">

<div class="page-header">

<div>
<h1>Reports</h1>
<p>View system statistics and performance</p>
</div>

</div>


<div class="patient-tools">

<select id="reportFilter" style="padding: 10px; font-size: 10px;">

<option value="all">All Time</option>
<option value="today">Today</option>
<option value="month">This Month</option>

</select>

</div>

<div class="cards">

<div class="card">
<h3>Total Patients</h3>
<h2 id="reportPatients">0</h2>
</div>

<div class="card">
<h3>Appointments</h3>
<h2 id="reportAppointments">0</h2>
</div>

<div class="card">
<h3>ANC Visits</h3>
<h2 id="reportAnc">0</h2>
</div>

<div class="card">
<h3>PNC Records</h3>
<h2 id="reportPnc">0</h2>
</div>

<div class="card">
<h3>Immunizations</h3>
<h2 id="reportImm">0</h2>
</div>

<div class="card">
<h3>High Risk Cases</h3>
<h2 id="reportHigh">0</h2>
</div>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>System Summary</h2>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Category</th>
<th>Total</th>
</tr>
</thead>

<tbody>

<tr>
<td>Patients</td>
<td id="tablePatients">0</td>
</tr>

<tr>
<td>Appointments</td>
<td id="tableAppointments">0</td>
</tr>

<tr>
<td>ANC Visits</td>
<td id="tableAnc">0</td>
</tr>

<tr>
<td>PNC Records</td>
<td id="tablePnc">0</td>
</tr>

<tr>
<td>Immunization</td>
<td id="tableImm">0</td>
</tr>

<tr>
<td>High Risk Cases</td>
<td id="tableHigh">0</td>
</tr>

</tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('message')): ?>
<main class="main-content" id="messageContent">

<div class="page-header">

<div>
<h1>Messages</h1>
<p>Manage communication between staff and patients</p>
</div>
<?php if(canWrite('messages')): ?>
<button class="add-btn" id="addMsgBtn">
<i class="fa-solid fa-plus"></i>
New Message
</button>
<?php endif; ?>
</div>

<div class="patient-form-container" id="msgForm">

<h2>Send Message</h2>

<form id="msgFormData">

<div class="form-grid">

<div class="form-group">
<label>Receiver</label>
<input type="text" id="msgReceiver" name="receiver" required>
</div>

<div class="form-group">
<label>Subject</label>
<input type="text" id="msgSubject" name="subject" required>
</div>

<div class="form-group" style="grid-column: span 2;">
<label>Message</label>
<textarea id="msgText" name="message" rows="4" style="padding:12px;"></textarea>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelMsgBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Send Message
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>Inbox</h2>
<span id="msgCount">0 Messages</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Receiver</th>
<th>Subject</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="msgTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('education')): ?>
<main class="main-content" id="educationContent">

<div class="page-header">

<div>
<h1>Health Education</h1>
<p>Provide guidance and education to mothers</p>
</div>
<?php if(canWrite('health education')): ?>
<button class="add-btn" id="addEduBtn">
<i class="fa-solid fa-plus"></i>
Add Topic
</button>
<?php endif; ?>
</div>

<div class="patient-form-container" id="eduForm">

<h2>New Education Topic</h2>

<form id="eduFormData">

<div class="form-grid">

<div class="form-group">
<label>Title</label>
<input type="text" id="eduTitle" name="title" required>
</div>

<div class="form-group">
<label>Category</label>
<select id="eduCategory" name="category">
<option>Pregnancy Care</option>
<option>Nutrition</option>
<option>Baby Care</option>
<option>Immunization</option>
<option>Postnatal Care</option>
</select>
</div>

<div class="form-group" style="grid-column: span 2;">
<label>Description</label>
<textarea id="eduText" name="description" rows="4" style="padding:12px;"></textarea>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelEduBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save Topic
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>Education Topics</h2>
<span id="eduCount">0 Topics</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Title</th>
<th>Category</th>
<th>Description</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody id="eduTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>

<?php if (canSee('users')): ?>
<main class="main-content" id="usersContent">

<div class="page-header">

<div>
<h1>Manage Users</h1>
<p>Add and manage staff accounts and their roles</p>
</div>

<button class="add-btn" id="addUserBtn">
<i class="fa-solid fa-plus"></i>
New User
</button>

</div>

<div class="patient-form-container" id="userForm">

<h2>Staff Account</h2>

<form id="userFormData">

<div class="form-grid">

<div class="form-group">
<label>Full Name</label>
<input type="text" id="userFullName" name="full_name" required>
</div>

<div class="form-group">
<label>Email Address</label>
<input type="email" id="userEmail" name="email" required>
</div>

<div class="form-group password-field">
<label>Password <span id="userPasswordHint" style="font-weight:normal;color:#888;"></span></label>
<input type="password" id="userPassword" name="password" minlength="8" placeholder="Min. 8 characters">
<i class="fa-solid fa-eye toggle-password" data-target="userPassword" title="Show password"></i>
</div>

<div class="form-group">
<label>Role</label>
<select id="userRole" name="role" required>
<option value="Admin">Admin</option>
<option value="Doctor">Doctor</option>
<option value="Nurse" selected>Nurse</option>
<option value="CHW">Community Health Worker</option>
<option value="Manager">Manager</option>
</select>
</div>

<div class="form-group">
<label>Status</label>
<select id="userIsActive" name="is_active">
<option value="1" selected>Active</option>
<option value="0">Inactive (deactivated)</option>
</select>
</div>

</div>

<div class="form-buttons">

<button type="button" class="cancel-btn" id="cancelUserBtn">
Cancel
</button>

<button type="submit" class="save-btn">
Save User
</button>

</div>

</form>

</div>

<div class="patient-table-container">

<div class="table-header">
<h2>Staff Accounts</h2>
<span id="userCount">0 Users</span>
</div>

<div class="table-wrapper">

<table>

<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="userTableBody"></tbody>

</table>

</div>

</div>

</main>
<?php endif; ?>
</div>
<script>
window.MHS_PERMISSIONS = {
patient: <?php echo canWrite('patient') ? 'true' : 'false'; ?>,
appointment: <?php echo canWrite('appointment') ? 'true' : 'false'; ?>,
antenatal: <?php echo canWrite('antenatal') ? 'true' : 'false'; ?>,
postnatal: <?php echo canWrite('postnatal') ? 'true' : 'false'; ?>,
immunization: <?php echo canWrite('immunization') ? 'true' : 'false'; ?>,
high: <?php echo canWrite('high') ? 'true' : 'false'; ?>,
message: <?php echo canWrite('message') ? 'true' : 'false'; ?>,
education: <?php echo canWrite('education') ? 'true' : 'false'; ?>,
users: <?php echo canWrite('users') ? 'true' : 'false'; ?>
};
</script>
<script src="dashboard.js"></script>
<script src="dashboard.js"></script>
<script src="auth.js"></script>
</body>
</html>
