
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".toggle-password").forEach(function (icon) {
        icon.addEventListener("click", function () {
            const targetId = icon.getAttribute("data-target");
            const input = document.getElementById(targetId);
            if (!input) return;

            const willShow = input.type === "password";
            input.type = willShow ? "text" : "password";
            icon.classList.toggle("fa-eye", !willShow);
            icon.classList.toggle("fa-eye-slash", willShow);
            icon.setAttribute("title", willShow ? "Hide password" : "Show password");
        });
    });
});