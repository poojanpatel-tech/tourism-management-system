/**
 * Public Website Javascript
 */
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alertEl) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});
