<?php
// Author: G.Jothibasu
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("../../globals.php");

use OpenEMR\Core\Header;

session_start(); // Start the session

if (isset($_SESSION['pid'])) {
    $patient_id = $_SESSION['pid'];

    if (isset($_POST['save']) || isset($_POST['update'])) {
        // Handle form submission for updating patient data
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $dob = $_POST['dob'];
        $phone_home = $_POST['phone_home'];
        $phone_cell = $_POST['phone_cell'];
        $email = $_POST['email'];

        // Check if "ok_leave" is set in the POST data
        $ok_leave = isset($_POST['ok_leave']) ? '1' : '0';

        // Check if "source" is set in the POST data (for web or mobile)
        $source = isset($_POST['source']) ? $_POST['source'] : 'Web'; // Default to 'Web' if not specified

        // Use the patient ID along with the current date for "created_by" and "updated_by"
        $created_by = $patient_id . ' (' . date('Y-m-d H:i:s') . ')';
        $updated_by = $patient_id . ' (' . date('Y-m-d H:i:s') . ')';

        if (isset($_POST['preferred_language'])) {
            $preferred_language = $_POST['preferred_language'];
        
            // Check if the selected language is 'Others' and if 'other_language' is provided
            if ($preferred_language === 'Others' && isset($_POST['other_language']) && !empty($_POST['other_language'])) {
                // Use the custom language entered in the text box
                $other_language = $_POST['other_language'];
            } else {
                // Use the selected language (English, Spanish, etc.) or set a default value
                $other_language = $preferred_language ?? 'Not Specified';
            }
        } else {
            // Set default values if no language is selected
            $preferred_language = 'Not Specified';
            $other_language = '';
        }
        
        // Additional fields for Alternate Contact
        $alter_contact = isset($_POST['alter_contact']) && !empty($_POST['alter_contact']) ? $_POST['alter_contact'] : '';
        $relationship = isset($_POST['relationship']) && !empty($_POST['relationship']) ? $_POST['relationship'] : '';

        $p_phone = isset($_POST['p_phone']) && !empty($_POST['p_phone']) ? $_POST['p_phone'] : '';

        // Additional fields
        $household_size = isset($_POST['household_size']) && !empty($_POST['household_size']) ? $_POST['household_size'] : '';

        $annual_household_income = isset($_POST['annual_household_income']) ? $_POST['annual_household_income'] : '';

        // Check if the "Consent for Patient Resources and Information (OPTIONAL)" checkbox is checked
        $cpri = isset($_POST['cpri']) && $_POST['cpri'] === '1' ? '1' : '0';

        // Check if the "Telephone Consumer Protection Act (TCPA) Consent (OPTIONAL)" checkbox is checked
        $tcpac = isset($_POST['tcpac']) && $_POST['tcpac'] === '1' ? '1' : '0';

        // Handle the "Signature of Patient/Legally Authorized Representative" field
        $sign = isset($_POST['sign']) ? $_POST['sign'] : '';

        // Handle the "Date Signed" field
        $date_signed = isset($_POST['date_signed']) ? $_POST['date_signed'] : '';

        // Handle the "Print First Name" field
        $p_fname = isset($_POST['p_fname']) && !empty($_POST['p_fname']) ? $_POST['p_fname'] : '';

        // Handle the "Print Last Name" field
        $p_lname = isset($_POST['p_lname']) && !empty($_POST['p_lname']) ? $_POST['p_lname'] : '';

        // Handle the "Relationship to Patient" field
        $r_patient = isset($_POST['r_patient']) && !empty($_POST['r_patient']) ? $_POST['r_patient'] : '';

        // Update patient data in the database
        $update_query = "UPDATE patient_data SET fname = ?, lname = ?, DOB = ?, phone_home = ?, phone_cell = ?, email = ? WHERE pid = ?";
        $update_params = array($fname, $lname, $dob, $phone_home, $phone_cell, $email, $patient_id);

        // Execute the SQL query to update the data
        $result = sqlStatement($update_query, $update_params);

        if ($result) {
            // Data updated successfully
            $_SESSION['update_message'] = "Patient Consent Data Updated Successfully";

            // Check if the record for the patient exists in the patient_consent_form table
            $check_existing_query = "SELECT * FROM patient_consent_form WHERE patient_id = ?";
            $check_existing_params = array($patient_id);
            $existing_result = sqlQuery($check_existing_query, $check_existing_params);

            if ($existing_result !== false && count($existing_result) > 0) {
                // If the record exists, update it, including the "source," "updated_by," and "updated" fields
                $update_consent_query = "
                UPDATE patient_consent_form
                SET fname = ?, lname = ?, DOB = ?, phone_home = ?, phone_cell = ?, email = ?, ok_leave = ?, preferred_language = ?, other_language = ?,
                alter_contact = ?, relationship = ?, p_phone = ?, household_size = ?, annual_household_income = ?, cpri = ?, tcpac = ?, sign = ?, date_signed = ?,
                p_fname = ?, p_lname = ?, r_patient = ?, source = ?, created_by = ?, updated_by = ? WHERE patient_id = ?
            ";
                $update_consent_params = array(
                    $fname, $lname, $dob, $phone_home, $phone_cell, $email, $ok_leave, $preferred_language, $other_language,
                    $alter_contact, $relationship, $p_phone, $household_size, $annual_household_income, $cpri, $tcpac, $sign, $date_signed,
                    $p_fname, $p_lname, $r_patient, $source, $created_by, $updated_by, $patient_id
                );
                $result_consent = sqlStatement($update_consent_query, $update_consent_params);
            } else {
                // If the record doesn't exist, insert a new one, including the "source," "created_by," and "updated_by" fields
                $insert_consent_query = "
                INSERT INTO patient_consent_form (patient_id, pid, fname, lname, DOB, phone_home, phone_cell, email, ok_leave, preferred_language, other_language, alter_contact, relationship, p_phone, household_size, annual_household_income, cpri, tcpac, sign, date_signed, p_fname, p_lname, r_patient, source, created_by, updated_by, created, updated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
                $insert_consent_params = array(
                    $patient_id, $patient_id, $fname, $lname, $dob, $phone_home, $phone_cell, $email, $ok_leave, $preferred_language, $other_language,
                    $alter_contact, $relationship, $p_phone, $household_size, $annual_household_income, $cpri, $tcpac, $sign, $date_signed,
                    $p_fname, $p_lname, $r_patient, $source, $created_by, $updated_by
                );
                $result_consent = sqlStatement($insert_consent_query, $insert_consent_params);
            }

            if ($result_consent) {
                // Consent form data updated or inserted successfully
                $update_message = "Patient Consent Data Updated Successfully for $fname $lname (ID: $patient_id)";
                $_SESSION['update_message'] = $update_message;
            } else {
                // Error handling for the consent form update or insert operation
                $update_message = "Error updating/inserting patient consent data for $fname $lname (ID: $patient_id)";
                $_SESSION['update_message'] = $update_message;
            }
            
            // ...

            if (isset($_SESSION['update_message'])) {
                // Display the update message with CSS styling
                echo '<div style="height: 32px; text-align: center; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                <h4 style="font-size: 20px; text-align: center; color: #ffffff; font-weight: 500; margin: 0px;">' . $_SESSION['update_message'] . '</h4>
                </div>';
            
                // Redirect to the demographics page after 1 second
                echo '<meta http-equiv="refresh" content="1;url=../../../interface/patient_file/summary/demographics.php">';
                unset($_SESSION['update_message']); // Clear the message from the session
                exit; // Add this line to exit the script
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information</title>
    <style>
    /* Add CSS styles here */
    </style>
</head>

<body>
    <!--  HTML content goes here -->
</body>

</html>
