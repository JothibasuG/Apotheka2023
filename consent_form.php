<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("../../globals.php");

require_once __DIR__ . '../../../../vendor/autoload.php';

use OpenEMR\Core\Header;

if (isset($_GET['set_pid'])) {
    include_once("$srcdir/pid.inc");
    setpid($_GET['set_pid']);
}

$patient_id = $_SESSION['pid'];

if (isset($_SESSION['pid'])) {
    $patient_id = $_SESSION['pid'];

    // Retrieve patient data from patient_data table
    $patient_data = sqlStatement("SELECT fname, lname, DOB, phone_home, phone_cell, email FROM patient_data WHERE pid = '$patient_id'");

    // Check if patient data is found in patient_data table
    if ($patient_data && sqlNumRows($patient_data) > 0) {
        $patient_row = sqlFetchArray($patient_data);

        // Populate the form fields with patient_data
        $fname = $patient_row['fname'];
        $lname = $patient_row['lname'];
        $dob = $patient_row['DOB'];
        $phone_home = $patient_row['phone_home'];
        $phone_cell = $patient_row['phone_cell'];
        $email = $patient_row['email'];
    } else {
        // Handle the case where patient data is not found in patient_data table
        echo "Patient data not found!";
    }

    // Retrieve consent form data from patient_consent_form table
    $consent_form_data = sqlStatement("SELECT * FROM patient_consent_form WHERE patient_id = '$patient_id'");

    // Check if consent form data is found in patient_consent_form table
    if ($consent_form_data && sqlNumRows($consent_form_data) > 0) {
        // Consent form data is found, populate the form fields from patient_consent_form
        $consent_form_row = sqlFetchArray($consent_form_data);

        $ok_leave = $consent_form_row['ok_leave'];
        $preferred_language = $consent_form_row['preferred_language'];  
        $other_language = $consent_form_row['other_language'];     
        $alter_contact = $consent_form_row['alter_contact'];
        $relationship = $consent_form_row['relationship'];
        $p_phone = $consent_form_row['p_phone'];
        $household_size = $consent_form_row['household_size'];
        $annual_household_income = $consent_form_row['annual_household_income'];
        $cpri = $consent_form_row['cpri'];
        $tcpac = $consent_form_row['tcpac'];
        $sign = $consent_form_row['sign'];
        $date_signed = $consent_form_row['date_signed'];
        $p_fname = $consent_form_row['p_fname'];
        $p_lname = $consent_form_row['p_lname'];
        $r_patient = $consent_form_row['r_patient'];
    } else {
        // Consent form data not found, populate missing fields from patient_data and store in patient_consent_form
        // Populate missing fields from patient_data
        $fname = isset($fname) ? $fname : '';
        $lname = isset($lname) ? $lname : '';
        $email = isset($email) ? $email : '';
        $dob = isset($dob) ? $dob : '';
        $phone_home = isset($phone_home) ? $phone_home : '';
        $phone_cell = isset($phone_cell) ? $phone_cell : '';

        // Now, you can insert the missing fields into patient_consent_form
        $insert_query = "
            INSERT INTO patient_consent_form (patient_id, pid, fname, lname, DOB, phone_home, phone_cell, email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $insert_params = array(
            $patient_id, $patient_id, $fname, $lname, $dob, $phone_home, $phone_cell, $email
        );

        // Execute the SQL query to insert the missing data into patient_consent_form
        $insert_result = sqlStatement($insert_query, $insert_params);

        if ($insert_result) {
            // Data inserted successfully, you can choose to display a success message here
        } else {
            // Error handling for the insert operation, you can choose to display an error message here
        }

        // Set the form fields to the inserted values
        $ok_leave ='';
        $preferred_language = ''; // Set your default values here for missing fields
        $other_language ='';
        $alter_contact = '';
        $relationship = '';
        $p_phone = '';
        $household_size = '';
        $annual_household_income = '';
        $cpri = '';
        $tcpac = '';
        $sign = '';
        $date_signed = '';
        $p_fname = '';
        $p_lname = '';
        $r_patient = '';
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title> Patient Consent Form (<?php echo $patient_id; ?>)</title>
    <link rel="stylesheet"
        href="<?php echo $GLOBALS['assets_static_relative']; ?>/other/bootstrap/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/other/css/theme_style.css"
        type="text/css">
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/other/css/custom.css"
        type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
    body {
        font-family: 'Roboto', sans-serif;
        padding: 0;
        margin: 0;
    }

    h1 {
        margin: 0;
    }

    p {
        margin: 0;
    }

    .x-list li {
        position: relative;
    }

    .x-list li::before {
        content: "\2022";
        color: #b2bb1e;
        font-size: 30px;
        display: inline-block;
        position: absolute;
        left: -19px;
        top: -8px;
    }

    .flatpickr-calendar {
        font-size: 14px;
    }

    /* Add a border around the calendar */
    .flatpickr-calendar.open {
        border: 1px solid #ccc;
        box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.2);
    }
    </style>

</head>


<body class="body_top" onload="javascript:document.patient_consent_form.reason.focus();">
<div class="table-scrollable">
    <table class="table_header table" style="margin-top: 10px;">
        <?php if(isset($_GET['is_new']) && $GLOBALS['block_chain_status'] == "On") { ?>
        <tr>
            <td colspan="5">
                <!-- <div class="BlockChainResponseDiv demographics">
                            <div class='transfer_img'>
                                <span><img src='../../../images/apoketha-min-logo.png'></span>
                                <span class='file_trns_gif'><img src='../../../images/file-transfer.gif'></span>
                                <span><img src='../../../images/saved_chain.gif'></span>
                                <span class='file_trns_content'>Saved to Blockchain</span>
                            </div>
                        </div> -->
                <div class="BlockChainMessage">
                    <p class="text data_saved_into_blockchain"> Saved to Blockchain <i
                            class="fa fa-check-circle fa-lg fa-fw"></i> </p>
                </div>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td>
                <span class='title btn dark btn-outline m-b-10'>
                    <?php echo htmlspecialchars(getPatientName($pid), ENT_NOQUOTES); ?>
                </span>
            </td>
            <?php  // Allow PT delete
                if ($GLOBALS['erx_enable']) :
                    ?>
            <td style="padding-left:1em;" class="erx">
                <a class="css_button" href="../../eRx.php?page=medentry" onclick="top.restoreSession()">
                    <span
                        style="font-size: 12px;"><?php echo htmlspecialchars(xl('NewCrop MedEntry'), ENT_NOQUOTES); ?></span>
                </a>
            </td>
            <td style="padding-left:1em;">
                <a class="css_button iframe1" href="../../soap_functions/soap_accountStatusDetails.php"
                    onclick="top.restoreSession()">
                    <span
                        style="font-size: 12px;"><?php echo htmlspecialchars(xl('NewCrop Account Status'), ENT_NOQUOTES); ?></span>
                </a>
            </td>
            <td id='accountstatus'></td>
            <?php
                endif; // eRX Enabled
                //Patient Portal
                $portalUserSetting = true; //flag to see if patient has authorized access to portal
                if (($GLOBALS['portal_onsite_enable'] && $GLOBALS['portal_onsite_address']) ||
                        ($GLOBALS['portal_onsite_two_enable'] && $GLOBALS['portal_onsite_two_address'])) :
                    $portalStatus = sqlQuery("SELECT allow_patient_portal FROM patient_data WHERE pid=?", array($pid));
                    if ($portalStatus['allow_patient_portal'] == 'YES') :
                        $portalLogin = sqlQuery("SELECT pid FROM `patient_access_onsite` WHERE `pid`=?", array($pid));
                        
                        ?>
                        
            <td style='padding-left:1em;'>
                <a class='css_button small_modal btn btn-round btn-success'
                    href='create_portallogin.php?portalsite=on&patient=<?php echo htmlspecialchars($pid, ENT_QUOTES); ?>'
                    onclick='top.restoreSession()'>
                    <?php $display = (empty($portalLogin)) ? xlt('Create Onsite Portal Credentials') : xlt('Reset Onsite Portal Credentials'); ?>
                    <span><?php echo $display; ?></span>
                </a>
            </td>
            <?php
                    else :
                        $portalUserSetting = false;
                    endif; // allow patient portal
                endif; // Onsite Patient Portal
                if ($GLOBALS['portal_offsite_enable'] && $GLOBALS['portal_offsite_address']) :
                    $portalStatus = sqlQuery("SELECT allow_patient_portal FROM patient_data WHERE pid=?", array($pid));
                    if ($portalStatus['allow_patient_portal'] == 'YES') :
                        $portalLogin = sqlQuery("SELECT pid FROM `patient_access_offsite` WHERE `pid`=?", array($pid));
                        ?>
            <td style='padding-left:1em;'>
                <a class='css_button small_modal btn btn-round btn-success'
                    href='create_portallogin.php?portalsite=off&patient=<?php echo htmlspecialchars($pid, ENT_QUOTES); ?>'
                    onclick='top.restoreSession()'>
                    <span>
                        <?php $text = (empty($portalLogin)) ? xlt('Create Offsite Portal Credentials') : xlt('Reset Offsite Portal Credentials'); ?>
                        <?php echo $text; ?>
                    </span>
                </a>
            </td>
            <?php
                    else :
                        $portalUserSetting = false;
                    endif; // allow_patient_portal
                endif; // portal_offsite_enable
              
                //Patient Portal
                // If patient is deceased, then show this (along with the number of days patient has been deceased for)
                $days_deceased = is_patient_deceased($pid);
                if ($days_deceased != null) :
                    ?>
            <td class="deceased" style="padding-left:1em;font-weight:bold;color:red">
                <?php
                        if ($days_deceased == 0) {
                            echo xlt("DECEASED (Today)");
                        } else if ($days_deceased == 1) {
                            echo xlt("DECEASED (1 day ago)");
                        } else {
                            echo xlt("DECEASED") . " (" . text($days_deceased) . " " . xlt("days ago") . ")";
                        }
                        ?>
            </td>
            <?php endif; ?>
            <td>
                <a class="btn btn-info btn-success"
                    href="<?php echo $web_root ?>/interface/patient_file/summary/demographics.php?autoloaded=1&calenc=&cpid=<?php echo $patient_id; ?>">Demographics</a>
            </td>
            <td>
                <a class="btn btn-info btn-danger"
                    href="<?php echo $web_root ?>/interface/forms/newpatient/new.php?autoloaded=1&calenc=&cpid=<?php echo $patient_id; ?>">Create
                    Current Visit</a>
            </td>
            <td>
                <a class="btn btn-info btn-warning"
                    href="<?php echo $web_root ?>/interface/patient_file/front_payment.php">Payment</a>
            </td>
            <td>
                <a class="btn btn-primary"
                    href="<?php echo $web_root ?>/interface/dashboard/find_pharmacy/find_pharmacy.php"
                    title="Find Nearest Pharmacy">Pharmacy</a>
            </td>
            <td>

                <a href="patient_consent_form_pdf.php?generate_pdf=1&patient_id=<?php echo $patient_id; ?>"
                    class="btn btn-success btn-save">View / Download PDF</a>

            </td>
        </tr>
    </table>
</div>
    <div class="container-fluid">
        <div class="card card-apotheka-color">
            <!-- <form id="patient-consent-form" method="post" action="/interface/patient_file/consent_form/save_patient.php" name="patient_consent_form"> -->
            <!-- <div style="margin: 20px 0px;"> -->

            <form method="POST" action="save_patient.php">
                <div>
                    <div style="display:flex; justify-content: space-between; margin: 0px 30px; padding-bottom: 10px;">
                        <div>
                            <h3
                                style="font-family: 'Roboto', sans-serif; font-size: 25px; letter-spacing: 0px; line-height: 27px; color: #005596; font-weight: bold; margin-bottom: 10px;">
                                PATIENT CONSENT FORM</h3>
                            <img src='images/logo.png' style="width: 200px;" />
                        </div>
                        <div style="text-align: right;">
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Genentech-Access.com</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Phone: <span style="color: #000;">(866) 422-2377 </span>&nbsp;&nbsp;
                                Fax: <span style="color: #000;">(866) 480-7762</span></p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500; margin-bottom: 5px;">
                                6 a.m.–5 p.m. (PT) M-F</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                M-US-00002802(v2.0)</p>
                        </div>
                    </div>

                    <div
                        style="height: 32px; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                        <h4 style="font-size: 20px; color: #ffffff;font-weight: 500; margin: 0px;">New Title Here</h4>
                    </div>

                    <div style="display: flex; margin-top: 15px;">
                        <div style="width: 50%; display:none;">
                            <p style="background-color: #e2e5b7; display: inline; padding: 5px 0px;">
                                <span
                                    style="font-size: 16px; letter-spacing: 0px; line-height: 26px; color: #005596; font-family: 'Roboto', sans-serif; padding-left: 30px; font-weight: 500; padding-right: 10px;">
                                    By completing this form, you can:
                                </span>
                            </p>

                            <div style="display: flex; padding-left: 30px; align-items: center; margin: 10px 0px;">
                                <img src="images/icon1.png" style="width: 50px; height: 50px;;" />
                                <p
                                    style="padding-left: 30px; font-size: 14px; letter-spacing: 0px; color: #000000; font-family: 'Roboto', sans-serif;">
                                    Learn about your health insurance coverage and other options to get your Genentech
                                    medicine</p>
                            </div>
                            <div style="display: flex; padding-left: 30px; align-items: center; margin: 10px 0px;">
                                <img src="images/icon2.png" style="width: 50px; height: 50px;;" />
                                <p
                                    style="padding-left: 20px; font-size: 14px; letter-spacing: 0px; color: #000000; font-family: 'Roboto', sans-serif;">
                                    Sign up to receive <b>optional</b> disease education and other material</p>
                            </div>
                        </div>

                        <div style="width: 50%; margin-right: 30px; display:none;">
                            <p style="font-size: 16px; letter-spacing: 0px; line-height: 26px; color: #000; font-family: 'Roboto', sans-serif; padding-left: 20px; font-weight: 500;
           padding-right: 10px; padding-bottom: 5px;">
                                Please follow these 3 steps to get started:</p>

                            <div style="display: flex; padding-left: 20px; align-items: center; margin-bottom: 5px;">
                                <div
                                    style="font-size: 22px; letter-spacing: 0px; line-height: 35px; color: #b2bb1e; font-family: 'Roboto', sans-serif; font-weight: 600; margin-right: 5px;">
                                    1. </div>
                                <div>
                                    <p
                                        style="font-size: 14px; letter-spacing: 0px; color: #000000; font-family: 'Roboto', sans-serif;">
                                        Read “Authorization to Use and Disclose Personal Information” on page 2.
                                    </p>
                                </div>
                            </div>

                            <div style="display: flex; padding-left: 20px; align-items: center; margin-bottom: 5px;">
                                <div
                                    style="font-size: 22px; letter-spacing: 0px; line-height: 35px; color: #b2bb1e; font-family: 'Roboto', sans-serif; font-weight: 600; margin-right: 5px;">
                                    2. </div>
                                <div>
                                    <p
                                        style="font-size: 14px; letter-spacing: 0px; color: #000000; font-family: 'Roboto', sans-serif;">
                                        Sign and date page 3. Please note you must sign the form to get support for your
                                        treatment. </p>
                                </div>
                            </div>

                            <div style="display: flex; padding-left: 20px; align-items: center; margin-bottom: 5px;">
                                <div
                                    style="font-size: 22px; letter-spacing: 0px; line-height: 35px; color: #b2bb1e; font-family: 'Roboto', sans-serif; font-weight: 600; margin-right: 5px;">
                                    3. </div>
                                <div>
                                    <p
                                        style="font-size: 14px; letter-spacing: 0px; color: #000000; font-family: 'Roboto', sans-serif;">
                                        Send in your completed form using one of the options below
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background-color: #005596; height: 2px; margin: 0px 30px; margin-bottom: 5px;"></div>

                    <div style="margin: 0px 30px;">
                        <p style="font-size: 16px; color: #292829;">Genentech can start supporting you when <b>page
                                3</b> of this form is
                            submitted by you or your
                            doctor’s office in
                            one of the following ways:</p>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; align-self:center; margin: 0px 30px;">
                        <div style="display: flex; align-items: center; align-self:center; width: 35%;">
                            <img src="images/QR.jpg" style="height: 80px; width: 80px;" />
                            <p style="font-size: 14px; margin-left: 10px;
      letter-spacing: 0px;
      color: #000000;">Complete online by <br />
                                scanning this QR code <br />
                                or visiting <br />
                                Genentech-Access.com/<br />
                                PatientConsent</p>
                        </div>

                        <div style="border-left: 1px solid #005596; height: 100px; display: flex; align-items: center;">
                            <p
                                style="width: 25px; height: 25px; border-radius: 18px;  background-color: #ffffff; border: 1px solid #005596; margin-left: -14px;">
                                <span
                                    style="font-size: 10px; position: relative; left: 6px;   right: 0;  top: -1px; line-height: 26px; color: #005596; font-weight: 500;">OR</span>
                            </p>
                        </div>

                        <div style="display: flex; align-items: center; align-self:center; width: 28%;">
                            <img src="images/icon4.png" style="height: 60px; width: 60px;" />
                            <p style="font-size: 14px; margin-left: 10px; letter-spacing: 0px; color: #000000;">Print,
                                complete, take <br />
                                a
                                photo and text it to <br />(650) 877-1111</p>
                        </div>

                        <div style="border-left: 1px solid #005596; height: 100px; display: flex; align-items: center;">
                            <p
                                style="width: 25px; height: 25px; border-radius: 18px;  background-color: #ffffff; border: 1px solid #005596; margin-left: -14px;">
                                <span
                                    style="font-size: 10px; position: relative; left: 6px;   right: 0;  top: -1px; line-height: 26px; color: #005596; font-weight: 500;">OR</span>
                            </p>
                        </div>


                        <div style="display: flex; align-items: center; align-self:center; width: 28%;">
                            <img src="images/icon5.png" style="height: 60px; width: 60px;" />
                            <p style="font-size: 14px; margin-left: 10px; letter-spacing: 0px; color: #000000;">Print,
                                complete <br /> and
                                fax it
                                to <br /> (866) 480-7762</p>
                        </div>

                    </div>

                    <div style="margin: 0px 30px; padding-top: 5px;">
                        <p style="font-size: 16px;  color: #292829;">A representative from Genentech Access Solutions or
                            your doctor’s
                            office will call you to tell
                            you about your coverage,
                            costs and support for your treatment.</p>
                    </div>

                    <div style="padding: 0px 0px; margin: 10px 0px 15px 0px; background-color: #e2e5b7;">
                        <p style="padding: 5px 0px;">
                            <span
                                style="font-size: 14px; letter-spacing: 0px; color: #005596; font-family: 'Roboto', sans-serif; padding-left: 30px; font-weight: 500; padding-right: 10px;">
                                If you have any questions, talk to your health care provider or call Genentech Access
                                Solutions at (866)
                                422-2377.
                            </span>
                        </p>
                    </div>

                    <div
                        style="height: 32px; background-color: #005596; display: flex; align-items: center; padding-left: 30px; display:none;">
                        <h4 style="font-size: 20px; color: #ffffff;font-weight: 500; margin: 0px;">Helpful Terminology
                        </h4>
                    </div>

                    <div style="display: flex; margin: 0px 30px;">
                        <div style="width: 50%; margin: 10px 0px; padding-right: 20px; display:none;">
                            <p style="font-size: 14px; margin-bottom: 10px; color: #000000;">
                                <span style=" color: #005596;"><b>Genentech:</b></span>
                                The maker of the medicine your doctor
                                wants to prescribe for you. Genentech is committed to
                                helping patients get the medicine their doctor prescribed.
                                When used on this form, the term “Genentech” refers
                                to Genentech, Genentech Patient Foundation, and their
                                respective partners, affiliates, subcontractors and agents.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px;  color: #000000;">
                                <span style=" color: #005596;"><b>Genentech Access Solutions:</b></span>
                                A team at Genentech that
                                works with your doctor and health insurance plan to help
                                you get your medicine.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px; color: #000000;">
                                <span style=" color: #005596;"><b>Genentech Patient Foundation:</b></span>
                                A program that gives free
                                Genentech medicine to eligible people who don't have
                                insurance coverage or who have financial concerns.
                            </p>

                            <p style="font-size: 14px; color: #000000;">
                                <span style=" color: #005596;"><b>Annual household income:</b></span>
                                How much you and the members
                                of your household currently make each year, minus specific
                                deductions. This is also frequently referred to as your adjusted
                                gross income or AGI. This information is needed to determine
                                Genentech Patient Foundation eligibility.
                            </p>


                        </div>
                        <div style="width: 50%; margin: 10px 0px; display:none;">
                            <p style="font-size: 14px; margin-bottom: 10px;  color: #000000;">
                                <span style=" color: #005596;"><b>Household size:</b></span>
                                Number of people living in your household, including you.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px; color: #000000;">
                                <span style=" color: #005596;"><b>Deductible:</b></span>
                                The amount you pay for health care services
                                or medicines out of pocket before your health insurance
                                plan begins to pay.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px; color: #000000;">
                                <span style=" color: #005596;"><b>Out-of-pocket costs:</b></span>
                                The amount not paid by the
                                insurance plan that you must pay for your treatment.
                                This includes deductibles, co-pays and co-insurance.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px;  color: #000000;">
                                <span style=" color: #005596;"><b>Co-pay assistance:</b></span>
                                Programs available to help eligible
                                patients pay for their medicines.
                            </p>

                            <p style="font-size: 14px; margin-bottom: 10px;  color: #000000;">
                                <span style=" color: #005596;"><b>Alternate contact:</b></span>
                                Someone you choose to be your contact
                                person if Genentech Access Solutions cannot reach you.
                            </p>

                            <p style="font-size: 14px; color: #000000;">
                                <span style=" color: #005596;"><b>Legally authorized representative:</b></span>
                                An individual or judicial
                                or other body authorized under applicable law to consent on
                                behalf of a patient (e.g., parent or legal guardian of a minor).
                            </p>
                        </div>
                    </div>


                    <div
                        style="height: 32px; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                        <h4 style="font-size: 20px; color: #ffffff;font-weight: 500; margin: 0px;">Terms and Conditions
                            of the Genentech Patient Foundation</h4>
                    </div>

                    <div style="margin: 0px 30px;;">
                        <ul style="padding-left: 25px; margin-bottom: 0px; margin-top: 10px;" class="x-list">
                            <li style="font-size: 14px; margin-bottom: 10px;color: #000000; display: block;">
                                If I receive free medicine from the Genentech Patient Foundation, I will not sell or
                                give out the medicine
                                because it is
                                illegal to do so. I am responsible to ensure that the medicine is sent to a secure
                                address when shipped to me,
                                and I
                                must control any medicine that I receive
                            </li>
                            <li style="font-size: 14px; margin-bottom: 10px;color: #000000; display: block;">
                                I understand that, for purposes of an audit, the Genentech Patient Foundation may ask me
                                for a copy of my IRS
                                1040 form or other proof of income
                            </li>
                        </ul>
                    </div>

                </div>


                <!-- <div style="margin: 20px 0px;"> -->
                <div>
                    <div style="display:flex; justify-content: space-between; margin: 0px 30px; padding-bottom: 10px;">
                        <div>
                            <h3
                                style="font-family: 'Roboto', sans-serif; font-size: 25px; letter-spacing: 0px; line-height: 27px; color: #005596; font-weight: bold; margin-bottom: 10px;">
                                PATIENT CONSENT FORM</h3>
                            <img src='images/logo.png' style="width: 200px;" />
                        </div>
                        <div style="text-align: right;">
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Genentech-Access.com</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Phone: <span style="color: #000;">(866) 422-2377 </span>&nbsp;&nbsp;
                                Fax: <span style="color: #000;">(866) 480-7762</span></p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500; margin-bottom: 5px;">
                                6 a.m.–5 p.m. (PT) M-F</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                M-US-00002802(v2.0)</p>
                        </div>
                    </div>

                    <div
                        style="height: 32px; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                        <h4 style="font-size: 20px; color: #ffffff;font-weight: 500; margin: 0px;">Authorization to Use
                            and Disclose
                            Personal Information</h4>
                    </div>

                    <div style="margin: 0px 30px; padding-top: 10px;">
                        <p style="font-size: 14px;  color: #292829;">Authorization to Use and Disclose Personal
                            Information
                            I authorize my physician(s) and their staff, pharmacies, and health insurance plan (my
                            “health care providers”)
                            to share my personal information, which may include contact information, demographic
                            information, financial
                            information, and information related to my medical condition, treatments, and health
                            insurance and benefits,
                            with
                            Genentech, Genentech Patient Foundation, and their respective partners, affiliates,
                            subcontractors, and agents
                            (together, “Genentech”). I authorize Genentech to receive, use, and share my personal
                            information in order to
                            provide
                            me with access to the products, services, and programs described on this form, which may
                            include the following:
                        </p>
                    </div>
                    <div style="margin: 0px 30px;;">
                        <ul style="padding-left: 25px; margin-bottom: 0px; margin-top: 10px;" class="x-list">
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                Working with my health insurance plan to understand or verify coverage for Genentech
                                products
                            </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                Applying to the Genentech Patient Foundation
                            </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Determining
                                my eligibility for
                                and facilitating enrollment into financial assistance services if I’m eligible,
                                including
                                co-pay assistance</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Coordinating
                                my prescription
                                through a pharmacy, infusion site and/or health care provider’s office. This
                                includes contacting me to discuss my coverage, costs and eligibility for assistance and
                                other program
                                administration purposes</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Facilitating
                                my access to
                                Genentech products</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Ensuring
                                quality and safety and
                                improving our products and services</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Contacting
                                me by mail, e-mail,
                                telephone calls and text messages at the number(s) and address(es) provided for
                                non-marketing purposes</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">If I agree
                                to the
                                <b>optional</b> Consent for Patient Resources and Information, providing me with
                                optional disease
                                information and marketing material about products, services and programs offered by
                                Genentech, its partners
                                and their respective affiliates. This is not required to enroll into Genentech Access
                                Solutions services
                            </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">If I agree
                                to the
                                <b>optional</b> Telephone Consumer Protection Act (TCPA) Consent, contacting me by
                                autodialed calls
                                and/or text messages at the phone number(s) I have provided for marketing purposes. This
                                is not required to
                                enroll into Genentech Access Solutions services
                            </li>
                        </ul>
                    </div>

                    <div style="margin: 0px 30px; padding-top: 10px;">
                        <p style="font-size: 14px;  color: #292829;">I understand that Genentech may also share my
                            personal information
                            for the purposes described on this
                            authorization with my health care providers, service providers, and any individual I may
                            designate as an
                            alternate
                            contact. I understand that my pharmacy may receive payment or other remuneration for
                            disclosing my personal
                            information pursuant to this authorization. I can choose not to sign this authorization, but
                            Genentech will not
                            be able
                            to provide the services to me without it. However, my health care providers may not
                            condition either my
                            treatment or
                            my payment, enrollment, or eligibility for benefits on signing this authorization.</p>
                    </div>

                    <div style="margin: 0px 30px;;">
                        <p style="font-size: 14px;  color: #292829; padding-top: 10px;">I also understand and agree
                            that:</p>
                        <ul style="padding-left: 25px; margin-bottom: 0px; margin-top: 5px;" class="x-list">
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                This authorization is valid for 6 years from the date I sign or the date I last
                                enrolled, whichever comes
                                first,
                                unless a shorter period is required by law, or I revoke it earlier </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                My personal information released under this authorization may no longer be protected by
                                state and federal law,
                                including the Health Insurance Portability and Accountability Act (HIPAA). However,
                                Genentech will only use
                                and
                                share my personal information for the purposes stated on this authorization or as
                                otherwise permitted by law
                            </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                I have the right to revoke (cancel) this authorization at any time by submitting a
                                written notice to:
                                Genentech
                                Access Solutions, 1 DNA Way, South San Francisco, CA 94080-4990. If I revoke this
                                authorization, I will no
                                longer
                                be eligible for the services described. If a health care provider is disclosing my
                                personal information to
                                Genentech
                                on an authorized, ongoing basis, my revocation will be effective with respect to such
                                health care provider
                                when they receive notice of my revocation. My revocation will not impact uses and
                                disclosures of my personal
                                information that have already occurred in reliance on this authorization
                            </li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">Coordinating
                                my prescription
                                through a pharmacy, infusion site and/or health care provider’s office. This
                                includes contacting me to discuss my coverage, costs and eligibility for assistance and
                                other program
                                administration purposes</li>
                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                More information on my privacy rights, including specific rights I may have as a
                                resident of certain states,
                                like
                                California, can be found in Genentech’s privacy policy
                                <b>(www.gene.com/privacy-policy)</b>
                            </li>

                            <li style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                I have a right to receive a copy of this authorization </li>

                        </ul>
                    </div>



                </div>


                <div>
                    <div style="display:flex; justify-content: space-between; margin: 0px 30px; padding-bottom: 10px;">
                        <div>
                            <h3
                                style="font-family: 'Roboto', sans-serif; font-size: 25px; letter-spacing: 0px; line-height: 27px; color: #005596; font-weight: bold; margin-bottom: 10px;">
                                PATIENT CONSENT FORM</h3>
                            <img src='images/logo.png' style="width: 200px;" />
                        </div>
                        <div style="text-align: right;">
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Genentech-Access.com</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                Phone: <span style="color: #000;">(866) 422-2377 </span>&nbsp;&nbsp;
                                Fax: <span style="color: #000;">(866) 480-7762</span></p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500; margin-bottom: 5px;">
                                6 a.m.–5 p.m. (PT) M-F</p>
                            <p
                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                M-US-00002802(v2.0)</p>
                        </div>
                    </div>

                    <div
                        style="height: 32px; background-color: #005596; display: flex; align-items: center; padding-left: 30px;">
                        <h4 style="font-size: 18px; color: #ffffff;font-weight: 500; margin: 0px;">
                            Patient Information (to be completed by patient or their legally authorized representative)
                        </h4>
                    </div>

                    <div style="margin: 5px 30px;">

                        <p style="display: flex; justify-content: space-evenly; width: 100%;">
                            <span
                                style="color: #ed1d24; font-size: 14px; line-height: 29px; width: 13%;font-weight: 500;">*First
                                Name:</span>
                            <input type="text" name="fname" id="fname"
                                value="<?php echo isset($fname) ? htmlspecialchars($fname) : ''; ?>" required
                                oninput="validateFullName(this)" autocomplete="off"
                                style="width: 35%; height: 25px; font-size: 14px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; margin-right: 10px; " />

                            <span
                                style="color: #ed1d24; font-size: 14px; line-height: 29px; width: 14%; font-weight: 500;">*Last
                                name:</span>
                            <input type="text" name="lname" id="lname"
                                value="<?php echo isset($lname) ? htmlspecialchars($lname) : ''; ?>" required
                                oninput="validateFullName(this)" autocomplete="off"
                                style="width: 35%;height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; font-size: 14px;">
                        </p>


                        <p style="display: flex; justify-content: space-between; width: 100%;">
                            <span
                                style="color: #000000; font-size: 14px; line-height: 29px; width: 14%; font-weight: 400;">Home
                                phone:</span>
                            <input type="text" name="phone_home" id="phone_home" autocomplete="off"
                                value="<?php echo isset($phone_home) ? htmlspecialchars($phone_home) : ''; ?>"
                                style="width: 35.5%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; font-size: 14px; margin-right: 10px; ">
                            <span
                                style="color: #000000; font-size: 14px; line-height: 29px; width: 15%; font-weight: 400;">Cell
                                phone:</span>
                            <input type="text" name="phone_cell" id="phone_cell" autocomplete="off"
                                value="<?php echo isset($phone_cell) ? htmlspecialchars($phone_cell) : ''; ?>"
                                style="width: 35%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; margin-right: 10px; ">
                            <span style="color: #000000; font-size: 14px; line-height: 29px; font-weight: 400;">
                        </p>

                        <p style="display: flex;justify-content: space-between; width: 100%;">
                            <span
                                style="color: #000000; font-size: 14px; line-height: 29px; width: 43%;font-weight: 400; position: relative;">
                                <input type="checkbox" name="ok_leave" id="ok_leave" value="1"
                                    <?php echo isset($ok_leave) && $ok_leave === '1' ? 'checked' : ''; ?>>

                                <label for="ok_leave"
                                    style="position: absolute; font-size: 14px; left:15px; height: 15px; ">OK to leave a
                                    detailed message?</label>
                            </span>
                            <span style="color: #000000; font-size: 14px; line-height: 29px; ">Date of birth<span
                                    style="font-size: 14px;"> (YYYY/MM/DD):</span></span>
                            <input type="text" name="dob" id="dob"
                                value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>"
                                style="width: 23%;height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; font-size: 14px; border-left: 0px;">
                        </p>

                        <p style="display: flex;justify-content: space-between; width: 100%;">
                            <span style="color: #000000; font-size: 14px; line-height: 29px; ">Email:</span>
                            <input type="email" name="email" id="email" autocomplete="off"
                                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                style="width: 20%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px;"
                                pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                title="Enter a valid email address">
                            <label for="preferred_language"
                                style="color: #000000; font-size: 14px; line-height: 29px; ">Preferred Language:</label>
                            </span>
                            <span style="display: flex; align-items: center; font-size: 14px; position: relative;">
                                <input
                                    style="height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px; font-size: 14px;"
                                    type="radio" name="preferred_language" value="English" id="english_option"
                                    <?php echo isset($preferred_language) && $preferred_language === 'English' ? 'checked' : ''; ?>>
                                English
                            </span>

                            <span style="display: flex; align-items: center; font-size: 14px; position: relative;">
                                <input
                                    style="height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px; font-size: 14px; "
                                    type="radio" name="preferred_language" value="Spanish" id="spanish_option"
                                    <?php echo isset($preferred_language) && $preferred_language === 'Spanish' ? 'checked' : ''; ?>>
                                Spanish
                            </span>

                            <span style="display: flex; align-items: center; font-size: 14px; position: relative;">
                                <input
                                    style="height: 20px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px; font-size: 14px; "
                                    type="radio" name="preferred_language" value="Others" id="others_option"
                                    <?php echo isset($preferred_language) && $preferred_language === 'Others' ? 'checked' : ''; ?>>
                                Others
                            </span>

                            <!-- Text box for "Other Language" -->
                            <span
                                style="display: flex; align-items: center; position: relative; line-height: 20px; top:5px;">
                                <label for="other_language" id="other_language_label"
                                    style="display: none; font-size: 14px;">Language:</label>
                                <input type="text" name="other_language" id="other_language" autocomplete="off"
                                    style="display: none; bottom:5px; width: 75%; font-size: 14px; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px; margin-right: 10px;"
                                    value="<?php echo isset($other_language) ? htmlspecialchars($other_language) : ''; ?>">
                            </span>



                        <p style="display: flex; width: 100%;">


                            <label for="alter_contact" style="color: #005596; padding-right: 5px;"><span
                                    style="color: #000000; font-size: 14px; line-height: 29px; display: flex;"><span
                                        style="color: #005596; padding-right: 5px;">Alternate Contact
                                        (optional)</span>Full Name:</span></label>

                            <input type="text"
                                style="width: 57%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; font-size: 14px; border-top:0px; border-left: 0px; top:5px;"
                                name="alter_contact" id="alter_contact" autocomplete="off"
                                oninput="validateFullName(this)"
                                value="<?php echo isset($alter_contact) ? htmlspecialchars($alter_contact) : ''; ?>">

                        </p>

                        <p style="display: flex; justify-content: space-between; width: 100%;">
                            <label for="relationship"><span
                                    style="color: #000000; font-size: 14px; line-height: 25px; font-weight: 400; margin:right:3px">Relationship:
                                </span></label>

                            <input
                                style="width: 55%; height: 25px; top:5px; background-color: #dee5ff; border-bottom: 1px solid #000000;  font-size: 14px;border-top:0px; border-left: 0px; "
                                type="text" name="relationship" id="relationship" autocomplete="off"
                                oninput="validateFullName(this)"
                                value="<?php echo isset($relationship) ? htmlspecialchars($relationship) : ''; ?>">


                            <span
                                style="color: #000000; font-size: 14px; line-height: 29px; width: 5%; font-weight: 400; margin-left:5px;">Phone:</span>
                            <input type="text" name="p_phone" id="p_phone" autocomplete="off"
                                value="<?php echo isset($p_phone) ? htmlspecialchars($p_phone) : ''; ?>"
                                style="width: 25%; height: 25px; font-size: 14px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; margin-right: 10px; ">
                        </p>
                    </div>

                    <div style="margin: 0px 30px; display: flex;  border: 2px solid #005596; margin-bottom: 5px;">
                        <div style="background-color: #005596; width: 8%; position: relative;">
                            <div
                                style="position: absolute; left: 35%;  top: 40%; color: #ffffff; font-size: 25px; margin-right: 5px;">
                                <span> 1</span>
                            </div>
                        </div>
                        <div style="padding: 5px; width: 92%;">
                            <p style="color: #005596; font-size: 14px;">Financial Eligibility: Complete <b>only</b> if
                                you are applying to
                                the Genentech Patient Foundation</p>
                            <p style="color: #000000; font-size: 14px;">By completing this section, I am agreeing to the
                                Terms and
                                Conditions of the Genentech Patient Foundation outlined on page 1.</p>

                            <p style="display: flex;justify-content: space-between; width: 100%;">
                                <label for="household_size"> <span
                                        style="color: #000000; font-size: 14px; line-height: 29px; font-weight: 400;">Household
                                        size (including
                                        you):</span></label>

                                <input type="text"
                                    style="width: 40%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; margin-right: 10px; font-size: 14px;"
                                    name="household_size" id="household_size_input" autocomplete="off"
                                    oninput="validateHouseholdSize(this)"
                                    value="<?php echo isset($household_size) ? htmlspecialchars($household_size) : ''; ?>">
                                <span id="household_size_error" style="color: red;"></span>

                                <label for="annual_household_income"><span
                                        style="color: #000000; font-size: 14px; line-height: 29px; font-weight: 400;">Annual
                                        household income:</span></label>
                                <input
                                    style="height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; font-size: 14px; "
                                    type="radio" name="annual_household_income" id="income_under_75k"
                                    value="Under $75,000"
                                    <?php echo isset($annual_household_income) && $annual_household_income === 'Under $75,000' ? 'checked' : ''; ?>><span
                                    style="display: flex; align-items: left; font-size: 16px; position: relative;">
                                    <label for="income_under_75k" style=" font-size: 14px;"> Under $75,000</label>
                                </span>


                            </p>

                            <p style="display: flex; align-items: center; justify-content: space-between;">


                                <input style=" font-size: 14px; background-color: #dee5ff; margin-right: 5px;"
                                    type="radio" name="annual_household_income" id="income_75k_100k"
                                    value="$75,000 – $100,000"
                                    <?php echo isset($annual_household_income) && $annual_household_income === '$75,000 – $100,000' ? 'checked' : ''; ?>><span
                                    style="display: flex; align-items: left; font-size: 14px; position: relative;">
                                    <label for="income_75k_100k" style=" font-size: 14px;">$75,000 – $100,000</label>
                                </span>

                                <input style=" font-size: 20px; background-color: #dee5ff; margin-right: 5px;"
                                    type="radio" name="annual_household_income" id="income_100k_125k"
                                    value="$100,001 – $125,000"
                                    <?php echo isset($annual_household_income) && $annual_household_income === '$100,001 – $125,000' ? 'checked' : ''; ?>><span
                                    style="display: flex; align-items: left; font-size: 14px; position: relative;">
                                    <label for="income_100k_125k" style=" font-size: 14px;">$100,001 – $125,000</label>
                                </span>

                                <input
                                    style="height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; font-size: 14px; "
                                    type="radio" name="annual_household_income" id="income_125k_150k"
                                    value="$125,001 – $150,000"
                                    <?php echo isset($annual_household_income) && $annual_household_income === '$125,001 – $150,000' ? 'checked' : ''; ?>><span
                                    style="display: flex; align-items: left; font-size: 14px; position: relative;">
                                    <label for="income_125k_150k" style=" font-size: 14px;">$125,001 – $150,000</label>
                                </span>

                                <input
                                    style="height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000; border-top:0px; border-left: 0px; font-size: 14px; "
                                    type="radio" name="annual_household_income" id="income_over_150k"
                                    value="Over $150,000"
                                    <?php echo isset($annual_household_income) && $annual_household_income === 'Over $150,000' ? 'checked' : ''; ?>><span
                                    style="display: flex; align-items: left; font-size: 16px; position: relative;">
                                    <label for="income_over_150k" style=" font-size: 14px;">Over $150,000</label>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div style="margin: 0px 30px; display: flex;  border: 2px solid #005596; margin-bottom: 5px;">
                        <div style="background-color: #005596; width: 8%; position: relative;">
                            <div
                                style="position: absolute; left: 35%;  top: 40%; color: #ffffff; font-size: 25px; margin-right: 5px;">
                                <span>2</span>
                            </div>
                        </div>
                        <div style="padding: 5px; width: 92%; font-weight:normal;">

                            <label style="color: #005596; font-size: 14px;" for="cpri">Consent for Patient Resources and
                                Information <b>(OPTIONAL)</b< /label>
                                    <p style="color: #000000; font-size: 14px; font-weight:normal;">Genentech offers
                                        <b>optional</b> and
                                        free
                                        disease education and
                                        other material for patients. This may
                                        include information and marketing material about products, services and programs
                                        offered
                                        by Genentech, its
                                        partners and their respective affiliates. If you sign up, you may be contacted
                                        using the
                                        information you have provided.</p>

                                    <div style="display: flex; align-items: start; position: relative;">


                                        <input type="checkbox" name="cpri" id="cpri" value="1"
                                            style=" font-size: 18px; background-color: #dee5ff; margin-right: 5px;margin-top: 7px;"
                                            <?php echo isset($cpri) && $cpri === '1' ? 'checked' : ''; ?>>


                                        <p style="color: #000; font-size: 14px; margin-top: 5px; font-weight:normal;">
                                            By checking this box, I agree to receive <b>optional</b> disease education
                                            and other
                                            material.
                                            I understand providing this agreement is voluntary and plays no role in
                                            getting
                                            Genentech Access
                                            Solutions services or my medicine. I also understand that I may opt out of
                                            receiving
                                            this information
                                            at any time by calling <b>(877) 436-3683</b> and that this consent will
                                            remain
                                            active unless I opt out.</p>
                                    </div>
                                    <p style="color: #005596; font-size: 14px; margin-top: 5px;">
                                        <label for="tcpac">Telephone Consumer Protection Act (TCPA) Consent
                                            <b>(OPTIONAL)</b></label>
                                    </p>
                                    <div style="display: flex; align-items: start; position: relative;">

                                        <input type="checkbox" name="tcpac" id="tcpac" value="1"
                                            style=" font-size: 18px; background-color: #dee5ff; margin-right: 5px; margin-top: 7px;"
                                            <?php echo isset($tcpac) && $tcpac === '1' ? 'checked' : ''; ?>>



                                        <p style="color: #000; font-size: 14px; margin-top: 5px; font-weight:normal;">
                                            By checking this box, I consent to receive autodialed marketing calls and
                                            text
                                            messages from and
                                            on behalf of Genentech at the phone number(s) I have provided. I understand
                                            that
                                            consent is not a
                                            requirement of any purchase or enrollment. Message frequency may vary.
                                            Message and
                                            data rates
                                            may apply. I may opt out at any time by texting STOP or calling <b>(877)
                                                GENENTECH</b>/(877) 436-3683.</p>
                                    </div>
                        </div>
                    </div>

                    <div
                        style="margin: 0px 30px; display: flex;  border-top: 2px solid #005596; border-left: 2px solid #005596; border-right: 2px solid #005596; border-bottom: 0px solid #005596; ">
                        <div style="background-color: #005596; width: 8%; position: relative;">
                            <div
                                style="position: absolute; left: 35%;  top: 40%; color: #ffffff; font-size: 25px; margin-right: 5px;">
                                <span>3</span>
                            </div>
                        </div>
                        <div style="padding: 5px; width: 92%;">
                            <p style="color: #005596; font-size: 14px; font-weight:normal;">By signing this form, I
                                acknowledge that I have
                                provided accurate and complete information
                                and understand and agree to the terms of this form. My signature certifies that I have
                                read,
                                understood, and agree to the release and use of my personal information pursuant to the
                                Authorization to Use and Disclose Personal Information and as otherwise stated on this
                                form.</p>
                        </div>
                    </div>

                    <div
                        style="margin: 0px 30px; display: flex;  border-top: 0px solid #005596; border-left: 2px solid #005596;    border-right: 2px solid #005596; border-bottom: 2px solid #005596;  margin-bottom: 5px;">
                        <div style="background-color: #e2e5b7; width: 8%; position: relative;">
                            <div
                                style="    position: absolute; left: 30%; bottom: 0; transform: rotate(-90deg);  transform-origin: 0 0;color: #005596; font-size: 18px; margin-right: 5px; font-weight: 500;">
                                <span>REQUIRED</span>
                            </div>
                        </div>
                        <div style="padding: 5px 5px 5px 0px; width: 92%;">
                            <div style="display: flex;">
                                <p style="position: relative; display: flex; align-self: center; width: 20%;">
                                    <img src="images/icon6.png"
                                        style="position: relative; width: 130px; height: 60px;" />
                                    <span
                                        style="position: absolute; left: 0; top:10px; color: #ffffff; font-size: 13px;  padding-left: 5px;">Sign
                                        and <br /> date here</span>
                                </p>
                                <div style="width: 80%; margin-left: 5px;">
                                    <input style="width: 96%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000;
            border-top: 0px; border-left: 0px;  margin-right: 10px;" type="text" name="sign"
                                        oninput="validateFullName(this)" id="sign"
                                        value="<?php echo isset($sign) ? htmlspecialchars($sign) : ''; ?>" required
                                        autocomplete="off">


                                    <label for="sign"
                                        style="color: #ed1d24;  font-size: 14px;  font-weight: 500; letter-spacing: 0px;margin: 0;  padding: 0;">*Signature
                                        of Patient/Legally Authorized Representative</label>
                                    </p>
                                    <p style="color: #000;font-size: 14px;font-weight: 500;">(A parent or guardian must
                                        sign for patients under 18 years of age)</p>
                                </div>
                                <div style="width: 20%;">
                                    <span
                                        style="display: flex; width: 100%; height: 25px;  border-bottom: 1px solid #000000;">

                                        <input style="height: 22px; background-color: #dee5ff; border:0px; " type="text"
                                            name="date_signed" id="date_signed"
                                            value="<?php echo htmlspecialchars($date_signed); ?>" required>
                                    </span>

                                    <label for="date_signed" style="color: #ed1d24; font-size: 14px;  ">*Date Signed
                                        <span style="color: #000; font-size: 14px;  letter-spacing: 0px;">
                                            (YYYY/MM/DD) </span></label>


                                </div>
                            </div>

                            <div style="display: flex; margin-top: 10px;">
                                <p style="position: relative; display: flex; align-self: center; width: 20%;">
                                    <img src="images/icon6.png"
                                        style="position: relative; width: 130px; height: 60px;" />
                                    <span
                                        style="position: absolute; left: 0; top:10px; color: #ffffff; font-size: 15px; padding-left: 5px;">
                                        <span style=" font-size: 13px; "> Person signing </span><br /> <span
                                            style=" font-size: 13px; ">(if not
                                            patient) </span>
                                </p>
                                <div style="width: 30%; margin-right: 10px;">
                                    <input style="width: 100%; height: 25px;  background-color: #ffffff;border-bottom: 1px solid #000000; border-top: 0px; border-left: 0px;
            margin-right: 10px; background-color: #dee5ff; font-size: 14px;" type="text" name="p_fname"
                                        oninput="validateFullName(this)" id="p_fname"
                                        value="<?php echo isset($p_fname) ? htmlspecialchars($p_fname) : ''; ?>"
                                        autocomplete="off">
                                    <p style="color: #000; font-size: 14px;text-align: center;
        "> <label for="p_fname">Print first name</label></p>
                                </div>

                                <div style="width: 30%; margin-right: 10px;">
                                    <input type="text"
                                        style="width: 100%; background-color: #ffffff;  border-bottom: 1px solid #000000; border-top: 0px; font-size: 14px; border-left: 0px; margin-right: 10px; background-color: #dee5ff;"
                                        name="p_lname" id="p_lname" oninput="validateFullName(this)"
                                        value="<?php echo isset($p_lname) ? htmlspecialchars($p_lname) : ''; ?>"
                                        autocomplete="off">

                                    <p style="color: #000; font-size: 14px;font-weight: 400; text-align: center; ">
                                        <label for="p_lname">Print last name </label>
                                    </p>
                                </div>


                                <div style="width: 30%; margin-right: 5px;">
                                    <input style="width: 100%; height: 25px; font-size: 14px; background-color: #ffffff;  border-bottom: 1px solid #000000; border-top: 0px;  border-left: 0px;
            margin-right: 10px; background-color: #dee5ff;" oninput="validateFullName(this)" type="text"
                                        name="r_patient" id="r_patient"
                                        value="<?php echo isset($r_patient) ? htmlspecialchars($r_patient) : ''; ?>"
                                        autocomplete="off">

                                    <p style="color: #000; font-size: 14x; font-weight: 400; text-align: center; ">
                                        <label for="r_patient">Relationship to patient</label>
                                    </p>
                                </div>
                            </div>


                        </div>
                    </div>


                    <div style="margin: 0px 30px;">
                        <p style="color: #000;font-size: 14px; font-weight:normal;">
                            <span style="color: #005596; font-size: 14px; font-weight:normal;"><b>Once this page (3/3)
                                    has been
                                    completed,</b></span>
                            please text a photo of the page to <b>(650) 877-1111</b> or fax to
                            <b>(866) 480-7762</b>. You can also complete this form online at
                            <b>Genentech-Access.com/PatientConsent.</b>
                        </p>
                        <p style="color: #000; font-size: 14px; font-weight:normal;">
                            If this is an electronic consent, you understand that by typing your name and the date above
                            and submitting, or taking a picture and sending to us, that you are providing your consent
                            electronically and that it has the same force and effect as if you were signing in person on
                            paper. Genentech reserves the right to rescind, revoke or amend the program without notice
                            at any time.</p>
                    </div>
                </div>
        </div>

        <input type="submit" name="save" value="Save " class="btn btn-primary btn-save">
        <input type="submit" name="update" value="Update" style="display:none" class="btn  btn-info btn-save">


        <a href="patient_consent_form_pdf.php?generate_pdf=1&patient_id=<?php echo $patient_id; ?>"
            class="btn btn-success btn-save">View / Download PDF</a>

        </form>

</body>

</html>

<!-- Your HTML form -->
<form method="POST" action="save_patient.php" style="display:none">

    <!-- First Name -->
    <label for="fname">First Name:</label>
    <input type="text" name="fname" id="fname" value="<?php echo isset($fname) ? htmlspecialchars($fname) : ''; ?>"
        required>

    <!-- Last Name -->
    <label for="lname">Last Name:</label>
    <input type="text" name="lname" id="lname" value="<?php echo isset($lname) ? htmlspecialchars($lname) : ''; ?>"
        required>

    <!-- Date of Birth -->
    <label for="dob">Date of Birth:</label>
    <input type="text" name="dob" id="dob" value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>" required>

    <!-- Phone Home -->
    <label for="phone_home">Home Phone:</label>
    <!-- <input type="text" name="phone_home" id="phone_home"
        value="<?php echo isset($phone_home) ? htmlspecialchars($phone_home) : ''; ?>" required> -->

    <input type="tel" name="phone_home" id="phone_home"
        value="<?php echo isset($phone_home) ? htmlspecialchars($phone_home) : ''; ?>" required
        data-inputmask="'mask': '999-999-9999'">

    <!-- Phone Cell -->
    <label for="phone_cell">Cell Phone:</label>
    <input type="text" name="phone_cell" id="phone_cell"
        value="<?php echo isset($phone_cell) ? htmlspecialchars($phone_cell) : ''; ?>" required>

    <!-- Email -->
    <label for="email">Email:</label>
    <input type="email" name="email" id="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
        required autocomplete="off">

    <!-- Checkbox for ok_leave -->
    <label for="ok_leave">OK to leave a detailed message?</label>

    <input type="checkbox" name="ok_leave" id="ok_leave" value="1"
        <?php echo isset($ok_leave) && $ok_leave === '1' ? 'checked' : ''; ?>>
    <!-- Preferred Language (radio buttons) -->
    <label for="preferred_language">Preferred Language:</label><br>
    <input type="radio" name="preferred_language" value="English" id="english_option"
        <?php echo isset($preferred_language) && $preferred_language === 'English' ? 'checked' : ''; ?>> English<br>
    <input type="radio" name="preferred_language" value="Spanish" id="spanish_option"
        <?php echo isset($preferred_language) && $preferred_language === 'Spanish' ? 'checked' : ''; ?>> Spanish<br>
    <input type="radio" name="preferred_language" value="Others" id="others_option"
        <?php echo isset($preferred_language) && $preferred_language === 'Others' ? 'checked' : ''; ?>> Others<br>


    <!-- Text box for "Other Language" -->
    <label for="other_language" id="other_language_label" style="display: none;">Other Language:</label>
    <input type="text" name="other_language" id="other_language" style="display: none;"
        value="<?php echo isset($other_language) ? htmlspecialchars($other_language) : ''; ?>">



    <!-- Alternate Contact (optional) -->

    <label for="alter_contact">Alternate Contact Full Name (optional):</label>
    <input type="text" name="alter_contact" id="alter_contact" autocomplete="off" oninput="validateFullName(this)"
        value="<?php echo isset($alter_contact) ? htmlspecialchars($alter_contact) : ''; ?>">

    <label for="relationship">Relationship:</label>

    <input type="text" name="relationship" id="relationship" oninput="validateFullName(this)"
        value="<?php echo isset($relationship) ? htmlspecialchars($relationship) : ''; ?>" required autocomplete="off">
    <!-- Phone Home -->
    <label for="phone">Home Phone:</label>

    <input type="text" name="p_phone" id="p_phone" autocomplete="off"
        value="<?php echo isset($p_phone) ? htmlspecialchars($p_phone) : ''; ?>">
    <!-- Household Size -->
    <label for="household_size">Household Size:</label>


    <input type="text" name="household_size" id="household_size_input" oninput="validateHouseholdSize(this)"
        value="<?php echo isset($household_size) ? htmlspecialchars($household_size) : ''; ?>" autocomplete="off">
    <span id="household_size_error" style="color: red;"></span>
    <!-- Annual Household Income -->
    <label for="annual_household_income">Annual Household Income:</label>
    <input type="radio" name="annual_household_income" id="income_under_75k" value="Under $75,000"
        <?php echo isset($annual_household_income) && $annual_household_income === 'Under $75,000' ? 'checked' : ''; ?>>
    <label for="income_under_75k">Under $75,000</label>

    <input type="radio" name="annual_household_income" id="income_75k_100k" value="$75,000 – $100,000"
        <?php echo isset($annual_household_income) && $annual_household_income === '$75,000 – $100,000' ? 'checked' : ''; ?>>
    <label for="income_75k_100k">$75,000 – $100,000</label>

    <input type="radio" name="annual_household_income" id="income_100k_125k" value="$100,001 – $125,000"
        <?php echo isset($annual_household_income) && $annual_household_income === '$100,001 – $125,000' ? 'checked' : ''; ?>>
    <label for="income_100k_125k">$100,001 – $125,000</label>

    <input type="radio" name="annual_household_income" id="income_125k_150k" value="$125,001 – $150,000"
        <?php echo isset($annual_household_income) && $annual_household_income === '$125,001 – $150,000' ? 'checked' : ''; ?>>
    <label for="income_125k_150k">$125,001 – $150,000</label>

    <input type="radio" name="annual_household_income" id="income_over_150k" value="Over $150,000"
        <?php echo isset($annual_household_income) && $annual_household_income === 'Over $150,000' ? 'checked' : ''; ?>>
    <label for="income_over_150k">Over $150,000</label>
    <!-- Consent for Patient Resources and Information (OPTIONAL) -->
    <label for="cpri">Consent for Patient Resources and Information (OPTIONAL):</label>
    <!-- <input type="checkbox" name="cpri" id="cpri" value="1"> -->
    <input type="checkbox" name="cpri" id="cpri" value="1"
        <?php echo isset($cpri) && $cpri === '1' ? 'checked' : ''; ?>>
    <!-- Telephone Consumer Protection Act (TCPA) Consent (OPTIONAL) -->
    <label for="tcpac">Telephone Consumer Protection Act (TCPA) Consent (OPTIONAL):</label>
    <!-- <input type="checkbox" name="tcpac" id="tcpac" value="1"> -->
    <input type="checkbox" name="tcpac" id="tcpac" value="1"
        <?php echo isset($tcpac) && $tcpac === '1' ? 'checked' : ''; ?>>
    <!-- Signature of Patient/Legally Authorized Representative (text box) -->
    <label for="sign">Signature of Patient/Legally Authorized Representative:</label>
    <input type="text" name="sign" id="sign" oninput="validateFullName(this)"
        value="<?php echo isset($sign) ? htmlspecialchars($sign) : ''; ?>" required autocomplete="off">



    <label for="date_signed">Date Signed:</label>
    <input type="text" id="date_signed" name="date_signed" value="<?php echo htmlspecialchars($date_signed); ?>"
        required>
    <!-- Add this script to initialize the date picker -->

    <!-- Print First Name (text box) -->
    <label for="p_fname">Print First Name:</label>
    <input type="text" name="p_fname" id="p_fname" oninput="validateFullName(this)"
        value="<?php echo isset($p_fname) ? htmlspecialchars($p_fname) : ''; ?>" required autocomplete="off">

    <!-- Print Last Name (text box) -->
    <label for="p_lname">Print Last Name:</label>
    <input type="text" name="p_lname" id="p_lname" oninput="validateFullName(this)"
        value="<?php echo isset($p_lname) ? htmlspecialchars($p_lname) : ''; ?>" required autocomplete="off">

    <!-- Relationship to Patient (text box) -->
    <label for="r_patient">Relationship to Patient:</label>
    <input type="text" name="r_patient" id="r_patient" oninput="validateFullName(this)"
        value="<?php echo isset($r_patient) ? htmlspecialchars($r_patient) : ''; ?>" required autocomplete="off">

    <!-- Add other form fields as needed -->

    <!-- Submit Button -->
    <!-- Submit Button -->
    <input type="submit" name="save" value="Save ">
    <input type="submit" name="update" value="Update ">

    <!-- Add this link within your existing form -->
    <!-- <a href="patient_consent_form_pdf.php" target="_blank">Download PDF</a> -->
    <a href="patient_consent_form_pdf.php?generate_pdf=1">View / Download PDF</a>


</form>

<script>
// JavaScript code for controlling page behavior
function generatePDF() {
    // Simulate a click on the "Save" button when the "Generate PDF" link is clicked
    document.getElementById('saveButton').click();
}

// JavaScript code to toggle the "Other Language" input field based on radio button selection
document.addEventListener("DOMContentLoaded", function() {
    const othersOption = document.getElementById("others_option");
    const otherLanguageLabel = document.getElementById("other_language_label");
    const otherLanguageInput = document.getElementById("other_language");

    // Initially, check if "Others" is selected and show the input field if necessary
    toggleOtherLanguageInput();

    // Add an event listener to the radio button to monitor changes
    othersOption.addEventListener("change", toggleOtherLanguageInput);

    function toggleOtherLanguageInput() {
        if (othersOption.checked) {
            otherLanguageLabel.style.display = "inline"; // Show the label
            otherLanguageInput.style.display = "inline"; // Show the input field
        } else {
            otherLanguageLabel.style.display = "none"; // Hide the label
            otherLanguageInput.style.display = "none"; // Hide the input field
            // If "Others" is not selected, clear the value of the input field
            otherLanguageInput.value = "";
        }
    }
});
</script>
<script>
// Function to format phone number as (123) 456-7890
function formatPhoneNumber(phoneNumber) {
    // Remove all non-numeric characters
    const numericOnly = phoneNumber.replace(/\D/g, '');

    // Check if the numericOnly value is not empty
    if (numericOnly.length > 0) {
        // Format the phone number
        const formattedPhoneNumber =
            `+1 (${numericOnly.substring(0, 3)}) ${numericOnly.substring(3, 6)}-${numericOnly.substring(6, 10)}`;
        return formattedPhoneNumber;
    } else {
        return ''; // Return an empty string if no numeric characters are entered
    }
}

// Add an event listener to format the phone number as the user types
const phoneHomeInput = document.getElementById('phone_home');
phoneHomeInput.addEventListener('input', function() {
    this.value = formatPhoneNumber(this.value);
});
const phoneMobileInput = document.getElementById('phone_cell');
phoneMobileInput.addEventListener('input', function() {
    this.value = formatPhoneNumber(this.value);
});
const phoneInput = document.getElementById('p_phone');
phoneInput.addEventListener('input', function() {
    this.value = formatPhoneNumber(this.value);
});

function validateFullName(input) {
    // Remove any non-alphabet characters except space
    input.value = input.value.replace(/[^A-Za-z\s]/g, '');

    // Remove extra spaces (more than one space in a row)
    input.value = input.value.replace(/\s{2,}/g, ' ');
}

function validateHouseholdSize(input) {
    // Remove any non-numeric characters, spaces, commas, and dots
    var cleanedValue = input.value.replace(/[^0-9,.\s]/g, '');

    // Update the input value with the cleaned value
    input.value = cleanedValue;

    // Display an error message if the input contains invalid characters
    var errorSpan = document.getElementById('household_size_error');
    if (cleanedValue !== input.value) {
        errorSpan.textContent = 'Only numbers, spaces, commas, and dots are allowed.';
    } else {
        errorSpan.textContent = '';
    }
}
flatpickr("#date_signed", {
    dateFormat: "Y-m-d", // Customize the date format as needed
});
flatpickr("#dob", {
    dateFormat: "Y-m-d", // Customize the date format as needed
});
</script>