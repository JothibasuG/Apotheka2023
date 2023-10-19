<?php
// Include the mPDF library
require_once $GLOBALS['vendor_dir'] . "/mpdf_2/vendor/autoload.php";

// Get the patient ID from the URL query parameter
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';

// Check if a patient ID is provided
if (empty($patient_id)) {
    echo 'Patient ID is missing.';
    exit;
}

// Initialize mPDF
$mpdf = new \Mpdf\Mpdf();

// Generate the PDF content (replace with your own content)
$patient_name = 'Jothibasu'; // Replace with the patient's name
$consent_html = '<p>This is the patient consent form for ' . $patient_name . '.</p>'; // Replace with your consent form HTML

// Write HTML content to PDF
$mpdf->WriteHTML($consent_html);

// Output the PDF to the browser
$mpdf->Output();

// Exit to prevent further processing
exit;
?>
