document.addEventListener("DOMContentLoaded", function () {

// ---- Usalama: zuia XSS kwa kufunika data yoyote inayotoka database
// kabla ya kuiweka ndani ya innerHTML. Namba za id (zinazotumika kwenye
// onclick) HAZIFUNIKWI kwa makusudi - zinatoka database kama namba tupu.
function escapeHTML(str) {
if (str === null || str === undefined) return "";
return String(str)
.replace(/&/g, "&amp;")
.replace(/</g, "&lt;")
.replace(/>/g, "&gt;")
.replace(/"/g, "&quot;")
.replace(/'/g, "&#39;");
}

// ---- RBAC: tengeneza vitufe vya Edit/Delete TU kama role ina ruhusa
// ya kuandika kwenye moduli husika (window.MHS_PERMISSIONS, iliyowekwa
// na dashboard.php kutoka kwa canWrite()). Kama hakuna ruhusa, badala
// ya vitufe inaonyesha "View only".
function actionButtonsHTML(moduleKey, id, editFnName, deleteFnName) {
const perms = window.MHS_PERMISSIONS || {};
if (!perms[moduleKey]) {
return `<span style="color:#999; font-size:12px;">View only</span>`;
}
return `
<button class="action-btn delete-btn" onclick="${deleteFnName}(${id})">
<i class="fa-solid fa-trash"></i>
</button>

<button class="action-btn delete-btn" onclick="${editFnName}(${id})" style="color:#007bff">
<i class="fa-solid fa-edit"></i>
</button>
`;
}

//  UX: toast, modal ya uthibitisho, "inapakia..." 
(function injectUXStyles() {
if (document.getElementById("mhsUXStyles")) return;
const style = document.createElement("style");
style.id = "mhsUXStyles";
style.textContent = `
#mhsToastContainer {
position: fixed;
top: 20px;
right: 20px;
z-index: 9999;
display: flex;
flex-direction: column;
gap: 10px;
}
.mhs-toast {
min-width: 240px;
max-width: 360px;
padding: 14px 18px;
border-radius: 8px;
color: #fff;
font-size: 14px;
line-height: 1.4;
box-shadow: 0 4px 14px rgba(0,0,0,0.18);
opacity: 0;
transform: translateX(24px);
transition: opacity 0.25s ease, transform 0.25s ease;
}
.mhs-toast.show { opacity: 1; transform: translateX(0); }
.mhs-toast.success { background: #2e7d32; }
.mhs-toast.error { background: #c62828; }
.mhs-confirm-overlay {
position: fixed;
inset: 0;
background: rgba(0,0,0,0.45);
display: flex;
align-items: center;
justify-content: center;
z-index: 10000;
}
.mhs-confirm-box {
background: #fff;
padding: 24px;
border-radius: 10px;
max-width: 360px;
width: 90%;
box-shadow: 0 8px 30px rgba(0,0,0,0.25);
text-align: center;
}
.mhs-confirm-box p {
margin: 0 0 20px;
font-size: 15px;
color: #222;
}
.mhs-confirm-actions {
display: flex;
gap: 10px;
justify-content: center;
}
.mhs-confirm-actions button {
padding: 8px 22px;
border: none;
border-radius: 6px;
font-size: 14px;
cursor: pointer;
}
.mhs-confirm-cancel { background: #e0e0e0; color: #333; }
.mhs-confirm-cancel:hover { background: #d0d0d0; }
.mhs-confirm-ok { background: #c62828; color: #fff; }
.mhs-confirm-ok:hover { background: #a82121; }
.mhs-loading-row {
text-align: center !important;
padding: 28px !important;
color: #888;
font-style: italic;
}
.mhs-risk-badge {
display: inline-block;
padding: 3px 10px;
border-radius: 12px;
font-size: 12px;
font-weight: 600;
color: #fff;
white-space: nowrap;
}
.mhs-risk-low { background: #2e7d32; }
.mhs-risk-medium { background: #f57c00; }
.mhs-risk-high { background: #c62828; }
.mhs-auto-tag {
display: inline-block;
margin-left: 6px;
padding: 2px 8px;
border-radius: 10px;
font-size: 11px;
color: #555;
}
`;
document.head.appendChild(style);
})();

let toastContainer = document.getElementById("mhsToastContainer");
if (!toastContainer) {
toastContainer = document.createElement("div");
toastContainer.id = "mhsToastContainer";
document.body.appendChild(toastContainer);
}

function showToast(message, type = "success") {
const toast = document.createElement("div");
toast.className = `mhs-toast ${type}`;
toast.textContent = message;
toastContainer.appendChild(toast);
requestAnimationFrame(() => toast.classList.add("show"));
setTimeout(() => {
toast.classList.remove("show");
setTimeout(() => toast.remove(), 300);
}, 3000);
}

function showConfirm(message) {
return new Promise(resolve => {
const overlay = document.createElement("div");
overlay.className = "mhs-confirm-overlay";
overlay.innerHTML = `
<div class="mhs-confirm-box">
<p>${escapeHTML(message)}</p>
<div class="mhs-confirm-actions">
<button type="button" class="mhs-confirm-cancel">CANCEL</button>
<button type="button" class="mhs-confirm-ok">DELETE</button>
</div>
</div>
`;
document.body.appendChild(overlay);

overlay.querySelector(".mhs-confirm-cancel").addEventListener("click", () => {
overlay.remove();
resolve(false);
});
overlay.querySelector(".mhs-confirm-ok").addEventListener("click", () => {
overlay.remove();
resolve(true);
});
});
}

function showLoadingRow(tbody) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">Loading...</td></tr>`;
}

function showErrorRow(tbody, message) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row" style="color:#c62828;">${escapeHTML(message)}</td></tr>`;
}

function todayISO() {
return new Date().toISOString().split("T")[0];
}

function setupSearch({ inputId, getAll, render, fields }) {
const input = document.getElementById(inputId);

function apply() {
const query = input ? input.value.trim().toLowerCase() : "";
const all = getAll();
if (query === "") {
render(all);
return;
}
const filtered = all.filter(row =>
fields.some(f => (row[f] || "").toString().toLowerCase().includes(query))
);
render(filtered);
}

if (input) {
input.addEventListener("input", apply);
}
return apply;
}

function riskBadgeHTML(level) {
if (!level) return "";
const cls = level === "High" ? "mhs-risk-high" : level === "Medium" ? "mhs-risk-medium" : "mhs-risk-low";
return `<span class="mhs-risk-badge ${cls}">${escapeHTML(level)}</span>`;
}


const navItems = document.querySelectorAll(".dash .home[data-target]");
const mainSections = document.querySelectorAll(".main-content");
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const overlay = document.getElementById("overlay");


const sectionLoaders = {
patientContent: () => loadPatients(),
appointmentContent: () => loadAppointments(),
antenatalContent: () => loadAnc(),
postnatalContent: () => loadPnc(),
immunizationContent: () => loadImmunizations(),
highContent: () => loadHighRisk(),
reportContent: () => loadReports(),
messageContent: () => loadMessages(),
educationContent: () => loadEducation(),
usersContent: () => loadUsers()
};

navItems.forEach(item => {
item.addEventListener("click", function (e) {
e.preventDefault();
navItems.forEach(i => i.classList.remove("active"));
this.classList.add("active");

const targetId = this.getAttribute("data-target");
mainSections.forEach(section => {
section.style.display = section.id === targetId ? "block" : "none";
});

if (sectionLoaders[targetId]) {
sectionLoaders[targetId]();
}

sidebar.classList.remove("active");
overlay.classList.remove("active");
});
});

if (toggleBtn) {
toggleBtn.addEventListener("click", function () {
sidebar.classList.toggle("active");
overlay.classList.toggle("active");
});
}

if (overlay) {
overlay.addEventListener("click", function () {
sidebar.classList.remove("active");
overlay.classList.remove("active");
});
}

//  PATIENTS 
const addPatientBtn = document.getElementById("addPatientBtn");
const cancelPatientBtn = document.getElementById("cancelPatientBtn");
const patientForm = document.getElementById("patientForm");
const registrationForm = document.getElementById("patientRegistrationForm");
let isEdit = false;

if (addPatientBtn) {
addPatientBtn.addEventListener("click", () => {
isEdit = false;
registrationForm.reset();
patientForm.style.display = "block";
});
}
if (cancelPatientBtn) {
cancelPatientBtn.addEventListener("click", () => {
patientForm.style.display = "none";
registrationForm.reset();
isEdit = false;
});
}

if (registrationForm) {
registrationForm.addEventListener("submit", function (e) {
e.preventDefault();

const formData = new FormData(this);
const action = isEdit ? "update" : "create";

fetch(`patient_action.php?action=${action}`, {
method: "POST",
body: formData
})
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message, "success");
patientForm.style.display = "none";
registrationForm.reset();
loadPatients();
isEdit = false;
} else {
showToast(data.message, "error");
}
})
.catch(err => {
console.error("Error saving patient:", err);
showToast("Network error. Try Again.", "error");
});
});
}

let allPatients = [];

function renderPatientRows(patients) {
const tbody = document.getElementById("patientTableBody");
const countSpan = document.getElementById("patientCount");
tbody.innerHTML = "";
countSpan.textContent = `${patients.length} Patients`;

if (patients.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No patient found.</td></tr>`;
return;
}

patients.forEach(patient => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(patient.patient_id)}</td>
<td>${escapeHTML(patient.full_name)}</td>
<td>${escapeHTML(patient.phone)}</td>
<td>${escapeHTML(patient.blood_group)}</td>
<td>${escapeHTML(patient.pregnancy_status)}</td>
<td>${actionButtonsHTML('patient', patient.id, 'editPatient', 'deletePatient')}</td>
`;
tbody.appendChild(tr);
});
}

function loadPatients() {
const tbody = document.getElementById("patientTableBody");
showLoadingRow(tbody);

fetch("patient_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allPatients = resData.data;
applyPatientSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load patients.");
}
})
.catch(err => {
console.error("Error loading patients:", err);
showErrorRow(tbody, "Could not load patients. Check your connection or session and try again.");
});
}

function applyPatientSearch() {
const query = patientSearchInput ? patientSearchInput.value.trim().toLowerCase() : "";
if (query === "") {
renderPatientRows(allPatients);
return;
}
const filtered = allPatients.filter(p =>
(p.full_name || "").toLowerCase().includes(query) ||
(p.patient_id || "").toLowerCase().includes(query)
);
renderPatientRows(filtered);
}

const patientSearchInput = document.getElementById("patientSearch");
if (patientSearchInput) {
patientSearchInput.addEventListener("input", applyPatientSearch);
}

window.deletePatient = function (id) {
showConfirm("Are you sure you want to delete this record of patient?").then(confirmed => {
if (!confirmed) return;
fetch(`patient_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Record deleted.", "success");
loadPatients();
} else {
showToast("Failed to delete the record.", "error");
}
});
});
};

window.editPatient = function (id) {
fetch(`patient_action.php?action=get&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
const p = data.patient;
patientForm.style.display = "block";
isEdit = true;
document.querySelector("input[name='id']").value = p.id;
document.querySelector("input[name='patient_id']").value = p.patient_id || '';
document.querySelector("input[name='full_name']").value = p.full_name || '';
document.querySelector("input[name='dob']").value = p.dob || '';
document.querySelector("input[name='phone']").value = p.phone || '';
document.querySelector("input[name='address']").value = p.address || '';
document.querySelector("select[name='blood_group']").value = p.blood_group || '';
document.querySelector("select[name='pregnancy_status']").value = p.pregnancy_status || '';
document.querySelector("input[name='emergency_contact']").value = p.emergency_contact || '';

} else {
showToast("Failed to get the information of patient.", "error");
}
});
};

function setupEditableModule({ addBtnId, cancelBtnId, containerId, formId, endpoint, onSaved, fieldNames, onSuccess }) {
const addBtn = document.getElementById(addBtnId);
const cancelBtn = document.getElementById(cancelBtnId);
const container = document.getElementById(containerId);
const form = document.getElementById(formId);
let isEditing = false;

if (addBtn && container && form) {
addBtn.addEventListener("click", () => {
isEditing = false;
form.reset();
delete form.dataset.editId;
container.style.display = "block";
});
}
if (cancelBtn && container && form) {
cancelBtn.addEventListener("click", () => {
container.style.display = "none";
form.reset();
isEditing = false;
delete form.dataset.editId;
});
}
if (form) {
form.addEventListener("submit", function (e) {
e.preventDefault();
const formData = new FormData(this);
const action = isEditing ? "update" : "create";

if (isEditing) {
formData.set("id", form.dataset.editId || "");
}

fetch(`${endpoint}?action=${action}`, {
method: "POST",
body: formData
})
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message, "success");
container.style.display = "none";
form.reset();
isEditing = false;
delete form.dataset.editId;
if (onSuccess) onSuccess(data);
if (onSaved) onSaved();
} else {
showToast(data.message, "error");
}
})
.catch(err => {
console.error(`Error saving (${endpoint}):`, err);
showToast("Network error. Try again.", "error");
});
});
}

return {
editRecord: function (id) {
fetch(`${endpoint}?action=get&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
const record = data.record;
isEditing = true;
form.dataset.editId = record.id;
container.style.display = "block";
fieldNames.forEach(name => {
const field = form.querySelector(`[name='${name}']`);
if (field) field.value = record[name] ?? '';
});
} else {
showToast("Failed to get information .", "error");
}
});
}
};
}

//  APPOINTMENTS 
const appointmentForm = document.getElementById("appointmentForm");
const appointmentFormData = document.getElementById("appointmentFormData");
const addAppointmentBtn = document.getElementById("addAppointmentBtn");
const cancelAppointmentBtn = document.getElementById("cancelAppointmentBtn");
let isEditAppointment = false;
const appDateInput = document.getElementById("appDate");
if (appDateInput) {
appDateInput.min = todayISO();
}

if (addAppointmentBtn) {
addAppointmentBtn.addEventListener("click", () => {
isEditAppointment = false;
appointmentFormData.reset();
if (appDateInput) appDateInput.min = todayISO();
appointmentForm.style.display = "block";
});
}
if (cancelAppointmentBtn) {
cancelAppointmentBtn.addEventListener("click", () => {
appointmentForm.style.display = "none";
appointmentFormData.reset();
isEditAppointment = false;
});
}
if (appointmentFormData) {
appointmentFormData.addEventListener("submit", function (e) {
e.preventDefault();
const formData = new FormData(this);
const action = isEditAppointment ? "update" : "create";
if (!isEditAppointment) {
const dateVal = formData.get("appointment_date");
if (dateVal && dateVal < todayISO()) {
showToast("The appointment date can't be earlier than today.", "error");
return;
}
}

if (isEditAppointment) {
formData.set("id", appointmentFormData.dataset.editId || "");
}

fetch(`appointment_action.php?action=${action}`, {
method: "POST",
body: formData
})
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message, "success");
appointmentForm.style.display = "none";
appointmentFormData.reset();
isEditAppointment = false;
loadAppointments();
} else {
showToast(data.message, "error");
}
})
.catch(err => {
console.error("Error saving appointment:", err);
showToast("Network error. Try Again.", "error");
});
});
}

let allAppointments = [];

function renderAppointmentRows(rows) {
const tbody = document.getElementById("appointmentTableBody");
const countSpan = document.getElementById("appointmentCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Appointments`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No appointment found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.patient_name)}</td>
<td>${escapeHTML(row.appointment_date)}</td>
<td>${escapeHTML(row.appointment_time)}</td>
<td>${escapeHTML(row.service)}</td>
<td>${escapeHTML(row.status)}</td>
<td>${actionButtonsHTML('appointment', row.id, 'editApointment', 'deleteAppointment')}</td>
`;
tbody.appendChild(tr);
});
}

function loadAppointments() {
const tbody = document.getElementById("appointmentTableBody");
showLoadingRow(tbody);

fetch("appointment_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allAppointments = resData.data;
applyAppointmentSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load appointments.");
}
})
.catch(err => {
console.error("Error loading appointments:", err);
showErrorRow(tbody, "Could not load appointments. Check your connection or session and try again.");
});
}

const applyAppointmentSearch = setupSearch({
inputId: "appointmentSearch",
getAll: () => allAppointments,
render: renderAppointmentRows,
fields: ["patient_name"]
});

window.deleteAppointment = function (id) {
showConfirm("Are you sure you want to Delete this Appointment?").then(confirmed => {
if (!confirmed) return;
fetch(`appointment_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Appointment Deleted.", "success");
loadAppointments();
} else {
showToast("Failed to Delete Appointment.", "error");
}
});
});
};

window.editApointment = function (id) {
fetch(`appointment_action.php?action=get&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
const a = data.appointment;
isEditAppointment = true;
appointmentForm.style.display = "block";
if (appDateInput) appDateInput.removeAttribute("min");
appointmentFormData.querySelector("[name='patient_name']").value = a.patient_name || '';
appointmentFormData.querySelector("[name='appointment_date']").value = a.appointment_date || '';
appointmentFormData.querySelector("[name='appointment_time']").value = a.appointment_time || '';
appointmentFormData.querySelector("[name='service']").value = a.service || '';
appointmentFormData.querySelector("[name='status']").value = a.status || 'Pending';
appointmentFormData.dataset.editId = a.id;
} else {
showToast("Failed to get Apointment Data.", "error");
}
});
};

//  ANTENATAL CARE 
const ancModule = setupEditableModule({
addBtnId: "addAncBtn",
cancelBtnId: "cancelAncBtn",
containerId: "ancForm",
formId: "ancFormData",
endpoint: "anc_action.php",
onSaved: loadAnc,
fieldNames: ["patient_name", "lmp_date", "edd_date", "gestational_weeks", "blood_pressure", "weight", "temperature", "fetal_heart_rate"],
onSuccess: (data) => {
if (data.risk_alert) {
const reasons = Array.isArray(data.risk_reasons) && data.risk_reasons.length
? " (" + data.risk_reasons.join(", ") + ")"
: "";
showToast(
` HIGH RISK ALERT: "${data.patient_name || ''}" has been classified as HIGH RISK${reasons} — added to High Risk Cases.`,
"error"
);
}
}
});
window.editAnc = (id) => ancModule.editRecord(id);

let allAnc = [];

function renderAncRows(rows) {
const tbody = document.getElementById("ancTableBody");
const countSpan = document.getElementById("ancCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Records`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No Data Found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.patient_name)}</td>
<td>${escapeHTML(row.lmp_date ?? "")}</td>
<td>${escapeHTML(row.edd_date ?? "")}</td>
<td>${escapeHTML(row.gestational_weeks ?? "")}</td>
<td>${escapeHTML(row.blood_pressure ?? "")}</td>
<td>${escapeHTML(row.weight ?? "")}</td>
<td>${escapeHTML(row.fetal_heart_rate ?? "")}</td>
<td>${riskBadgeHTML(row.risk_level)}</td>
<td>${actionButtonsHTML('antenatal', row.id, 'editAnc', 'deleteAnc')}</td>
`;
tbody.appendChild(tr);
});
}

function loadAnc() {
const tbody = document.getElementById("ancTableBody");
showLoadingRow(tbody);

fetch("anc_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allAnc = resData.data;
applyAncSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load ANC records.");
}
})
.catch(err => {
console.error("Error loading ANC records:", err);
showErrorRow(tbody, "Could not load ANC records. Check your connection or session and try again.");
});
}

const applyAncSearch = setupSearch({
inputId: "ancSearch",
getAll: () => allAnc,
render: renderAncRows,
fields: ["patient_name"]
});

window.deleteAnc = function (id) {
showConfirm("Are you sure you want to Delete this record of ANC?").then(confirmed => {
if (!confirmed) return;
fetch(`anc_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Record Deleted.", "success");
loadAnc();
} else {
showToast("Failed To delete record.", "error");
}
});
});
};

//  POSTNATAL CARE 
const pncModule = setupEditableModule({
addBtnId: "addPncBtn",
cancelBtnId: "cancelPncBtn",
containerId: "pncForm",
formId: "pncFormData",
endpoint: "pnc_action.php",
onSaved: loadPnc,
fieldNames: ["mother_name", "father_name", "delivery_date", "delivery_method", "mother_condition", "blood_pressure", "temperature", "bleeding", "baby_name", "baby_sex", "birth_weight", "feeding"]
});
window.editPnc = (id) => pncModule.editRecord(id);

let allPnc = [];

function renderPncRows(rows) {
const tbody = document.getElementById("pncTableBody");
const countSpan = document.getElementById("pncCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Records`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No record Found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.mother_name)}</td>
<td>${escapeHTML(row.father_name ?? "")}</td>
<td>${escapeHTML(row.delivery_date ?? "")}</td>
<td>${escapeHTML(row.delivery_method ?? "")}</td>
<td>${escapeHTML(row.mother_condition ?? "")}</td>
<td>${escapeHTML(row.baby_name ?? "")}</td>
<td>${escapeHTML(row.birth_weight ?? "")}</td>
<td>${escapeHTML(row.feeding ?? "")}</td>
<td>${actionButtonsHTML('postnatal', row.id, 'editPnc', 'deletePnc')}</td>
`;
tbody.appendChild(tr);
});
}

function loadPnc() {
const tbody = document.getElementById("pncTableBody");
showLoadingRow(tbody);

fetch("pnc_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allPnc = resData.data;
applyPncSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load PNC records.");
}
})
.catch(err => {
console.error("Error loading PNC records:", err);
showErrorRow(tbody, "Could not load PNC records. Check your connection or session and try again.");
});
}

const applyPncSearch = setupSearch({
inputId: "pncSearch",
getAll: () => allPnc,
render: renderPncRows,
fields: ["mother_name", "baby_name"]
});

window.deletePnc = function (id) {
showConfirm("Are you sure you want to delete this record of PNC?").then(confirmed => {
if (!confirmed) return;
fetch(`pnc_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Record Deleted.", "success");
loadPnc();
} else {
showToast("Failed to delete record.", "error");
}
});
});
};

//  IMMUNIZATION 
const immModule = setupEditableModule({
addBtnId: "addImmBtn",
cancelBtnId: "cancelImmBtn",
containerId: "immForm",
formId: "immFormData",
endpoint: "immunization_action.php",
onSaved: loadImmunizations,
fieldNames: ["patient_name", "vaccine", "dose", "date_given", "next_due_date", "status"]
});
window.editImmunization = (id) => immModule.editRecord(id);

let allImmunizations = [];

function renderImmunizationRows(rows) {
const tbody = document.getElementById("immTableBody");
const countSpan = document.getElementById("immCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Records`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No record Found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.patient_name)}</td>
<td>${escapeHTML(row.vaccine)}</td>
<td>${escapeHTML(row.dose ?? "")}</td>
<td>${escapeHTML(row.date_given ?? "")}</td>
<td>${escapeHTML(row.next_due_date ?? "")}</td>
<td>${escapeHTML(row.status)}</td>
<td>${actionButtonsHTML('immunization', row.id, 'editImmunization', 'deleteImmunization')}</td>
`;
tbody.appendChild(tr);
});
}

function loadImmunizations() {
const tbody = document.getElementById("immTableBody");
showLoadingRow(tbody);

fetch("immunization_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allImmunizations = resData.data;
applyImmunizationSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load immunization records.");
}
})
.catch(err => {
console.error("Error loading immunization records:", err);
showErrorRow(tbody, "Could not load immunization records. Check your connection or session and try again.");
});
}

const applyImmunizationSearch = setupSearch({
inputId: "immSearch",
getAll: () => allImmunizations,
render: renderImmunizationRows,
fields: ["patient_name", "vaccine"]
});

window.deleteImmunization = function (id) {
showConfirm("Are you sure you want to delete this record of Immunization?").then(confirmed => {
if (!confirmed) return;
fetch(`immunization_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Record Deleted.", "success");
loadImmunizations();
} else {
showToast("Failed to Delete record.", "error");
}
});
});
};

//  HIGH RISK 
const highModule = setupEditableModule({
addBtnId: "addHighBtn",
cancelBtnId: "cancelHighBtn",
containerId: "highForm",
formId: "highFormData",
endpoint: "highrisk_action.php",
onSaved: loadHighRisk,
fieldNames: ["patient_name", "gestational_weeks", "risk_level", "risk_factor", "followup_date", "status"]
});
window.editHighRisk = (id) => highModule.editRecord(id);

let allHighRisk = [];

function renderHighRiskRows(rows) {
const tbody = document.getElementById("highTableBody");
const countSpan = document.getElementById("highCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Records`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No case found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
const autoTag = row.auto_flagged == 1 ? `<span class="mhs-auto-tag" title="Automatically added by the system from an ANC visit"></span>` : "";
tr.innerHTML = `
<td>${escapeHTML(row.patient_name)}${autoTag}</td>
<td>${escapeHTML(row.gestational_weeks ?? "")}</td>
<td>${riskBadgeHTML(row.risk_level)}</td>
<td>${escapeHTML(row.risk_factor ?? "")}</td>
<td>${escapeHTML(row.followup_date ?? "")}</td>
<td>${escapeHTML(row.status)}</td>
<td>${actionButtonsHTML('high', row.id, 'editHighRisk', 'deleteHighRisk')}</td>
`;
tbody.appendChild(tr);
});
}

function loadHighRisk() {
const tbody = document.getElementById("highTableBody");
showLoadingRow(tbody);

fetch("highrisk_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allHighRisk = resData.data;
applyHighRiskSearch();
} else {
showErrorRow(tbody, resData.message || "Failed to load high risk cases.");
}
})
.catch(err => {
console.error("Error loading high risk cases:", err);
showErrorRow(tbody, "Could not load high risk cases. Check your connection or session and try again.");
});
}

const applyHighRiskSearch = setupSearch({
inputId: "highSearch",
getAll: () => allHighRisk,
render: renderHighRiskRows,
fields: ["patient_name"]
});

window.deleteHighRisk = function (id) {
showConfirm("Are you sure you want to delete this Case?").then(confirmed => {
if (!confirmed) return;
fetch(`highrisk_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Case Deleted.", "success");
loadHighRisk();
} else {
showToast("Failed to Delete Case.", "error");
}
});
});
};


// MESSAGES 
const msgModule = setupEditableModule({
addBtnId: "addMsgBtn",
cancelBtnId: "cancelMsgBtn",
containerId: "msgForm",
formId: "msgFormData",
endpoint: "message_action.php",
onSaved: loadMessages,
fieldNames: ["receiver", "subject", "message"]
});
window.editMessage = (id) => msgModule.editRecord(id);

function loadMessages() {
const tbody = document.getElementById("msgTableBody");
showLoadingRow(tbody);

fetch("message_action.php?action=list")
.then(res => res.json())
.then(resData => {
const countSpan = document.getElementById("msgCount");
tbody.innerHTML = "";

if (resData.status === "success") {
const rows = resData.data;
countSpan.textContent = `${rows.length} Messages`;

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.receiver)}</td>
<td>${escapeHTML(row.subject)}</td>
<td>${escapeHTML(row.created_at ?? "")}</td>
<td>${escapeHTML(row.status)}</td>
<td>${actionButtonsHTML('message', row.id, 'editMessage', 'deleteMessage')}</td>
`;
tbody.appendChild(tr);
});
} else {
showErrorRow(tbody, resData.message || "Failed to load messages.");
}
})
.catch(err => {
console.error("Error loading messages:", err);
showErrorRow(tbody, "Could not load messages. Check your connection or session and try again.");
});
}

window.deleteMessage = function (id) {
showConfirm("Are you sure you want to Delete this Message?").then(confirmed => {
if (!confirmed) return;
fetch(`message_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Message Deleted.", "success");
loadMessages();
} else {
showToast("Failed to Delete message.", "error");
}
});
});
};

//  EDUCATION
const eduModule = setupEditableModule({
addBtnId: "addEduBtn",
cancelBtnId: "cancelEduBtn",
containerId: "eduForm",
formId: "eduFormData",
endpoint: "education_action.php",
onSaved: loadEducation,
fieldNames: ["title", "category", "description"]
});
window.editEducation = (id) => eduModule.editRecord(id);

function loadEducation() {
const tbody = document.getElementById("eduTableBody");
showLoadingRow(tbody);

fetch("education_action.php?action=list")
.then(res => res.json())
.then(resData => {
const countSpan = document.getElementById("eduCount");
tbody.innerHTML = "";

if (resData.status === "success") {
const rows = resData.data;
countSpan.textContent = `${rows.length} Topics`;

rows.forEach(row => {
const tr = document.createElement("tr");
tr.innerHTML = `
<td>${escapeHTML(row.title)}</td>
<td>${escapeHTML(row.category ?? "")}</td>
<td>${escapeHTML(row.description ?? "")}</td>
<td>${escapeHTML(row.created_at ?? "")}</td>
<td>${actionButtonsHTML('education', row.id, 'editEducation', 'deleteEducation')}</td>
`;
tbody.appendChild(tr);
});
} else {
showErrorRow(tbody, resData.message || "Failed to load education topics.");
}
})
.catch(err => {
console.error("Error loading education topics:", err);
showErrorRow(tbody, "Could not load education topics. Check your connection or session and try again.");
});
}

window.deleteEducation = function (id) {
showConfirm("Are you Sure you want to Delete this topic?").then(confirmed => {
if (!confirmed) return;
fetch(`education_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "Topic deleted.", "success");
loadEducation();
} else {
showToast("Failed to delete Topic.", "error");
}
});
});
};

//  MANAGE USERS
const addUserBtn = document.getElementById("addUserBtn");
const cancelUserBtn = document.getElementById("cancelUserBtn");
const userForm = document.getElementById("userForm");
const userFormData = document.getElementById("userFormData");
const userPasswordInput = document.getElementById("userPassword");
const userPasswordHint = document.getElementById("userPasswordHint");
let isEditUser = false;

if (addUserBtn) {
addUserBtn.addEventListener("click", () => {
isEditUser = false;
userFormData.reset();
delete userFormData.dataset.editId;
if (userPasswordInput) {
userPasswordInput.setAttribute("required", "required");
}
if (userPasswordHint) userPasswordHint.textContent = "";
userForm.style.display = "block";
});
}
if (cancelUserBtn) {
cancelUserBtn.addEventListener("click", () => {
userForm.style.display = "none";
userFormData.reset();
isEditUser = false;
delete userFormData.dataset.editId;
});
}
if (userFormData) {
userFormData.addEventListener("submit", function (e) {
e.preventDefault();
const formData = new FormData(this);
const action = isEditUser ? "update" : "create";

if (isEditUser) {
formData.set("id", userFormData.dataset.editId || "");
}

fetch(`user_action.php?action=${action}`, {
method: "POST",
body: formData
})
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message, "success");
userForm.style.display = "none";
userFormData.reset();
isEditUser = false;
delete userFormData.dataset.editId;
loadUsers();
} else {
showToast(data.message, "error");
}
})
.catch(err => {
console.error("Error saving user:", err);
showToast("Network error. Try again.", "error");
});
});
}

let allUsers = [];

function renderUserRows(rows) {
const tbody = document.getElementById("userTableBody");
const countSpan = document.getElementById("userCount");
tbody.innerHTML = "";
countSpan.textContent = `${rows.length} Users`;

if (rows.length === 0) {
tbody.innerHTML = `<tr><td colspan="100%" class="mhs-loading-row">No users found.</td></tr>`;
return;
}

rows.forEach(row => {
const tr = document.createElement("tr");
const statusBadge = row.is_active == 1
? `<span class="mhs-risk-badge mhs-risk-low">Active</span>`
: `<span class="mhs-risk-badge mhs-risk-high">Inactive</span>`;
tr.innerHTML = `
<td>${escapeHTML(row.full_name)}</td>
<td>${escapeHTML(row.email)}</td>
<td>${escapeHTML(row.role)}</td>
<td>${statusBadge}</td>
<td>${actionButtonsHTML('users', row.id, 'editUser', 'deleteUser')}</td>
`;
tbody.appendChild(tr);
});
}

function loadUsers() {
const tbody = document.getElementById("userTableBody");
if (!tbody) return; // mtumiaji si Admin - section hii haipo kwenye HTML yake
showLoadingRow(tbody);

fetch("user_action.php?action=list")
.then(res => res.json())
.then(resData => {
if (resData.status === "success") {
allUsers = resData.data;
renderUserRows(allUsers);
} else {
showErrorRow(tbody, resData.message || "Failed to load users.");
}
})
.catch(err => {
console.error("Error loading users:", err);
showErrorRow(tbody, "Could not load users. Check your connection or session and try again.");
});
}

window.deleteUser = function (id) {
showConfirm("Are you sure you want to delete this user account? This cannot be undone.").then(confirmed => {
if (!confirmed) return;
fetch(`user_action.php?action=delete&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
showToast(data.message || "User deleted.", "success");
loadUsers();
} else {
showToast(data.message || "Failed to delete user.", "error");
}
});
});
};

window.editUser = function (id) {
fetch(`user_action.php?action=get&id=${id}`)
.then(res => res.json())
.then(data => {
if (data.status === "success") {
const u = data.record;
isEditUser = true;
userForm.style.display = "block";
userFormData.dataset.editId = u.id;
userFormData.querySelector("[name='full_name']").value = u.full_name || '';
userFormData.querySelector("[name='email']").value = u.email || '';
userFormData.querySelector("[name='role']").value = u.role || 'Nurse';
userFormData.querySelector("[name='is_active']").value = u.is_active == 1 ? "1" : "0";
if (userPasswordInput) {
userPasswordInput.value = "";
userPasswordInput.removeAttribute("required");
}
if (userPasswordHint) userPasswordHint.textContent = "(leave blank to keep current password)";
} else {
showToast(data.message || "Failed to get user data.", "error");
}
});
};


//  REPORTS
function loadReports() {
const filter = document.getElementById("reportFilter")
? document.getElementById("reportFilter").value
: "all";

fetch(`report_action.php?filter=${filter}`)
.then(res => res.json())
.then(resData => {
if (resData.status !== "success") return;
const d = resData.data;

document.getElementById("reportPatients").textContent = d.patients;
document.getElementById("reportAppointments").textContent = d.appointments;
document.getElementById("reportAnc").textContent = d.anc;
document.getElementById("reportPnc").textContent = d.pnc;
document.getElementById("reportImm").textContent = d.immunizations;
document.getElementById("reportHigh").textContent = d.high_risk;
document.getElementById("tablePatients").textContent = d.patients;
document.getElementById("tableAppointments").textContent = d.appointments;
document.getElementById("tableAnc").textContent = d.anc;
document.getElementById("tablePnc").textContent = d.pnc;
document.getElementById("tableImm").textContent = d.immunizations;
document.getElementById("tableHigh").textContent = d.high_risk;
});
}

const reportFilter = document.getElementById("reportFilter");
if (reportFilter) {
reportFilter.addEventListener("change", loadReports);
}
});
