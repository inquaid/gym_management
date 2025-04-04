<?php
require_once '../../include/session.php';
require_once '../../include/db.php';

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $report_type = filter_input(INPUT_POST, 'report_type', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $format = filter_input(INPUT_POST, 'format', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$report_type || !$start_date || !$end_date || !$format) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Validate dates
    if (!strtotime($start_date) || !strtotime($end_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
        exit;
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        echo json_encode(['status' => 'error', 'message' => 'End date must be after start date']);
        exit;
    }

    // Validate report type
    $valid_report_types = ['membership', 'attendance', 'revenue', 'equipment'];
    if (!in_array($report_type, $valid_report_types)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
        exit;
    }

    // Validate format
    $valid_formats = ['pdf', 'excel', 'csv'];
    if (!in_array($format, $valid_formats)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid format']);
        exit;
    }

    try {
        // Generate report based on type
        $report_data = [];
        switch ($report_type) {
            case 'membership':
                $report_data = generateMembershipReport($conn, $start_date, $end_date);
                break;
            case 'attendance':
                $report_data = generateAttendanceReport($conn, $start_date, $end_date);
                break;
            case 'revenue':
                $report_data = generateRevenueReport($conn, $start_date, $end_date);
                break;
            case 'equipment':
                $report_data = generateEquipmentReport($conn, $start_date, $end_date);
                break;
        }

        // Generate file based on format
        $filename = generateReportFile($report_data, $format, $report_type, $start_date, $end_date);

        echo json_encode([
            'status' => 'success',
            'message' => 'Report generated successfully',
            'filename' => $filename
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error generating report: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

// Generate membership report
function generateMembershipReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                COUNT(*) as total_members,
                COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_members,
                COUNT(CASE WHEN status = 'Inactive' THEN 1 END) as inactive_members,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_members,
                COUNT(CASE WHEN expiry_date BETWEEN ? AND ? THEN 1 END) as expiring_members
              FROM members";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Generate attendance report
function generateAttendanceReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                COUNT(*) as total_visits,
                COUNT(DISTINCT member_id) as unique_members,
                DATE(visit_date) as date
              FROM attendance
              WHERE visit_date BETWEEN ? AND ?
              GROUP BY DATE(visit_date)
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Generate revenue report
function generateRevenueReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                SUM(amount) as total_revenue,
                COUNT(*) as total_transactions,
                DATE(transaction_date) as date
              FROM payments
              WHERE transaction_date BETWEEN ? AND ?
              GROUP BY DATE(transaction_date)
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Generate equipment report
function generateEquipmentReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                COUNT(*) as total_equipment,
                COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_equipment,
                COUNT(CASE WHEN status = 'In Use' THEN 1 END) as in_use_equipment,
                COUNT(CASE WHEN status = 'Maintenance' THEN 1 END) as maintenance_equipment,
                COUNT(CASE WHEN status = 'Damaged' THEN 1 END) as damaged_equipment
              FROM equipment";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Generate report file
function generateReportFile($data, $format, $report_type, $start_date, $end_date) {
    $filename = "report_{$report_type}_{$start_date}_{$end_date}";
    
    switch ($format) {
        case 'pdf':
            require_once '../../vendor/tecnickcom/tcpdf/tcpdf.php';
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Gym Management System');
            $pdf->SetAuthor('Admin');
            $pdf->SetTitle(ucfirst($report_type) . ' Report');
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 12);
            
            // Add content
            $pdf->Cell(0, 10, ucfirst($report_type) . ' Report', 0, 1, 'C');
            $pdf->Cell(0, 10, "Period: {$start_date} to {$end_date}", 0, 1, 'C');
            $pdf->Ln(10);
            
            // Add data
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $pdf->Cell(0, 10, ucfirst(str_replace('_', ' ', $key)) . ':', 0, 1);
                        foreach ($value as $subkey => $subvalue) {
                            $pdf->Cell(40, 10, ucfirst(str_replace('_', ' ', $subkey)) . ':', 0, 0);
                            $pdf->Cell(0, 10, $subvalue, 0, 1);
                        }
                    } else {
                        $pdf->Cell(40, 10, ucfirst(str_replace('_', ' ', $key)) . ':', 0, 0);
                        $pdf->Cell(0, 10, $value, 0, 1);
                    }
                }
            }
            
            // Save file
            $filename .= '.pdf';
            $pdf->Output($filename, 'F');
            break;
            
        case 'excel':
            require_once '../../vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add headers
            $sheet->setCellValue('A1', ucfirst($report_type) . ' Report');
            $sheet->setCellValue('A2', "Period: {$start_date} to {$end_date}");
            
            // Add data
            $row = 4;
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $sheet->setCellValue("A{$row}", ucfirst(str_replace('_', ' ', $key)));
                        $row++;
                        foreach ($value as $subkey => $subvalue) {
                            $sheet->setCellValue("A{$row}", ucfirst(str_replace('_', ' ', $subkey)));
                            $sheet->setCellValue("B{$row}", $subvalue);
                            $row++;
                        }
                    } else {
                        $sheet->setCellValue("A{$row}", ucfirst(str_replace('_', ' ', $key)));
                        $sheet->setCellValue("B{$row}", $value);
                        $row++;
                    }
                }
            }
            
            // Save file
            $filename .= '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filename);
            break;
            
        case 'csv':
            $filename .= '.csv';
            $fp = fopen($filename, 'w');
            
            // Add headers
            fputcsv($fp, [ucfirst($report_type) . ' Report']);
            fputcsv($fp, ["Period: {$start_date} to {$end_date}"]);
            fputcsv($fp, []);
            
            // Add data
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        fputcsv($fp, [ucfirst(str_replace('_', ' ', $key))]);
                        foreach ($value as $subkey => $subvalue) {
                            fputcsv($fp, [
                                ucfirst(str_replace('_', ' ', $subkey)),
                                $subvalue
                            ]);
                        }
                        fputcsv($fp, []);
                    } else {
                        fputcsv($fp, [
                            ucfirst(str_replace('_', ' ', $key)),
                            $value
                        ]);
                    }
                }
            }
            
            fclose($fp);
            break;
    }
    
    return $filename;
}
?> 