document.addEventListener("DOMContentLoaded", function () {
const loginForm = document.querySelector(".loginform");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");

if (loginForm) {
loginForm.addEventListener("submit", function (event) {
const email = emailInput.value.trim();
const password = passwordInput.value.trim();

if (email === "" || password === "") {
event.preventDefault();
alert("Tafadhali jaza email na password.");
}
});
}
});
