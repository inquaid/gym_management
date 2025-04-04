// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle sidebar toggle on mobile
    const sidebarToggle = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Handle form submissions with confirmation
    const forms = document.querySelectorAll('form[data-confirm]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Handle search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Handle file input preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.querySelector(this.dataset.preview);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Handle date picker initialization
    const datePickers = document.querySelectorAll('.datepicker');
    datePickers.forEach(input => {
        if (typeof flatpickr !== 'undefined') {
            flatpickr(input, {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }
    });

    // Handle time picker initialization
    const timePickers = document.querySelectorAll('.timepicker');
    timePickers.forEach(input => {
        if (typeof flatpickr !== 'undefined') {
            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                allowInput: true
            });
        }
    });

    // Handle select2 initialization
    const select2Elements = document.querySelectorAll('.select2');
    select2Elements.forEach(select => {
        if (typeof $(select).select2 !== 'undefined') {
            $(select).select2({
                theme: 'bootstrap-5'
            });
        }
    });

    // Handle data table initialization
    const dataTables = document.querySelectorAll('.datatable');
    dataTables.forEach(table => {
        if (typeof $(table).DataTable !== 'undefined') {
            $(table).DataTable({
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    });

    // Handle notification dismissal
    const notifications = document.querySelectorAll('.alert-dismissible');
    notifications.forEach(notification => {
        const closeButton = notification.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                notification.classList.add('fade');
                setTimeout(() => {
                    notification.remove();
                }, 150);
            });
        }
    });

    // Handle auto-hide notifications
    const autoHideNotifications = document.querySelectorAll('.alert[data-autohide]');
    autoHideNotifications.forEach(notification => {
        const delay = parseInt(notification.dataset.autohide) || 5000;
        setTimeout(() => {
            notification.classList.add('fade');
            setTimeout(() => {
                notification.remove();
            }, 150);
        }, delay);
    });
}); 