<?php
/**
 *
 * Patient summary screen.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @author    Sharon Cohen <sharonco@matrix.co.il>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2017 Sharon Cohen <sharonco@matrix.co.il>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/acl.inc");
require_once("$srcdir/options.inc.php");
require_once("../history/history.inc.php");
require_once("$srcdir/edi.inc");
require_once("$srcdir/invoice_summary.inc.php");
require_once("$srcdir/clinical_rules.php");
require_once("$srcdir/options.js.php");
require_once("$srcdir/group.inc");
require_once(dirname(__FILE__) . "/../../../library/appointments.inc.php");

use OpenEMR\Core\Header;
use OpenEMR\Menu\PatientMenuRole;
use OpenEMR\Reminder\BirthdayReminder;

if (isset($_GET['set_pid'])) {
    include_once("$srcdir/pid.inc");
    setpid($_GET['set_pid']);
}

$patient_id = $_SESSION['pid'];
$primary_pharmacy_details = array();
$secondary_pharmacy_details = array();

$get_patient_covid_data = sqlStatement("SELECT * FROM patient_covid_data WHERE patient_id = '$patient_id'");

$pharmacy_query = sqlStatement("SELECT * FROM patient_pharmacy WHERE patient_id = '$patient_id' ORDER BY name ASC");
if(sqlNumRows($pharmacy_query) > 0) {
    while($pharmacy_row = sqlFetchArray($pharmacy_query)) {
        if($pharmacy_row['pharmacy_type'] == "Primary") {
            $primary_pharmacy_details[] = array(
                    'id'            =>  $pharmacy_row['id'],
                    'name'          =>  $pharmacy_row['name'],
                    'address'       =>  $pharmacy_row['address'],
                    'phone_number'  =>  $pharmacy_row['phone_number'],
                    'email'         =>  $pharmacy_row['email'],
                    'fax_number'    =>  $pharmacy_row['fax_number'],
                    'website'       =>  $pharmacy_row['website'],
                    'created_date'  =>  date('m-d-Y h:i A')
                );
        } else {
            $secondary_pharmacy_details[] = array(
                    'id'            =>  $pharmacy_row['id'],
                    'name'          =>  $pharmacy_row['name'],
                    'address'       =>  $pharmacy_row['address'],
                    'phone_number'  =>  $pharmacy_row['phone_number'],
                    'email'         =>  $pharmacy_row['email'],
                    'fax_number'    =>  $pharmacy_row['fax_number'],
                    'website'       =>  $pharmacy_row['website'],
                    'created_date'  =>  date('m-d-Y h:i A')
                );
        }
    }
}

$active_reminders = false;
$all_allergy_alerts = false;
if ($GLOBALS['enable_cdr']) {
    //CDR Engine stuff
    if ($GLOBALS['enable_allergy_check'] && $GLOBALS['enable_alert_log']) {
        //Check for new allergies conflicts and throw popup if any exist(note need alert logging to support this)
        $new_allergy_alerts = allergy_conflict($pid, 'new', $_SESSION['authUser']);
        if (!empty($new_allergy_alerts)) {
            $pop_warning = '<script type="text/javascript">alert(\'' . xls('WARNING - FOLLOWING ACTIVE MEDICATIONS ARE ALLERGIES') . ':\n';
            foreach ($new_allergy_alerts as $new_allergy_alert) {
                $pop_warning .= addslashes($new_allergy_alert) . '\n';
            }

            $pop_warning .= '\')</script>';
            echo $pop_warning;
        }
    }

    if ((!isset($_SESSION['alert_notify_pid']) || ($_SESSION['alert_notify_pid'] != $pid)) && isset($_GET['set_pid']) && $GLOBALS['enable_cdr_crp']) {
        // showing a new patient, so check for active reminders and allergy conflicts, which use in active reminder popup
        $active_reminders = active_alert_summary($pid, "reminders-due", '', 'default', $_SESSION['authUser'], true);
        if ($GLOBALS['enable_allergy_check']) {
            $all_allergy_alerts = allergy_conflict($pid, 'all', $_SESSION['authUser'], true);
        }
    }
}

function print_as_money($money) {
    preg_match("/(\d*)\.?(\d*)/", $money, $moneymatches);
    $tmp = wordwrap(strrev($moneymatches[1]), 3, ",", 1);
    $ccheck = strrev($tmp);
    if ($ccheck[0] == ",") {
        $tmp = substr($ccheck, 1, strlen($ccheck) - 1);
    }

    if ($moneymatches[2] != "") {
        return "$ " . strrev($tmp) . "." . $moneymatches[2];
    } else {
        return "$ " . strrev($tmp);
    }
}

// get an array from Photos category
function pic_array($pid, $picture_directory) {
    $pics = array();
    $sql_query = "select documents.id from documents join categories_to_documents " .
            "on documents.id = categories_to_documents.document_id " .
            "join categories on categories.id = categories_to_documents.category_id " .
            "where categories.name like ? and documents.foreign_id = ?";
    if ($query = sqlStatement($sql_query, array($picture_directory, $pid))) {
        while ($results = sqlFetchArray($query)) {
            array_push($pics, $results['id']);
        }
    }

    return ($pics);
}

// Get the document ID of the first document in a specific catg.
function get_document_by_catg($pid, $doc_catg) {

    $result = array();

    if ($pid and $doc_catg) {
        $result = sqlQuery("SELECT d.id, d.date, d.url FROM " .
                "documents AS d, categories_to_documents AS cd, categories AS c " .
                "WHERE d.foreign_id = ? " .
                "AND cd.document_id = d.id " .
                "AND c.id = cd.category_id " .
                "AND c.name LIKE ? " .
                "ORDER BY d.date DESC LIMIT 1", array($pid, $doc_catg));
    }

    return($result['id']);
}

// Display image in 'widget style'
function image_widget($doc_id, $doc_catg) {
    global $pid, $web_root;
    $docobj = new Document($doc_id);
    $image_file = $docobj->get_url_file();
    $image_width = $GLOBALS['generate_doc_thumb'] == 1 ? '' : 'width=100';
    $extension = substr($image_file, strrpos($image_file, "."));
    $viewable_types = array('.png', '.jpg', '.jpeg', '.png', '.bmp', '.PNG', '.JPG', '.JPEG', '.PNG', '.BMP');
    if (in_array($extension, $viewable_types)) { // extention matches list
        $to_url = "<td> <a href = $web_root" .
                "/controller.php?document&retrieve&patient_id=$pid&document_id=$doc_id&as_file=false&original_file=true&disable_exit=false&show_original=true" .
                "/tmp$extension" . // Force image type URL for fancybo
                " onclick=top.restoreSession(); class='image_modal'>" .
                " <img src = $web_root" .
                "/controller.php?document&retrieve&patient_id=$pid&document_id=$doc_id&as_file=false" .
                " $image_width alt='$doc_catg:$image_file'>  </a> </td> <td valign='center'>" .
                htmlspecialchars($doc_catg) . '<br />&nbsp;' . htmlspecialchars($image_file) .
                "</td>";
    } else {
        $to_url = "<td> <a href='" . $web_root . "/controller.php?document&retrieve" .
                "&patient_id=$pid&document_id=$doc_id'" .
                " onclick='top.restoreSession()' class='css_button_small'>" .
                "<span>" .
                htmlspecialchars(xl("View"), ENT_QUOTES) . "</a> &nbsp;" .
                htmlspecialchars("$doc_catg - $image_file", ENT_QUOTES) .
                "</span> </td>";
    }

    echo "<table><tr>";
    echo $to_url;
    echo "</tr></table>";
}

// Determine if the Vitals form is in use for this site.
$tmp = sqlQuery("SELECT count(*) AS count FROM registry WHERE " .
        "directory = 'vitals' AND state = 1");
$vitals_is_registered = $tmp['count'];

// Get patient/employer/insurance information.
//
$result = getPatientData($pid, "*, DATE_FORMAT(DOB,'%Y-%m-%d') as DOB_YMD");
$result2 = getEmployerData($pid);
$result3 = getInsuranceData($pid, "primary", "copay, provider, DATE_FORMAT(`date`,'%Y-%m-%d') as effdate");
$insco_name = "";
if ($result3['provider']) {   // Use provider in case there is an ins record w/ unassigned insco
    $insco_name = getInsuranceProvider($result3['provider']);
}


// Initialize variables to store form data
$act_sign = '';
$act_date_signed = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission
    $act_sign = $_POST['act_sign'];
    $act_date_signed = $_POST['act_date_signed'];
    
    // Store or update the data in the database (you can add your database logic here)
    
    // Redirect to another page or display a success message
}

// Check if the patient_act_notice data exists for the current patient
$select_query = "SELECT act_sign, act_date_signed FROM patient_act_notice WHERE patient_id = ?";
$select_params = array($patient_id);
$act_notice_data = sqlQuery($select_query, $select_params);

// If data is found, populate the variables
if ($act_notice_data !== false) {
    $act_sign = $act_notice_data['act_sign'];
    $act_date_signed = $act_notice_data['act_date_signed'];
}
?>
<html>

<head>

    <?php Header::setupHeader(['common']); ?>

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

    <script type="text/javascript" language="JavaScript">
    var mypcc = '<?php echo htmlspecialchars($GLOBALS['phone_country_code'], ENT_QUOTES); ?>';
    //////////
    function oldEvt(apptdate, eventid) {
        let title = '<?php echo xla('Appointments'); ?>';
        dlgopen('../../main/calendar/add_edit_event.php?date=' + apptdate + '&eid=' + eventid, '_blank', 'modal-lg',
            500, '', title);
    }

    function advdirconfigure() {
        dlgopen('advancedirectives.php', '_blank', 400, 500);
    }

    function refreshme() {
        top.restoreSession();
        location.reload();
    }

    // Process click on Delete link.
    function deleteme() { // @todo don't think this is used any longer!!
        dlgopen('../deleter.php?patient=<?php echo htmlspecialchars($pid, ENT_QUOTES); ?>', '_blank', 500, 450, '',
            '', {
                allowResize: false,
                allowDrag: false,
                dialogId: 'patdel',
                type: 'iframe'
            });
        return false;
    }

    // Called by the deleteme.php window on a successful delete.
    function imdeleted() {
        <?php if ($GLOBALS['new_tabs_layout']) { ?>
        top.clearPatient();
        <?php } else { ?>
        parent.left_nav.clearPatient();
        <?php } ?>
    }

    function newEvt() {
        let title = '<?php echo xla('Appointments'); ?>';
        let url = '../../main/calendar/add_edit_event.php?patientid=<?php echo htmlspecialchars($pid, ENT_QUOTES); ?>';
        dlgopen(url, '_blank', 'modal-lg', 500, '', title);
        return false;
    }

    function chatDetails(channelName) {
        let title = '<?php echo xla('Chat Details'); ?>';
        let url = '../../main/profile/chat_history.php?channel=' + channelName;
        dlgopen(url, '_blank', 'modal-lg', 500, '', title);
        return false;
    }

    function sendimage(pid, what) {
        // alert('Not yet implemented.'); return false;
        dlgopen('../upload_dialog.php?patientid=' + pid + '&file=' + what,
            '_blank', 500, 400);
        return false;
    }
    </script>

    <script type="text/javascript">
    function toggleIndicator(target, div) {

        $mode = $(target).find(".indicator").attr('title');
        if ($mode == 'collapse') {
            $(target).find(".indicator").attr('title', 'expand');
            $(target).find(".indicator").html("<i class='fa fa-chevron-up'></i>");
            $("#" + div).hide();
            $.post("../../../library/ajax/user_settings.php", {
                target: div,
                mode: 0
            });
        } else {
            $(target).find(".indicator").attr('title', 'collapse');
            $(target).find(".indicator").html("<i class='fa fa-chevron-down'></i>");
            $("#" + div).show();
            $.post("../../../library/ajax/user_settings.php", {
                target: div,
                mode: 1
            });
        }

        //    if ( $mode == "<?php echo htmlspecialchars(xl('collapse'), ENT_QUOTES); ?>" ) {
        //        $(target).find(".indicator").text( "<?php echo htmlspecialchars(xl('expand'), ENT_QUOTES); ?>" );
        //        $("#"+div).hide();
        //    $.post( "../../../library/ajax/user_settings.php", { target: div, mode: 0 });
        //    } else {
        //        $(target).find(".indicator").text( "<?php echo htmlspecialchars(xl('collapse'), ENT_QUOTES); ?>" );
        //        $("#"+div).show();
        //    $.post( "../../../library/ajax/user_settings.php", { target: div, mode: 1 });
        //    }
    }

    // edit prescriptions dialog.
    // called from stats.php.
    //
    function editScripts(url) {
        var AddScript = function() {
            var iam = top.tab_mode ? top.frames.editScripts : window[0];
            iam.location.href =
                "<?php echo $GLOBALS['webroot'] ?>/controller.php?prescription&edit&id=&pid=<?php echo attr($pid); ?>"
        };
        var ListScripts = function() {
            var iam = top.tab_mode ? top.frames.editScripts : window[0];
            iam.location.href =
                "<?php echo $GLOBALS['webroot'] ?>/controller.php?prescription&list&id=<?php echo attr($pid); ?>"
        };
        let title = '<?php echo xla('Prescriptions'); ?>';
        let w = 810;
        <?php
if ($GLOBALS['weno_rx_enable']) {
    echo 'w = 910;';
}
?>


        dlgopen(url, 'editScripts', 'modal-lg', 300, '', '', {
            buttons: [{
                    text: '<?php echo xla('Add'); ?>',
                    close: false,
                    style: 'primary  btn-sm',
                    click: AddScript
                },
                {
                    text: '<?php echo xla('List'); ?>',
                    close: false,
                    style: 'primary  btn-sm',
                    click: ListScripts
                },
                {
                    text: '<?php echo xla('Done'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }
            ],
            onClosed: 'refreshme',
            allowResize: true,
            allowDrag: true,
            dialogId: 'editscripts',
            type: 'iframe'
        });
    }

    function doPublish() {
        let title = '<?php echo xla('Publish Patient to FHIR Server'); ?>';
        let url = top.webroot_url + '/phpfhir/providerPublishUI.php?patient_id=<?php echo attr($pid); ?>';
        dlgopen(url, 'publish', 'modal-lg', 750, '', '', {
            buttons: [{
                text: '<?php echo xla('Done'); ?>',
                close: true,
                style: 'default btn-sm'
            }],
            allowResize: true,
            allowDrag: true,
            dialogId: '',
            type: 'iframe'
        });
    }

    function CreateFolderInGSuite(patient_id) {
        var g_suite_api_end_point = $("#g_suite_api_end_point").val();

        $.ajax({
            type: 'GET',
            url: g_suite_api_end_point + 'api/google_api/google_drive/create_patient_folder',
            data: {
                'patient_id': patient_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.Status == "Success") {

                } else {

                }
            }
        });
    }

    $(document).ready(function() {

        var g_suite_status = $("#g_suite_status").val();
        if (g_suite_status == "On") {
            var current_patient_id = '<?php echo $_SESSION["pid"]; ?>';
            CreateFolderInGSuite(current_patient_id);
        }
        var msg_updation = '';
        <?php
if ($GLOBALS['erx_enable']) {
    //$soap_status=sqlQuery("select soap_import_status from patient_data where pid=?",array($pid));
    $soap_status = sqlStatement("select soap_import_status,pid from patient_data where pid=? and soap_import_status in ('1','3')", array($pid));
    while ($row_soapstatus = sqlFetchArray($soap_status)) {
        //if($soap_status['soap_import_status']=='1' || $soap_status['soap_import_status']=='3'){ 
        ?>
        top.restoreSession();
        $.ajax({
            type: "POST",
            url: "../../soap_functions/soap_patientfullmedication.php",
            dataType: "html",
            data: {
                patient: <?php echo $row_soapstatus['pid']; ?>,
            },
            async: false,
            success: function(thedata) {
                //alert(thedata);
                msg_updation += thedata;
            },
            error: function() {
                alert('ajax error');
            }
        });
        <?php
        //}
        //elseif($soap_status['soap_import_status']=='3'){ 
        ?>
        top.restoreSession();
        $.ajax({
            type: "POST",
            url: "../../soap_functions/soap_allergy.php",
            dataType: "html",
            data: {
                patient: <?php echo $row_soapstatus['pid']; ?>,
            },
            async: false,
            success: function(thedata) {
                //alert(thedata);
                msg_updation += thedata;
            },
            error: function() {
                alert('ajax error');
            }
        });
        <?php if ($GLOBALS['erx_import_status_message']) { ?>
        if (msg_updation)
            alert(msg_updation);
        <?php
        }

        //}
    }
}
?>
        // load divs
        $("#stats_div").load("stats.php", {
            'embeddedScreen': true
        }, function() {});
        $("#pnotes_ps_expand").load("pnotes_fragment.php");
        $("#disclosures_ps_expand").load("disc_fragment.php");
        <?php if ($GLOBALS['enable_cdr'] && $GLOBALS['enable_cdr_crw']) { ?>
        top.restoreSession();
        $("#clinical_reminders_ps_expand").load("clinical_reminders_fragment.php", {
            'embeddedScreen': true
        }, function() {
            // (note need to place javascript code here also to get the dynamic link to work)
            $(".medium_modal").on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dlgopen('', '', 800, 200, '', '', {
                    buttons: [{
                        text: '<?php echo xla('Close'); ?>',
                        close: true,
                        style: 'default btn-sm'
                    }],
                    onClosed: 'refreshme',
                    allowResize: false,
                    allowDrag: true,
                    dialogId: 'demreminder',
                    type: 'iframe',
                    url: $(this).attr('href')
                });
            });
        });
        <?php } // end crw   ?>

        <?php if ($GLOBALS['enable_cdr'] && $GLOBALS['enable_cdr_prw']) { ?>
        top.restoreSession();
        $("#patient_reminders_ps_expand").load("patient_reminders_fragment.php");
        <?php } // end prw   ?>

        <?php if ($vitals_is_registered && acl_check('patients', 'med')) { ?>
        // Initialize the Vitals form if it is registered and user is authorized.
        $("#vitals_ps_expand").load("vitals_fragment.php");
        <?php } ?>
        // Initialize track_anything
        $("#track_anything_ps_expand").load("track_anything_fragment.php");
        // Initialize labdata
        $("#labdata_ps_expand").load("labdata_fragment.php");
        <?php
// Initialize for each applicable LBF form.
$gfres = sqlStatement("SELECT grp_form_id FROM layout_group_properties WHERE " .
        "grp_form_id LIKE 'LBF%' AND grp_group_id = '' AND grp_repeats > 0 AND grp_activity = 1 " .
        "ORDER BY grp_seq, grp_title");
while ($gfrow = sqlFetchArray($gfres)) {
    ?>
        $("#<?php echo attr($gfrow['grp_form_id']); ?>_ps_expand").load(
            "lbf_fragment.php?formname=<?php echo attr($gfrow['grp_form_id']); ?>");
        <?php
}
?>
        tabbify();
        // modal for dialog boxes
        $(".large_modal").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dlgopen('', '', 'modal-lg', 600, '', '', {
                buttons: [{
                    text: '<?php echo xla('Close'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }],
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });
        $(".rx_modal").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var AddAmendment = function() {
                var iam = top.tab_mode ? top.frames.editAmendments : window[0];
                iam.location.href =
                    "<?php echo $GLOBALS['webroot'] ?>/interface/patient_file/summary/add_edit_amendments.php"
            };
            var ListAmendments = function() {
                var iam = top.tab_mode ? top.frames.editAmendments : window[0];
                iam.location.href =
                    "<?php echo $GLOBALS['webroot'] ?>/interface/patient_file/summary/list_amendments.php"
            };
            var title = '<?php echo xla('Amendments'); ?>';
            dlgopen('', 'editAmendments', 'modal-lg', 900, '', title, {
                buttons: [{
                        text: '<?php echo xla('Add'); ?>',
                        close: false,
                        style: 'primary  btn-sm',
                        click: AddAmendment
                    },
                    {
                        text: '<?php echo xla('List'); ?>',
                        close: false,
                        style: 'primary  btn-sm',
                        click: ListAmendments
                    },
                    {
                        text: '<?php echo xla('Done'); ?>',
                        close: true,
                        style: 'danger btn-sm'
                    }
                ],
                onClosed: 'refreshme',
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });
        // modal for image viewer
        $(".image_modal").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dlgopen('', '', 'modal-lg', 300, '', '<?php echo xla('Patient Images'); ?>', {
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });
        $(".deleter").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dlgopen('', '', 'modal-lg', 360, '', '', {
                buttons: [{
                    text: '<?php echo xla('Close'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }],
                //onClosed: 'imdeleted',
                allowResize: false,
                allowDrag: false,
                dialogId: 'patdel',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });
        $(".iframe1").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dlgopen('', '', 'modal-lg', 300, '', '', {
                buttons: [{
                    text: '<?php echo xla('Close'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }],
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });
        // for patient portal
        $(".small_modal").on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dlgopen('', '', 'modal-lg', 200, '', '', {
                buttons: [{
                    text: '<?php echo xla('Close'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }],
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $(this).attr('href')
            });
        });

        function openReminderPopup() {
            top.restoreSession()
            dlgopen('', 'reminders', 500, 250, '', '', {
                buttons: [{
                    text: '<?php echo xla('Close'); ?>',
                    close: true,
                    style: 'default btn-sm'
                }],
                allowResize: true,
                allowDrag: true,
                dialogId: '',
                type: 'iframe',
                url: $("#reminder_popup_link").attr('href')
            });
        }


        <?php
if ($GLOBALS['patient_birthday_alert']) {
    // To display the birthday alert:
    //  1. The patient is not deceased
    //  2. The birthday is today (or in the past depending on global selection)
    //  3. The notification has not been turned off (or shown depending on global selection) for this year
    $birthdayAlert = new BirthdayReminder($pid, $_SESSION['authUserID']);
    if ($birthdayAlert->isDisplayBirthdayAlert()) {
        ?>
        // show the active reminder modal
        dlgopen('', 'bdayreminder', 300, 170, '', false, {
            allowResize: false,
            allowDrag: true,
            dialogId: '',
            type: 'iframe',
            url: $("#birthday_popup").attr('href')
        });
        <?php } elseif ($active_reminders || $all_allergy_alerts) { ?>
        openReminderPopup();
        <?php } ?>
        <?php } elseif ($active_reminders || $all_allergy_alerts) { ?>
        openReminderPopup();
        <?php } ?>
    });
    // JavaScript stuff to do when a new patient is set.
    //
    function setMyPatient() {
        // Avoid race conditions with loading of the left_nav or Title frame.

        <?php if (isset($_GET['set_pid'])) { ?> parent.left_nav.setPatient(<?php
    echo "'" . addslashes($result['fname']) . " " . addslashes($result['lname']) .
    "'," . addslashes($pid) . ",'" . addslashes($result['pubpid']) .
    "','', ' " . xls('DOB') . ": " . addslashes(oeFormatShortDate($result['DOB_YMD'])) . " " . xls('Age') . ": " . addslashes(getPatientAgeDisplay($result['DOB_YMD'])) . "'";
    ?>);
        var EncounterDateArray = new Array;
        var CalendarCategoryArray = new Array;
        var EncounterIdArray = new Array;
        var Count = 0;
        <?php
    //Encounter details are stored to javacript as array.
    $result4 = sqlStatement("SELECT fe.encounter,fe.date,openemr_postcalendar_categories.pc_catname FROM form_encounter AS fe " .
            " left join openemr_postcalendar_categories on fe.pc_catid=openemr_postcalendar_categories.pc_catid  WHERE fe.pid = ? order by fe.date desc", array($pid));
    if (sqlNumRows($result4) > 0) {
        while ($rowresult4 = sqlFetchArray($result4)) {
            ?>
        EncounterIdArray[Count] = '<?php echo addslashes($rowresult4['encounter']); ?>';
        EncounterDateArray[Count] =
            '<?php echo addslashes(oeFormatShortDate(date("Y-m-d", strtotime($rowresult4['date'])))); ?>';
        CalendarCategoryArray[Count] = '<?php echo addslashes(xl_appt_category($rowresult4['pc_catname'])); ?>';
        Count++;
        <?php
        }
    }
    ?>
        parent.left_nav.setPatientEncounter(EncounterIdArray, EncounterDateArray, CalendarCategoryArray);
        <?php } // end setting new pid    ?>
        parent.left_nav.syncRadios();
        <?php
if ((isset($_GET['set_pid']) ) && (isset($_GET['set_encounterid'])) && ( intval($_GET['set_encounterid']) > 0 )) {
    $encounter = intval($_GET['set_encounterid']);
    $_SESSION['encounter'] = $encounter;
    $query_result = sqlQuery("SELECT `date` FROM `form_encounter` WHERE `encounter` = ?", array($encounter));
    ?>
        encurl = 'encounter/encounter_top.php?set_encounter=' + <?php echo attr($encounter); ?> + '&pid=' +
            <?php echo attr($pid); ?>;
        <?php if ($GLOBALS['new_tabs_layout']) { ?>
        parent.left_nav.setEncounter(
            '<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($query_result['date'])))); ?>',
            '<?php echo attr($encounter); ?>', 'enc');
        top.restoreSession();
        parent.left_nav.loadFrame('enc2', 'enc', 'patient_file/' + encurl);
        <?php } else { ?>
        var othername = (window.name == 'RTop') ? 'RBot' : 'RTop';
        parent.left_nav.setEncounter(
            '<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($query_result['date'])))); ?>',
            '<?php echo attr($encounter); ?>', othername);
        top.restoreSession();
        parent.frames[othername].location.href = '../' + encurl;
        <?php } ?>
        <?php } // end setting new encounter id (only if new pid is also set)    ?>
    }

    $(window).on('load', function() {
        setMyPatient();
    });
    </script>
    <style type="css/text">
        #pnotes_ps_expand {
                height:auto;
                width:100%;
            }
            
        </style>
    <?php
// This is for layout font size override.
        $grparr = array();
        getLayoutProperties('DEM', $grparr, 'grp_size');
        if (!empty($grparr['']['grp_size'])) {
            $FONTSIZE = $grparr['']['grp_size'];
            ?>
    /* Override font sizes in the theme. */
    #DEM .groupname {
    font-size: <?php echo attr($FONTSIZE); ?>pt;
    }
    #DEM .label {
    font-size: <?php echo attr($FONTSIZE); ?>pt;
    }
    #DEM .data {
    font-size: <?php echo attr($FONTSIZE); ?>pt;
    }
    #DEM .data td {
    font-size: <?php echo attr($FONTSIZE); ?>pt;
    }
    <?php } ?>

    </style>

    <style type="text/css">
    .demographics.BlockChainResponseDiv {
        width: 335px;
    }

    .demographics span.file_trns_content {
        left: 0;
    }

    .data_saved_into_blockchain {
        color: #008000;
        font-weight: 600;
        text-align: center;
        margin-bottom: 0;
    }

    .covid_title {
        font-size: 20px;
        font-weight: bold;
        color: #57c6d2;
        text-decoration: underline;
        margin-top: 50px;
    }
    </style>

</head>

<body class="body_top patient-demographics">
    <input type="hidden" name="g_suite_status" id="g_suite_status" class="form-control"
        value="<?php echo $GLOBALS['g_suite_status']; ?>">
    <input type="hidden" name="g_suite_api_end_point" id="g_suite_api_end_point" class="form-control"
        value="<?php echo $GLOBALS['g_suite_api_end_point']; ?>">
    <div class="card card-apotheka-color" style="margin: 10px 15px;">
        <div class="card-head">
            <header>
                Demographics
            </header>
            <div class="tools">
                <a class="t-collapse btn-color fa fa-chevron-down" href="javascript:;"></a>
            </div>
        </div>
        <div class="card-body">



            <a href='../reminder/active_reminder_popup.php' id='reminder_popup_link' style='display: none;'
                onclick='top.restoreSession()'></a>

            <a href='../birthday_alert/birthday_pop.php?pid=<?php echo attr($pid); ?>&user_id=<?php echo attr($_SESSION['authId']); ?>'
                id='birthday_popup' style='display: none;' onclick='top.restoreSession()'></a>
            <?php
    $thisauth = acl_check('patients', 'demo');
    if ($thisauth) {
        if ($result['squad'] && !acl_check('squads', $result['squad'])) {
            $thisauth = 0;
        }
    }

    if (!$thisauth) {
        echo "<p>(" . htmlspecialchars(xl('Demographics not authorized'), ENT_NOQUOTES) . ")</p>\n";
        echo "</body>\n</html>\n";
        exit();
    }

    if ($thisauth) :
        ?>
            <div class="table-scrollable">
                <table class="table_header table" style="margin-top: 10px;">
                    <?php if(isset($_GET['is_new']) && $GLOBALS['block_chain_status'] == "On") { ?>
                    <tr>
                        <td colspan="5">

                            <div class="BlockChainMessage">
                                <p class="text data_saved_into_blockchain"> Saved to Blockchain <i
                                        class="fa fa-check-circle fa-lg fa-fw"></i> </p>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                    <!-- main row start -->

                    <tr>
                        <td>
                            <span class='title btn dark btn-outline m-b-10'>
                                <?php echo htmlspecialchars(getPatientName($pid), ENT_NOQUOTES); ?>
                            </span>
                        </td>
                        <?php if (acl_check('admin', 'super') && $GLOBALS['allow_pat_delete']) : ?>
                        <td style='margin-top: -6px;' class="delete">
                            <a class='css_button deleter'
                                href='../deleter.php?patient=<?php echo htmlspecialchars($pid, ENT_QUOTES); ?>'
                                onclick='return top.restoreSession()'>
                                <!--<span><?php echo htmlspecialchars(xl('Delete'), ENT_NOQUOTES); ?></span>-->
                            </a>
                        </td>
                        <?php
                endif; // Allow PT delete
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
                if (!($portalUserSetting)) : // Show that the patient has not authorized portal access 
                    ?>
                        <td style='padding-left:1em;'>
                            <?php echo htmlspecialchars(xl('Patient has not authorized the Patient Portal.'), ENT_NOQUOTES); ?>
                        </td>
                        <?php
                endif;
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

                    </tr>
                    <!-- main row end -->

                    <!-- sub row started -->
                    <tr>
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
                            <a class="btn btn-warning"
                                href="<?php echo $web_root ?>/interface/patient_file/consent_form/act_notice_form.php?autoloaded=1&calenc=&cpid=<?php echo $patient_id; ?>"
                                title="ACT Notice Form">ACT Notice</a>
                        </td>
                        <td>
                            <a class="btn btn-danger"
                                href="<?php echo $web_root ?>/interface/patient_file/consent_form/treatment_form.php?autoloaded=1&calenc=&cpid=<?php echo $patient_id; ?>"
                                title="Treatment Form">Treatment Form</a>
                        </td>
                        <td>
                            <a class="btn btn-success"
                                href="<?php echo $web_root ?>/interface/patient_file/consent_form/consent_form.php?autoloaded=1&calenc=&cpid=<?php echo $patient_id; ?>"
                                title="Consent Form">Consent Form</a>
                        </td>
                    </tr>
                    <!-- sun row end -->
                    <!-- act form start -->
                    <tr>
                        <td colspan="6">
                            <form method="POST" action="act_notice_save.php">
                                <!-- <div style="margin: 20px 0px;"> -->
                                <div>
                                    <div
                                        style="display:flex; justify-content: space-between; margin: 0px 30px; padding-bottom: 10px;">
                                        <div>
                                            <h3
                                                style="font-family: 'Roboto', sans-serif; font-size: 25px; letter-spacing: 0px; line-height: 27px; color: #005596; font-weight: bold; margin-bottom: 10px;">
                                                PATIENT ACT NOTICE</h3>
                                            <img src='images/logo.png' style="width: 200px;" />
                                        </div>
                                        <div style="text-align: right;">
                                            <p
                                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                                Genentech-Access.com</p>
                                            <p
                                                style="font-size: 14px; line-height: 1.5; color: #005596; font-family: 'Roboto', sans-serif; font-weight: 500;">
                                                Phone: <span style="color: #000;">(866) 422-2377
                                                </span>&nbsp;&nbsp;
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
                                        <h4 style="font-size: 20px; color: #ffffff;font-weight: 500; margin: 0px;">
                                            Authorization to Use and Disclose Personal Information</h4>
                                    </div>


                                    <div style="margin: 0px 30px;;">
                                        <ul style="padding-left: 25px; margin-bottom: 0px; margin-top: 10px;"
                                            class="x-list">
                                            <li
                                                style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                                Working with my health insurance plan to understand or verify
                                                coverage for Genentech
                                                products
                                            </li>

                                        </ul>
                                    </div>

                                    <div style="margin: 0px 30px; padding-top: 10px;">
                                        <p style="font-size: 14px;  color: #292829;">I understand that Genentech
                                            may also share my personal information
                                            for the purposes described on this authorization with my health care
                                            providers, service providers, and any individual I may
                                            designate as an my payment, enrollment, or eligibility for benefits
                                            on signing this authorization.</p>
                                    </div>

                                    <div style="margin: 0px 30px;;">
                                        <p style="font-size: 14px;  color: #292829; padding-top: 10px;">I also
                                            understand and agree
                                            that:</p>
                                        <ul style="padding-left: 25px; margin-bottom: 0px; margin-top: 5px;"
                                            class="x-list">

                                            <li
                                                style="font-size: 14px; margin-bottom: 5px;color: #000000; display: block;">
                                                I have a right to receive a copy of this authorization </li>
                                        </ul>
                                    </div>
                                    <div
                                        style="margin: 0px 30px; display: flex; height:180px; border-top: 2px solid #005596; border-left: 2px solid #005596; border-right: 2px solid #005596; border-bottom: 2px solid #005596;  margin-bottom: 5px;">
                                        <div style="background-color: #e2e5b7; width: 7%; position: relative;">
                                            <div
                                                style="position: absolute; left: 30%; bottom: 0; transform: rotate(-90deg);  transform-origin: 0 0;color: #005596; font-size: 18px; margin-right: 5px; font-weight: 500;">
                                                <span>REQUIRED</span>
                                            </div>
                                        </div>
                                        <div style="padding: 50px 5px 5px 0px; width: 92%;">
                                            <div style="display: flex;">
                                                <p
                                                    style="position: relative; display: flex; align-self: center; width: 20%;">
                                                    <img src="images/icon6.png"
                                                        style="position: relative; width: 130px; height: 60px;" />
                                                    <span
                                                        style="position: absolute; left: 0; top:10px; color: #ffffff; font-size: 13px;  padding-left: 5px;">Sign
                                                        and <br /> date here</span>
                                                </p>
                                                <div style="width: 80%; margin-left: 5px;">
                                                    <input
                                                        style="width: 96%; height: 25px; background-color: #dee5ff; border-bottom: 1px solid #000000;  border-top: 0px; border-left: 0px;  margin-right: 10px;"
                                                        type="text" name="act_sign" oninput="validateact_sign(this)"
                                                        id="act_sign"
                                                        value="<?php echo isset($act_sign) ? htmlspecialchars($act_sign) : ''; ?>"
                                                        required autocomplete="off">
                                                    <label for="act_sign"
                                                        style="color: #ed1d24;  font-size: 14px;  font-weight: 500; letter-spacing: 0px;margin: 0;  padding: 0;">*Signature
                                                        of Patient/Legally Authorized Representative</label>
                                                    </p>
                                                    <p style="color: #000;font-size: 14px;font-weight: 500;">(A
                                                        parent or guardian must sign for patients under 18 years
                                                        of age)</p>
                                                </div>
                                                <div style="width: 20%;"> <span
                                                        style="display: flex; width: 100%; height: 25px;  border-bottom: 1px solid #000000;">
                                                        <input
                                                            style="height: 22px; background-color: #dee5ff; border:0px; "
                                                            type="text" name="act_date_signed" id="act_date_signed"
                                                            value="<?php echo htmlspecialchars($act_date_signed); ?>"
                                                            required>
                                                    </span>

                                                    <label for="date_signed"
                                                        style="color: #ed1d24; font-size: 14px;  ">*Date Signed
                                                        <span
                                                            style="color: #000; font-size: 14px;  letter-spacing: 0px;">
                                                            (YYYY/MM/DD) </span></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="submit" name="save" value="Save Act Notice"
                                        class="btn btn-primary btn-save">
                                    <input type="submit" name="update" value="Update" style="display:none"
                                        class="btn  btn-info btn-save">
                                    <a href="act_notice_form_pdf.php?generate_pdf=1&patient_id=<?php echo $patient_id; ?>"
                                        class="btn btn-success btn-save">View / Download PDF</a>
                                </div>


                            </form>
                        </td>
                    </tr>
                    <!-- act form end -->
                </table>
            </div>

            <?php
    endif; // $thisauth
    ?>

            <?php
// Get the document ID of the patient ID card if access to it is wanted here.
    $idcard_doc_id = false;
    if ($GLOBALS['patient_id_category_name']) {
        $idcard_doc_id = get_document_by_catg($pid, $GLOBALS['patient_id_category_name']);
    }

// Collect the patient menu then build it
    $menuPatient = new PatientMenuRole();
    $menu_restrictions = $menuPatient->getMenu();
    ?>




            <script language='JavaScript'>
            // Array of skip conditions for the checkSkipConditions() function.
            var skipArray = [
                <?php echo $condition_str; ?>
            ];
            checkSkipConditions();
            </script>
            <script src="<?php echo $web_root; ?>/interface/main/tabs/js/tabs_view_model.js" type="text/javascript">
            </script>

</body>

<script type="text/javascript">
$('.PharmacyTab').click(function() {
    var id_name = $(this).attr('data-id');
    $('.PharmacyDetails').hide();
    $('#' + id_name).show();
});
</script>

</html>

<?php if(isset($_GET['new_one'])) { exit(); } ?>
<script>
function validateact_sign(input) {
    // Remove any non-alphabet characters except space
    input.value = input.value.replace(/[^A-Za-z\s]/g, '');

    // Remove extra spaces (more than one space in a row)
    input.value = input.value.replace(/\s{2,}/g, ' ');
}


flatpickr("#act_date_signed", {
    dateFormat: "Y-m-d", // Customize the date format as needed
});
</script>