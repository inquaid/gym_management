<?php
require_once '../include/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Reports</h3>
                </div>
                <div class="card-body">
                    <form id="reportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="report_type">Report Type</label>
                                    <select class="form-control" id="report_type" name="report_type" required>
                                        <option value="">Select Report Type</option>
                                        <option value="membership">Membership Report</option>
                                        <option value="attendance">Attendance Report</option>
                                        <option value="revenue">Revenue Report</option>
                                        <option value="equipment">Equipment Report</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="format">Format</label>
                                    <select class="form-control" id="format" name="format" required>
                                        <option value="">Select Format</option>
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Report Preview -->
                    <div id="reportPreview" class="mt-4" style="display: none;">
                        <h4>Report Preview</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="previewTable">
                                <thead>
                                    <tr>
                                        <!-- Table headers will be dynamically added -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Table data will be dynamically added -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const firstDayOfMonth = new Date();
    firstDayOfMonth.setDate(1);
    const firstDayStr = firstDayOfMonth.toISOString().split('T')[0];
    
    $('#start_date').val(firstDayStr);
    $('#end_date').val(today);

    // Handle form submission
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '../actions/reports/generate_report.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    // Download the file
                    window.location.href = response.filename;
                } else {
                    showAlert('danger', response.message);
                }
            }
        });
    });
});

// Show alert message
function showAlert(type, message) {
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);
    $('.card-body').prepend(alert);
}
</script>

<?php
require_once '../include/footer.php';
?> 