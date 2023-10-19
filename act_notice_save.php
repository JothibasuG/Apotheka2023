<?php
// Author: G.Jothibasu

// Set error reporting and display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection file (globals.php)
require_once("../../globals.php");

// Start the session
session_start();

// Check if $_SESSION['pid'] is set
if (isset($_SESSION['pid'])) {
    $patient_id = $_SESSION['pid'];

    // Initialize the variables with default or null values
    $fname = ''; // You can set any default value you prefer for fname
    $sign = null; // Default value for act_sign (null if not set)
    $date_signed = null; // Default value for act_date_signed (null if not set)
    $file_location = null; // Default value for act_file_location (null if not set)
    $file_name = null; // Default value for act_file_name (null if not set)

    // Check if the 'save' or 'update' action is triggered
    if (isset($_POST['save']) || isset($_POST['update'])) {
        // Handle form submission for updating patient data
        if (isset($_POST['fname'])) {
            $fname = $_POST['fname'];
        } else {
           
        }

        // Check if "source" is set in the POST data (for web or mobile)
        $source = isset($_POST['act_source']) ? $_POST['act_source'] : 'Web'; // Default to 'Web' if not specified

        // Use the patient ID along with the current date for "created_by" and "updated_by"
        $created_by = $patient_id . ' (' . date('Y-m-d H:i:s') . ')';
        $updated_by = $patient_id . ' (' . date('Y-m-d H:i:s') . ')';

        // Handle the "Signature of Patient/Legally Authorized Representative" field
        if (isset($_POST['act_sign'])) {
            $sign = $_POST['act_sign'];
        } else {
            // Handle the case where 'act_sign' is not set
            // You can set a default value or display an error message
        }

        // Handle the "Date Signed" field
        if (isset($_POST['act_date_signed'])) {
            $date_signed = $_POST['act_date_signed'];
        } else {
            // Handle the case where 'act_date_signed' is not set
            // You can set a default value or display an error message
        }

        // Handle the "File Location" field
        if (isset($_POST['act_file_location'])) {
            $file_location = $_POST['act_file_location'];
        }

        // Handle the "File Name" field
        if (isset($_POST['act_file_name'])) {
            $file_name = $_POST['act_file_name'];
        }

        // Update patient data in the database
        $update_query = "UPDATE patient_data SET fname = ? WHERE pid = ?";
        $update_params = array($fname, $patient_id);

        // Execute the SQL query to update the data
        $result = sqlStatement($update_query, $update_params);

        if ($result) {
            // Data updated successfully
            $_SESSION['update_message'] = "Patient Act Notice Data Updated Successfully";

            // Check if the record for the patient exists in the patient_act_notice table
            $check_existing_query = "SELECT * FROM patient_act_notice WHERE patient_id = ?";
            $check_existing_params = array($patient_id);
            $existing_result = sqlQuery($check_existing_query, $check_existing_params);

            if ($existing_result !== false && count($existing_result) > 0) {
                // If the record exists, update it, including the new fields
                $update_consent_query = "
                    UPDATE patient_act_notice
                    SET fname = ?, act_sign = ?, act_date_signed = ?, act_source = ?, act_file_location = ?, act_file_name = ?, updated_by = ? WHERE patient_id = ?
                ";
                $update_consent_params = array(
                    $fname, $sign, $date_signed, $source, $file_location, $file_name, $updated_by, $patient_id
                );
                $result_consent = sqlStatement($update_consent_query, $update_consent_params);
            } else {
                // If the record doesn't exist, insert a new one, including the new fields
                $insert_consent_query = "
                    INSERT INTO patient_act_notice (patient_id, fname, act_sign, act_date_signed, act_source, act_file_location, act_file_name, created_by, updated_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $insert_consent_params = array(
                    $patient_id, $fname, $sign, $date_signed, $source, $file_location, $file_name, $created_by, $updated_by
                );
                $result_consent = sqlStatement($insert_consent_query, $insert_consent_params);
            }

            if ($result_consent) {
                // patient act notice updated or inserted successfully
                $update_message = "Patient Act Notice Data Updated Successfully for $fname  (ID: $patient_id)";
                $_SESSION['update_message'] = $update_message;
            } else {
                // Error handling for the consent form update or insert operation
                $update_message = "Error updating/inserting patient act notice data for $fname  (ID: $patient_id)";
                $_SESSION['update_message'] = $update_message;
            }

            // Check if the session update_message is set
            if (isset($_SESSION['update_message'])) {
                // Display the update message with CSS styling
                echo '<div style="height: 50px; text-align: center; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                <h4 style="font-size: 20px; text-align: center; color: #ffffff; font-weight: 500; margin: 0px;">' . $_SESSION['update_message'] . '</h4>
                </div>';

                // Redirect to the demographics page after 1 second
                echo '<meta http-equiv="refresh" content="1;url=../../../interface/patient_file/summary/demographics.php">';
                unset($_SESSION['update_message']); // Clear the message from the session
                exit; // Exit the script
            }
        }
    }
}
?>
