/**
 * Tourism Management System - Main JavaScript
 * Handles responsive sidebar, alert dismissal, and confirmations.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sidebar.classList.toggle('show');

            // Handle mobile backdrop
            if (window.innerWidth < 992) {
                let backdrop = document.querySelector('.sidebar-backdrop');
                if (sidebar.classList.contains('show')) {
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'sidebar-backdrop';
                        document.body.appendChild(backdrop);
                        backdrop.addEventListener('click', function () {
                            sidebar.classList.remove('show');
                            backdrop.remove();
                        });
                    }
                } else if (backdrop) {
                    backdrop.remove();
                }
            }
        });
    }

    // 2. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alertEl) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 3. Confirm dialog for delete actions
    document.addEventListener('click', function (e) {
        const confirmTarget = e.target.closest('[data-confirm]');
        if (confirmTarget) {
            const message = confirmTarget.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        }
    });

    // 4. Initialize Bootstrap tooltips if any
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
