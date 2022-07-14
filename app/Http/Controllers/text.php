
<?php

// CREDIT CANADA WEBSERVICE
// This page used by Credit canada website
// Operation of adding comments ,updating leads and contacts client information
// Sends Error Email to IT
include('constant.inc');
include(ROOT_PATH . 'controller/classes/crm_webservice.php');
include(ROOT_PATH . 'controller/auth.php');
include(ROOT_PATH . 'controller/classes/query.php');
include(ROOT_PATH . 'controller/PHPMailer/PHPMailerAutoload.php');


// Get the url and sessionid from auth.php
// create instance for class
$qryobj = new crm_crudoperation($endpointUrl, $sessionid);

// Create instance for email class
$mailObj = new PHPMailer;

// Post Varibles From WEbSITE  http://checkout.creditcanada.net/view/
$product = $_POST['product'];
$setup_fee = $_POST['setup_fee'];
$date = date('Y-m-d');
$Agent = $_POST['Agent'];
$promo = $_POST['promo_code'];
$Province = $_POST['Province_Territory'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$city = $_POST['City'];
$code = $_POST['postal'];
$address2 = $_POST['street_number'];
$address1 = $_POST['street_name'];
$ip = $_POST['ip'];
$CAT_CARD = $_POST['CAT_CARD'];
$CommentsData = $_POST['CommentsData'];
$phone = $_POST['phone'];
$mobile = $_POST['mobile'];
$MattressSize = $_POST['mattress_size'];
$selectedMattress = $_POST['selected_mattress'];
$cf_contacts_inhouseapp = $_POST['cf_contacts_inhouseapp'];
$mattress_bool = $_POST['mattress_bool'];

$websiteVal = ((isset($_POST['web_source']) && $_POST['web_source'] != '') ? $_POST['web_source'] : 'creditcanada.net');

$addressInfo = "";
//$assigned_user_id="";
// Object instatnce to pass parameter
$obj = new creditcanadalead($endpointUrl, $sessionid, $product, $setup_fee, $Agent, $promo, $Province, $firstname, $lastname, $email, $city, $code, $address2, $address1, $ip, $date, $CAT_CARD, $CommentsData, $phone, $mobile, $MattressSize, $selectedMattress, $websiteVal, $cf_contacts_inhouseapp, $mattress_bool);

class creditcanadalead
{  // Class start
    // Constructor for variable declaration

    function __construct($endpointUrl, $sessionid, $product, $setup_fee, $Agent, $promo, $Province, $firstname, $lastname, $email, $city, $code, $address2, $address1, $ip, $date, $CAT_CARD, $CommentsData, $phone, $mobile, $MattressSize, $selectedMattress, $websiteVal, $cf_contacts_inhouseapp, $mattress_bool)
    {
        //Access the outside object variable
        global $qryobj;  // CRM Class object
        global $mailObj; // MAIL class object

        $alldata = array();
        $this->qryobj = $qryobj;
        $this->mailObj = $mailObj;
        $this->product = $product;
        $this->setup_fee = $setup_fee;
        $this->Agent = $Agent;
        $this->promo = $promo;
        $this->Province = $Province;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->city = $city;
        $this->code = $code;
        $this->address2 = $address2;
        $this->address1 = $address1;
        $this->ip = $ip;
        $this->addressInfo = $this->address2 . " " . $this->address1;
        //$this->assigned_user_id="20x2";
        $this->date = $date;
        $this->CAT_CARD = $CAT_CARD;
        $this->phone = $phone;
        $this->mobile = $mobile;
        $this->CommentsData = str_replace("~", " ", $CommentsData);
        $this->MattressSize = $MattressSize;
        $this->selectedMattress = $selectedMattress;
        $this->websiteVal = $websiteVal;
        $this->cf_Inhouseapp = $cf_contacts_inhouseapp;
        $this->mattress_bool = $mattress_bool;


        $comment_data = explode("~", $CommentsData);
        foreach ($comment_data as $datavalue) {

            $arrindex = explode(":", $datavalue);
            $key = trim($arrindex[0]);
            $value = $arrindex[1];
            $alldata[$key] = $value;
        }
        $this->AllData = $alldata;


        // Sequence of function Execution Starts From here
        // Call Contact update function : with Loop count as 1
        // Search the client  In CRM Contacts.
        $this->contactsUpdate(1);
    }

    // Search Lead in Leads module
    // If exist update the Leadstatus and Call contactupdate function
    function leadupdate($loop)
    {

        $first_name = $this->firstname;
        $last_name = $this->lastname;
        $email = $this->email;
        $cat_option = strtolower($this->CAT_CARD);
        if ($cat_option == 'yes') {
            $cat_option = true;
        } else {
            $cat_option = fasle;
        }

        $mattress_option = strtolower($this->mattress_bool);
        if ($mattress_option == 'yes') {
            $mattress_option = true;
        } else {
            $mattress_option = fasle;
        }

        if (strpos($last_name, "'") !== false) {
            $last_name = str_replace("'", "''", $last_name);
        }

        $query = "select * from Leads where lastname='$last_name' and email='$email';";
        $queryresult = $this->qryobj->crm_query($query);

        if ($queryresult == "Noresult") {

            if ($loop == 1) {

                $this->createLead($loop);
            } else { // Loop count as more than 2
                // Call Email Function
                $this->sendEmail("Can not able to find credit canada client in Leads. - webservice function name : leadupdate");
            }
        } else {

            $retrievedObjects = $queryresult['result'][0];
            if (count($queryresult['result']) > 1) {
                $crmInsert = 1;
                foreach ($queryresult['result'] as $updateArr) {

                    $assignedUserid = explode('x', $updateArr['assigned_user_id']);
                    $groupArray = array(2, 3, 4, 6, 138);

                    if ($updateArr['leadstatus'] !== 'Bad Information - Junk' || $updateArr['leadstatus'] !== 'Bad Information - Duplicate') {

                        if (!in_array($assignedUserid[1], $groupArray)) {
                            $retrievedObjects = $updateArr;
                            $crmInsert = 2;
                            break;
                        }
                    }
                }   // foreach end
            }


            $assigned_user = $this->getAssignTo();
            $leadid = $retrievedObjects['id'];

            $id = explode('x', $leadid);
            echo $id[1];

            // Adding comments to Leads
            $createcommentData = array("commentcontent" => $this->CommentsData, "related_to" => $leadid,
                "assigned_user_id" => $assigned_user);
            // Call add comments function from CRM class
            $commentsDetails = $this->qryobj->crm_comments_create($createcommentData);

            if ($commentsDetails != "success") { // Comments not added
                // Call Email Function to send Error Email to IT
                $this->sendEmail("Comments not Added To the Contacts !");
            }
            $productval = $this->product;
            if ($this->product == "Premium CreditAdvise") {
                $productval = "CreditAdvise- Premium";
            }


            // Update Lead function :
            $updated_value = array(
                "leadstatus" => "Application Received", "cf_1167" => $cat_option, "lane" => $this->addressInfo,
                "city" => $this->city, "cf_799" => $this->Province,
                "code" => $this->code,
                "website" => $this->websiteVal,
                "cf_1726" => $productval,
                "cf_1728" => $this->setup_fee,
                "cf_leads_mattresssize" => $this->MattressSize,
                "cf_leads_mattress" => $this->selectedMattress,
                'cf_leads_inhouseapp' => $this->cf_Inhouseapp,
                'cf_leads_mattressadded' => $mattress_option, 'cf_809' => $this->ip);

            foreach ($updated_value as $key => $value) {
                $retrievedObjects[$key] = $value;
            }

            // Call CRM class function to perform update operation
            $updateDetails = $this->qryobj->crm_update_operation($retrievedObjects);

            if (empty($updateDetails['result'])) {
                /* if($loop == 1){
                  $this->leadupdate(2);

                  }else{ //Loop count as more than 2
                  // Call Email Function
                  $this->sendEmail("Error While Updating credit canada client information in Leads. - webservice function name : leadupdate - Werservice Error : ".$updateDetails['error']['message']);
                  } */
                $this->sendEmail("Error While Updating credit canada client information in Leads. - webservice function name : leadupdate - Werservice Error : " . $updateDetails['error']['message']);
            } else {
                $logoutDetails = $this->qryobj->crm_logout();
            }
        }
    }

// update lead Function End
    // Create new entry in Lead module
    // Search user in users module
    function createLead($loop)
    {  // Create new lead function Start
        //$websiteVal = "creditcanada.net";
        // Get Assigned User ID
        $assigned_user = $this->getAssignTo();

        //Create New entry Array variables
        $createData = array("firstname" => $this->firstname, "lastname" => $this->lastname,
            "email" => $this->email, "cf_799" => $this->Province, "city" => $this->city,
            "code" => $this->code, "website" => $this->websiteVal, "lane" => $this->addressInfo,
            "assigned_user_id" => $assigned_user, "phone" => $this->phone, "mobile" => $this->mobile);

        //Create New entry In Leads module : Call  crm_create function from CRM class
        $createDetails = $this->qryobj->crm_create($createData);

        if (!empty($createDetails['result'])) {
            // After the lead was created : Call Lead update with loop count as 2
            $this->leadupdate(2);
        } else {
            // Call Email Function From Mail Class
            $this->sendEmail("Can not able to create credit canada client entry in Leads. - webservice function name : createLead - Webservice Error: " . $createDetails['error']['message']);
        }
    }

// Create new lead function End
    // Search client in Contacts module
    // If client exist in Contact update their Information
    // If Client doesn't exist go to leadupdate function to search in lead
    function contactsUpdate($loop)
    {   // Contacts update function Start
        $first_name = $this->firstname;
        $last_name = $this->lastname;
        $email = $this->email;
        $prodVal = $this->product;
        $setup_fee = $this->setup_fee;
        $appDate = $this->date;
        $emailAddr = $this->email;
        $Province = $this->Province;
        $city = $this->city;
        $postalcode = $this->code;
        $streetinfo = $this->addressInfo;
        $catCond = $this->CAT_CARD;
        $phone_no = $this->phone;
        $mobile_no = $this->mobile;
        $mattress_size = $this->MattressSize;
        $selected_mattress = $this->selectedMattress;
        $websiteVal = $this->websiteVal;
        $mattress_bool = $this->mattress_bool;

        if (strpos($last_name, "'") !== false) {
            $last_name = str_replace("'", "''", $last_name);
        }
        /*
                switch ($selected_mattress) {
                    case "KING" :
                        $selected_mattress_type= 'King size mattress';
                        break;
                    case "QUEEN":
                        $selected_mattress_type= 'Queen size mattress';
                        break;
                    case "FULL":
                        $selected_mattress_type= 'Full size mattress';
                        break;
                    case "TWIN":
                        $selected_mattress_type= 'Twin size mattress';
                        break;
                    case "KING-WITH-FRAME" :
                        $selected_mattress_type= 'King size mattress with frame';
                        break;
                    case "QUEEN-WITH-FRAME":
                        $selected_mattress_type= 'Queen size mattress with frame';
                        break;
                    case "FULL-WITH-FRAME":
                        $selected_mattress_type= 'Full size mattress with frame';
                        break;
                    case "TWIN-WITH-FRAME":
                        $selected_mattress_type= 'Twin size mattress with frame';
                        break;
                    case "KING-FRAME":
                        $selected_mattress_type= 'King size frame';
                        break;
                    case "QUEEN-FRAME":
                        $selected_mattress_type= 'Queen size frame';
                        break;
                    case "FULL-FRAME":
                        $selected_mattress_type= 'Full size frame';
                        break;
                    case "TWIN-FRAME":
                        $selected_mattress_type= 'Twin size frame';
                        break;
                    default :
                        $selected_mattress_type= 'Mattress';
                        break;
                }
                */

        // Get Assigned User ID
        $assigned_user = $this->getAssignTo();

        $query = "select * from Contacts where lastname='$last_name' and email='$email';";

        $updated_value = array("cf_1003" => $setup_fee, "cf_977" => $prodVal, "cf_981" => $appDate,
            "mailingstreet" => $streetinfo, "mailingcity" => $city, "mailingzip" => $postalcode, "cf_859" => $Province,
            "birthday" => $this->AllData['DOB'], "cf_1203" => $this->AllData['Employer Length'],
            "mailingpobox" => $this->AllData['PO Box'], "cf_893" => $this->AllData['Rent Or Own'], "cf_823" => $this->AllData['Monthly Income'],
            "cf_825" => $this->AllData['Employer Name'], "cf_827" => $this->AllData['Rent Mortgage Payment'], "cf_1205" => $this->AllData['Time At Address'],
            "assigned_user_id" => $assigned_user, "contacttype" => "Lead", "cf_contacts_mattresssize" => $mattress_size, "cf_contacts_mattressselected" => $selected_mattress, 'cf_contacts_website' => $websiteVal, 'cf_869' => $this->ip);

        if (!empty($phone_no)) {
            $updated_value["homephone"] = $phone_no;
        }

        if (!empty($mobile_no)) {
            $updated_value["mobile"] = $mobile_no;
        }

        // CRM chebox condition
        if ($prodVal == '2 In 1 Credit Transformer') {
            $updated_value["cf_1189"] = true;
        } elseif ($prodVal == 'Premium CreditAdvise') {
            $updated_value["cf_1191"] = true;
        }
        if (strtolower($catCond) == "yes") {

            $updated_value["cf_1195"] = true;
            $updated_value["cf_1173"] = true;
            $updated_value["cf_1187"] = $appDate;
        }

        //new implementation of mattress checkbox selection in CRM
        if (strtolower($mattress_bool) == "yes") {
            $updated_value["cf_contacts_mattressadded"] = true;
        }

        // Call CRM class function to perform contact update operation
        $updateDetails = $this->qryobj->crm_contact_product_update($query, $updated_value);
        if ($updateDetails == "NoResult") { // client doesn't exist in contacts

            /* if($loop == 1){  // Loop count as 1
              // Call leadupdate function again with loop count as 2
              $this->leadupdate($loop);

              }else{  // Loop count as more than 1
              // Call Email Function to send Error Email to IT
              $this->sendEmail("Can not able to find the credit canada client in contacts. - webservice function name : contactsUpdate");
              } */
            $this->leadupdate($loop);
        } elseif ($updateDetails == "Error") { // Error while Update contact info
            /* if($loop == 1){ // Loop count as 1
              // Call contactupdate function again with loop count as 2
              $this->contactsUpdate(2);

              }else{
              // Call Email Function to send Error Email to IT
              $this->sendEmail("Webservice error while updating credit canada client's information in contacts. - webservice function name : contactsUpdate ");
              } */
            $this->sendEmail("Webservice error while updating credit canada client's information in contacts. - webservice function name : contactsUpdate ($updateDetails == 'Error') (httpdocs/webservice_2017/CANADA_CREDIT_webservice_2017.php) ");
        } else { // Good : Client exist and get their auto increment id
            $contact_id = $updateDetails['result']['id'];
            $id = explode('x', $updateDetails['result']['id']);
            echo $id[1];

            // Adding comments to contacts
            $createcommentData = array("commentcontent" => $this->CommentsData, "related_to" => $contact_id,
                "assigned_user_id" => $assigned_user);
            $commentsDetails = $this->qryobj->crm_comments_create($createcommentData);

            if ($commentsDetails != "success") { // Comments not added
                // Call Email Function to send Error Email to IT
                $this->sendEmail("Webservice error while adding  credit canada client comments to Contacts . - webservice function name : contactsUpdate ($commendsDetails != 'success') (httpdocs/webservice_2017/CANADA_CREDIT_webservice_2017.php)");
            } else {
                // logout the session
                $logoutDetails = $this->qryobj->crm_logout();
            }
        }
    }

// Contacts update function End
    // Function to return assignto user ID
    function getAssignTo()
    {

        $assignedTo = "20x2";
        $userlogin = $this->Agent . "@creditline.net";
        //$userlogin="wael@canadacreditfix.com";
        // Get agent/user ID from users table
        $userquery = "SELECT * FROM Users where user_name='$userlogin';";
        $searchDetails = $this->qryobj->crm_Query($userquery);

        if (!empty($searchDetails['result'])) {
            $crmuserArr = explode("x", $searchDetails['result']['0']['id']);
            $assignedTo = "19x" . $crmuserArr[1];
        }

        return $assignedTo;
    }

    function sendEmail($msg)
    {  // Email function Start
        $this->mailObj->CharSet = "UTF-8";
        $this->mailObj->isSMTP();                                      // Set mailer to use SMTP
        $this->mailObj->Host = 'smtp.1and1.com';  // Specify main and backup SMTP servers
        $this->mailObj->SMTPAuth = true;                               // Enable SMTP authentication
        $this->mailObj->Username = 'outgoing@creditcanada.net';    // SMTP username
        $this->mailObj->Password = 'Netsuite123!';                           // SMTP password
        $this->mailObj->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
        $this->mailObj->Port = 25;
        $this->mailObj->Subject = " New CRM Webservice Error - Credit Canada Application ";
        // TCP port to connect to
        $this->mailObj->setFrom("nirmala-a@creditline.net", 'Error');
        $msgBody = "";
        $msgBody .= '<STRONG style="color:#F00">New CRM Webservice Error : Credit Canada Client Application</STRONG><br>';
        $msgBody .= '<font > ' . $msg . ' </font><br>';
        $msgBody .= '<STRONG><u>Client Information:</u></STRONG><table>';
        $msgBody .= "<tr><td><span><b>date: </b></span></td><td> <font color='#0000CC'>" . $this->date . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>First Name: </b></span></td><td> <font color='#0000CC'>" . $this->firstname . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Last Name: </b></span></td><td> <font color='#0000CC'>" . $this->lastname . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Email: </b></span></td><td> <font color='#0000CC'>" . $this->email . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Phone: </b></span></td><td> <font color='#0000CC'>" . $this->phone . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Mobile: </b></span></td><td> <font color='#0000CC'>" . $this->mobile . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Address: </b></span></td><td> <font color='#0000CC'>" . $this->addressInfo . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>City: </b></span></td><td> <font color='#0000CC'>" . $this->city . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Province: </b></span></td><td> <font color='#0000CC'>" . $this->Province . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Postal Code: </b></span></td><td> <font color='#0000CC'>" . $_POST['postal'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Promo: </b></span></td><td> <font color='#0000CC'>" . $this->promo . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Setup Fee: </b></span></td><td> <font color='#0000CC'>" . $this->setup_fee . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Product: </b></span></td><td> <font color='#0000CC'>" . $this->product . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>CAT Option: </b></span></td><td> <font color='#0000CC'>" . $this->CAT_CARD . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Agent: </b></span></td><td> <font color='#0000CC'>" . $this->Agent . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>IP Address: </b></span></td><td> <font color='#0000CC'>" . $this->ip . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b></b></span></td><td> <font color='#0000CC'></font></td></tr>";
        $msgBody .= "<tr><td><span><b>Webservice Location: </b></span></td><td> <font color='#0000CC'> CREDIT CANADA (CANADA_CREDIT_Webservice_2017.php)</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Error Msg: </b></span></td><td> <font color='#F00'>" . $msg . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b></b></span></td><td> <font color='#0000CC'></font></td></tr>";
        $msgBody .= "<tr><td><span><b>Comments</b>: </b></span></td><td> <font color='#0000CC'>" . $this->CommentsData . "</font></td></tr>";
        $msgBody .= "</table>";
        $this->mailObj->isHTML(true);
        $this->mailObj->Body = $msgBody;
        $this->mailObj->addAddress('it@creditcanada.net');

        $this->mailObj->send();

        // Logout function call
        $logoutDetails = $this->qryobj->crm_logout();
    }

//Email function End
}

// Class End
?>
