/**
 * SpaceShare — Admin Panel JavaScript Interactive Engine
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("SpaceShare Admin Engine Initialized.");

    // Toggle Sidebar Mobile
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("admin-sidebar");
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed");
        });
    }

    // Auto-dismiss Alerts
    const alerts = document.querySelectorAll(".alert-dismissible");
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

/**
 * Confirm Action SweetAlert Wrapper
 */
function confirmAction(message, callback) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "Are you sure?",
            text: message,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#0284c7",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Yes, proceed!"
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    } else {
        if (confirm(message)) {
            callback();
        }
    }
}
