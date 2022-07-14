<?php
namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class LoanController extends Controller
{
    public function exportToExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', [1, 2, 3]);

        $writer = new Xlsx($spreadsheet);
        $writer->save('hello world.xlsx');
    }

    public function convertHtmlToPdf()
    {
        $pdf = Pdf::loadView('pdf.contract-working')->setPaper('a4', 'portrait');

        $this->sendEmail([], $pdf);
    }

    public function leadCapture()
    {
        if (isset($_POST['product_name']) && $_POST['product_name'] != '') {
            $selectedLaptop = $_POST['product_name'];
            $mattress_bool = "yes";
        } else {
            $selectedLaptop = "No Laptop";
            $mattress_bool = "no";
        }

        $crmupdateresult = "";
        $selectedLaptop = $_POST['product_name'];
        $commentsdata = "
        ~Agent Name: " . $_POST['agent'] . "
        ~Product Details: " . $_POST['product_details'] . "
        ~Gender: " . $_POST['gender'] . "
        ~Address: " . $_POST['address'] . "
        ~City: " . $_POST['city'] . "
        ~Province: " . $_POST['province'] . "
        ~Postal: " . $_POST['postal'] . "
        ~PO Box: " . $_POST['po_box'] . "
        ~Time At Address: " . $_POST['time_at_address'] . "
        ~DOB: " . $_POST['DOB'] . "
        ~Citizen: " . $_POST['citizen'] . "
        ~Monthly Income: " . $_POST['income'] . "
        ~Occupation: " . $_POST['occupation'] . "
        ~Employer Name: " . $_POST['emp_name'] . "
        ~Employer Phone: " . $_POST['emp_phone'] . "
        ~Employer Address: " . $_POST['emp_address'] . "
        ~Employer City: " . $_POST['emp_city'] . "
        ~Employer Province: " . $_POST['emp_province'] . "
        ~Employer Postal: " . $_POST['emp_postal'] . "
        ~Employer Length: " . $_POST['emp_time'];

        $data = array(
            'product' => "Omni Slice - Canada",
            'Agent' => $_POST['agent'],
            'Province_Territory' => $_POST['province'],
            'firstname' => $_POST['first_name'],
            'lastname' => $_POST['last_name'],
            'email' => $_POST['email'],
            'City' => $_POST['city'],
            'postal' => $_POST['postal'],
            'street_number' => $_POST['address'],
            'street_name' => $_POST['laneTwo'],
            'ip' => $_POST['systemIP'],
            'phone' => $_POST['phone'],
            'mobile' => $_POST['mobile'],
            'mattress_size' => $_POST['mattress_size'],
            'selected_mattress' => $selectedLaptop,
            'CommentsData' => $commentsdata,
            'web_source' => $_POST['web_source'],
            'description' => $_POST['product_details'],
            'mattress_bool' => $mattress_bool,
        );

        file_put_contents('datao.txt', var_export($data, true));

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );

        $context = stream_context_create($options);
        $url2 = 'https://mattressomni.ca/webservice_2017/view/CANADA_CREDIT_webservice_2017.php';
        $result_2017 = file_get_contents($url2, false, $context);

        $crmupdateresult = "<a href='https://canadacredit.od2.vtiger.com?module=Contacts&view=Detail&record=" . $result_2017 . "&app=MARKETING' target='_BLANK'>LINK TO CRM IN CONTACTS </a> </br>";
        $crmupdateresult .= "<a href='https://canadacredit.od2.vtiger.com?module=Leads&view=Detail&record=" . $result_2017 . "&app=MARKETING' target='_BLANK'>LINK TO CRM IN LEADS</a>";

        return $crmupdateresult;
    }

    public function sendEmail($payload, $attachment)
    {
        $mail = new PHPMailer(true);
        $client = $payload; // session variable or pass as an argument

        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host       = 'smtp.1and1.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'outgoing@creditcanada.net';
            $mail->Password   = 'Netsuite123!';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('info@mattressomni.ca', 'Omni Slice');
            $mail->addAddress('ivandejesus6363@gmail.com', 'Ivan De Jesus');
            $mail->AddStringAttachment($attachment->output(), 'aggreement.pdf');
            $mail->isHTML(true);
            $mail->Subject = 'Omni Slice Sales Agreement';

            $msgBody = "<table><tr>";
            $msgBody = "<td>";
            $msgBody = "";
            $msgBody .= "<table>";
            $msgBody .= '<STRONG><u>Product Information:</u></STRONG><table>';
            $msgBody .= "<tr><td><span><b>agent_name: </b></span></td><td> <font color='#0000CC'>" . $client['agent'] . "</font></td></tr>";
            $msgBody .= $client['product_details'] ;
            $msgBody .= '</table></td>';
            $msgBody .= '<STRONG><u>Banking Information:</u></STRONG><table>';

            $msgBody .= "<tr><td><span><b>Financial_Institution_Name: </b></span></td><td> <font color='#0000CC'>" . $client['bank_name'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Branch_Address: </b></span></td><td> <font color='#0000CC'>" . $client['branch_address'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Institution_Number: </b></span></td><td> <font color='#0000CC'>" . $client['institution_number'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Transit_Number: </b></span></td><td> <font color='#0000CC'>" . $client['transit_number'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Account_Number: </b></span></td><td> <font color='#0000CC'>" . $client['account_number'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Payment Type: </b></span></td><td> <font color='#ff0000' size='4'><strong>" . $client['payment_type'] . "</strong></font></td></tr></table>";
            $msgBody .= '</table></td>';

            $msgBody .= '<STRONG><u>Client Information:</u></STRONG>';
            $msgBody .= '<table>';
            $msgBody .= "<tr><td><span><b>first_name: </b></span></td><td> <font color='#0000CC'>" . $client['first_name'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>middle_name: </b></span></td><td> <font color='#0000CC'>" . $client['middle_name'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>last_name: </b></span></td><td> <font color='#0000CC'>" . $client['last_name'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>email: </b></span></td><td> <font color='#0000CC'>" . $client['email'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Phone: </b>   </span></td><td> <font color='#0000CC'>" . $client['mobile'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>gender: </b></span></td><td> <font color='#0000CC'>" . $client['gender'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>dob: </b></span></td><td> <font color='#0000CC'>" . $client['DOB'] . "</font></td></tr>";

            $msgBody .= "<tr><td><span><b>Address: </b></span></td><td> <font color='#0000CC'>" . $client['address'] . " - " . $client['unt'] . " " . $client['laneTwo'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>City: </b></span></td><td> <font color='#0000CC'>" . $client['city'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Province_Territory: </b></span></td><td> <font color='#0000CC'>" . $client['province'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>postal: </b></span></td><td> <font color='#0000CC'>" . $client['postal'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>PO Box: </b></span></td><td> <font color='#0000CC'>" . $client['po_box'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Time_at_address: </b></span></td><td> <font color='#0000CC'>" . $client['time_at_address'] . "</font></td></tr>";

            $msgBody .= "<tr><td><span><b>citizenship: </b></span></td><td> <font color='#0000CC'>" . $client['citizen'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>sin: </b></span></td><td> <font color='#0000CC'>" . $client['sin'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Monthly_Income: </b></span></td><td> <font color='#0000CC'>" . $client['income'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_name: </b></span></td><td> <font color='#0000CC'>" . $client['emp_name'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>job_title: </b></span></td><td> <font color='#0000CC'>" . $client['OCCUP_NAME'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_phone: </b></span></td><td> <font color='#0000CC'>" . $client['emp_phone'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_addy: </b></span></td><td> <font color='#0000CC'>" . $client['emp_address'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_city: </b></span></td><td> <font color='#0000CC'>" . $client['emp_city'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_Province_Territory: </b></span></td><td> <font color='#0000CC'>" . $client['emp_province'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_postal: </b></span></td><td> <font color='#0000CC'>" . $client['emp_postal'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Employer_lenght: </b></span></td><td> <font color='#0000CC'>" . $client['emp_time'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Credit Ratings: </b></span></td><td> <font color='#0000CC'>" . $client['rate_your_credit'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>Savings: </b></span></td><td> <font color='#0000CC'>" . $client['savings'] . "</font></td></tr>";
            $msgBody .= "<tr><td><span><b>terms: </b></span></td><td> <font color='#0000CC'>" . $client['terms'] . "</font></td></tr>";
            $msgBody .= '</table>';
            $msgBody .= "<br><br><br><br><table><tr><td><span><b>Ip form completed at: </b></span></td><td> <font color='#0000CC'>" . $ip . "</font></td></tr></table>";
            $msgBody .= "</table></td></tr></table>";

            $mail->Body = $msgBody;
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    public function sendEmailMattressOmni($client, $dupcheck, $from_office_warning, $crm_update_result, $files_locations)
    {
        $browser = new Browser();
        $ip = getRealIpAddr();
        if ($dupcheck == -1)
            $crm_update_result = "Customer doesn't exist in CRM";
        !empty($client['promo_code']) ? $promo = $client['promo_code'] . " ($" . $client['promo_value'] . ")" : $promo = "";
        $full_name = $client['full_name'];
    
        //DEAL COUNTER mail
        if (!strpos($client['email'], '@canadacreditfix.com')) {
            $dealcount = new PHPMailer;
            $dealcount->CharSet = "UTF-8";
            $dealcount->isSMTP();                                // Set mailer to use SMTP
            $dealcount->Host = 'smtp.1and1.com';                 // Specify main and backup SMTP servers
            $dealcount->SMTPAuth = true;                         // Enable SMTP authentication
            $dealcount->Username = 'outgoing@creditcanada.net';    // SMTP username
            $dealcount->Password = 'Netsuite123!';                           // SMTP password
            $dealcount->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
            $dealcount->Port = 587;                                    // TCP port to connect to
            $dealcount->setFrom('info@mattressomni.ca', 'mattressomni.ca');
            $dealcount->isHTML(true);
            $dealcount->Subject = "O Mattress Canada " . $full_name . " From Agent: " . $client['agent'];
            if ($from_office_warning != "") {
                $msgBody = '<STRONG style="color:#F00">' . $from_office_warning . '</STRONG></br>';
                $msgBody .= 'Mattress Selected : ' . $client['product_details'] . "</br>";
                $msgBody .= 'First name ' . $client['first_name'] . "</br>";
                $msgBody .= 'Last name ' . $client['last_name'] . "</br>";
            } else {
                $msgBody .= 'Mattress Selected : ' . $client['product_details'] . "</br>";
                $msgBody .= 'First name ' . $client['first_name'] . "</br>";
                $msgBody .= 'Last name ' . $client['last_name'] . "</br>";
            }
            $dealcount->Body = $msgBody;
            $dealcount->addAddress("dealcount@creditslab.com");
            //$dealcount->addBCC("sheldon@creditcanada.net");
            $dealcount->addBCC("cliff-dc@furnitureomni.com");
            //$dealcount->addBCC("edump@furniture7.com");
            //$dealcount->addBCC("it@furnitureomni.com");
            if (!$dealcount->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $dealcount->ErrorInfo;
            }
        }
        //END deal counter
        //INTERNAL Email
        $internalmail = new PHPMailer;
        $internalmail->CharSet = "UTF-8";
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        //$pop = POP3::popBeforeSmtp('pop.1and1.com', 995, 'wael@canadacreditfix.com', 'hardline', 1);
        $internalmail->isSMTP();                                      // Set mailer to use SMTP
        $internalmail->Host = 'smtp.1and1.com';  // Specify main and backup SMTP servers
        $internalmail->SMTPAuth = true;                               // Enable SMTP authentication
        $internalmail->Username = 'outgoing@creditcanada.net';    // SMTP username
        $internalmail->Password = 'Netsuite123!';                           // SMTP password
        $internalmail->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
        $internalmail->Port = 587;                                    // TCP port to connect to
        $internalmail->setFrom('info@mattressomni.ca', 'mattressomni.ca');
        foreach ($files_locations as $file) {                     //attach files to email
            $internalmail->addAttachment($file);
        }
    
        $msgBody = "<table><tr>";
        $msgBody = "<td>";
        $msgBody = "";
        $msgBody .= '<STRONG style="color:#F00">' . $crm_update_result . '</STRONG><br>';
        $msgBody .= '<STRONG style="color:#F00">' . $from_office_warning . '</STRONG>';
        $msgBody .= "<table>";
        $msgBody .= '<STRONG><u>Product Information:</u></STRONG><table>';
        $msgBody .= "<tr><td><span><b>agent_name: </b></span></td><td> <font color='#0000CC'>" . $client['agent'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>leadid: </b></span></td><td> <font color='#0000CC'>" . $crm_update_result . "</font></td></tr>";
        $msgBody .= $client['product_details'] ;
    
    
        $msgBody .= '</table></td>';
        $msgBody .= '<STRONG><u>Banking Information:</u></STRONG><table>';
    
        $msgBody .= "<tr><td><span><b>Financial_Institution_Name: </b></span></td><td> <font color='#0000CC'>" . $client['bank_name'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Branch_Address: </b></span></td><td> <font color='#0000CC'>" . $client['branch_address'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Institution_Number: </b></span></td><td> <font color='#0000CC'>" . $client['institution_number'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Transit_Number: </b></span></td><td> <font color='#0000CC'>" . $client['transit_number'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Account_Number: </b></span></td><td> <font color='#0000CC'>" . $client['account_number'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Payment Type: </b></span></td><td> <font color='#ff0000' size='4'><strong>" . $client['payment_type'] . "</strong></font></td></tr></table>";
        $msgBody .= '</table></td>';
    
        $msgBody .= '<STRONG><u>Client Information:</u></STRONG>';
        $msgBody .= '<table>';
        $msgBody .= "<tr><td><span><b>first_name: </b></span></td><td> <font color='#0000CC'>" . $client['first_name'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>middle_name: </b></span></td><td> <font color='#0000CC'>" . $client['middle_name'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>last_name: </b></span></td><td> <font color='#0000CC'>" . $client['last_name'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>email: </b></span></td><td> <font color='#0000CC'>" . $client['email'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Phone: </b></span></td><td> <font color='#0000CC'>" . $client['mobile'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>gender: </b></span></td><td> <font color='#0000CC'>" . $client['gender'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>dob: </b></span></td><td> <font color='#0000CC'>" . $client['DOB'] . "</font></td></tr>";
    
        $msgBody .= "<tr><td><span><b>Address: </b></span></td><td> <font color='#0000CC'>" . $client['address'] . " - " . $client['unt'] . " " . $client['laneTwo'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>City: </b></span></td><td> <font color='#0000CC'>" . $client['city'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Province_Territory: </b></span></td><td> <font color='#0000CC'>" . $client['province'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>postal: </b></span></td><td> <font color='#0000CC'>" . $client['postal'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>PO Box: </b></span></td><td> <font color='#0000CC'>" . $client['po_box'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Time_at_address: </b></span></td><td> <font color='#0000CC'>" . $client['time_at_address'] . "</font></td></tr>";
    
        $msgBody .= "<tr><td><span><b>citizenship: </b></span></td><td> <font color='#0000CC'>" . $client['citizen'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>sin: </b></span></td><td> <font color='#0000CC'>" . $client['sin'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Monthly_Income: </b></span></td><td> <font color='#0000CC'>" . $client['income'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_name: </b></span></td><td> <font color='#0000CC'>" . $client['emp_name'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>job_title: </b></span></td><td> <font color='#0000CC'>" . $client['OCCUP_NAME'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_phone: </b></span></td><td> <font color='#0000CC'>" . $client['emp_phone'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_addy: </b></span></td><td> <font color='#0000CC'>" . $client['emp_address'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_city: </b></span></td><td> <font color='#0000CC'>" . $client['emp_city'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_Province_Territory: </b></span></td><td> <font color='#0000CC'>" . $client['emp_province'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_postal: </b></span></td><td> <font color='#0000CC'>" . $client['emp_postal'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Employer_lenght: </b></span></td><td> <font color='#0000CC'>" . $client['emp_time'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Credit Ratings: </b></span></td><td> <font color='#0000CC'>" . $client['rate_your_credit'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>Savings: </b></span></td><td> <font color='#0000CC'>" . $client['savings'] . "</font></td></tr>";
        $msgBody .= "<tr><td><span><b>terms: </b></span></td><td> <font color='#0000CC'>" . $client['terms'] . "</font></td></tr>";
        $msgBody .= '</table>';
        $msgBody .= "<br><br><br><br>" . $crm_update_result;
        $msgBody .= "<br><br><br><br><table><tr><td><span><b>Ip form completed at: </b></span></td><td> <font color='#0000CC'>" . $ip . "</font></td></tr></table>";
        $msgBody .= "<br><table border='0'><tr><td><span><b>Browser Type: </b></span></td><td> <font color='#0000CC'>" . $browser->getBrowser() . "</font></td></tr></table>";
        $msgBody .= "<br><table border='0'><tr><td><span><b>Browser Version: </b></span></td><td> <font color='#0000CC'>" . $browser->getVersion() . "</font></td></tr></table>";
        $msgBody .= "<br><table border='0'><tr><td><span><b>Platform: </b></span></td><td> <font color='#0000CC'>" . $browser->getPlatform() . "</font></td></tr></table>";
        $msgBody .= "</table></td></tr></table>";
    
    
        $internalmail->isHTML(true);
        $internalmail->Subject = $full_name . " " . "Omni Slice Sales Agreement  ";
        $internalmail->Body = $msgBody;
        $internalmail->addAddress('apps@mattressomni.ca');
        $internalmail->addAddress('edump@furniture7.com');
        $internalmail->addBCC('applications@creditspark.ca');
        if (!$internalmail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $internalmail->ErrorInfo;
        }
    
        $salemail = new PHPMailer;
        $salemail->CharSet = "UTF-8";
        $salemail->isSMTP();                                      // Set mailer to use SMTP
        $salemail->Host = 'smtp.1and1.com';  // Specify main and backup SMTP servers
        $salemail->SMTPAuth = true;                               // Enable SMTP authentication
        $salemail->Username = 'outgoing@creditcanada.net';    // SMTP username
        $salemail->Password = 'Netsuite123!';                           // SMTP password
        $salemail->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
        $salemail->Port = 587;                                    // TCP port to connect to
        $salemail->setFrom('info@mattressomni.ca', 'mattressomni.ca');
        foreach ($files_locations as $file) {                     //attach files to email
            $salemail->addAttachment($file);
        }
        $salemail->isHTML(true);
    
    
        $msgBody = "Agent name: " . $client['agent'] . "<br>Agreement from " . $client['first_name'] . " " . $client['last_name'] . " has been received";
        $msgBody .= '<br><STRONG style="color:#F00">' . $dupcheck . '</STRONG><br>';
        $msgBody .= '<br><br><STRONG><u>Product Information:</u></STRONG> <br><br><table>';
        $msgBody .= "<tr><td><span><b>Product: </b></span></td><td>" . $client['product_details'] . " </td></tr>";
        $msgBody .= "<tr><td><span><b>Client: </b></span></td><td> " . $client['first_name'] . " " . $client['last_name'] . "</td></tr>";
        $msgBody .= "<tr><td><span><b>Email: </b></span></td><td> " . $client['email'] . "</td></tr>";
        $msgBody .= "<tr><td><span><b>Phone: </b></span></td><td> " . $client['mobile'] . "</td></tr>";
        $msgBody .= "<tr><td><hr></td><td><hr></td></tr>";
        $subject = $client['first_name'] . " " . $client['last_name'] . " " . "O Mattress™ Sales Agreement ";
    
        $salemail->Subject = $subject;
        $salemail->Body = $msgBody;
        //$salemail->addAddress("reception@canadacreditfix.com");
        //$salemail->addAddress('apps@mattressomni.ca');
        //$salemail->addAddress('apps@creditcanada.net');
    
        $salemail->addBCC('edump@furniture7.com');
        //$salemail->addAddress('hardik-p@furnitureomni.com');
    //    $salemail->addAddress("it@canadacreditfix.com");
        if ($client['agent'] != "None") {
            //$salemail->addAddress($client['agent'] . "@creditslab.com");
            $salemail->addAddress($client['agent'] . "@creditline.net");
        }
        if (!$salemail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $salemail->ErrorInfo;
        }
        //ends sales
        //Clinet Email Start
        $clientmail = new PHPMailer;
        $clientmail->CharSet = "UTF-8";
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        //$pop = POP3::popBeforeSmtp('pop.1and1.com', 995, 'wael@canadacreditfix.com', 'hardline', 1);
        $clientmail->isSMTP();                                      // Set mailer to use SMTP
        $clientmail->Host = 'smtp.1and1.com';  // Specify main and backup SMTP servers
        $clientmail->SMTPAuth = true;                               // Enable SMTP authentication
        $clientmail->Username = 'outgoing@creditcanada.net';    // SMTP username
        $clientmail->Password = 'Netsuite123!';                           // SMTP password
        $clientmail->SMTPSecure = 'TLS';                            // Enable TLS encryption, `ssl` also accepted
        $clientmail->Port = 587;                                  // TCP port to connect to
        $clientmail->setFrom('info@mattressomni.ca', 'mattressomni.ca');
        foreach ($files_locations as $file) {                     //attach files to email
            $clientmail->addAttachment($file);
        }
        $clientmail->isHTML(true);
    
    
        $express_payment_notice = '';
        if ($client['payment_type'] == 'express') {
            $express_payment_notice = "You have chosen the Express Start Program, <br>please expect we will pull $99.00 immediately, unless you have already sent an EMT<br><br>Remaining fees and and payments are detailed below.";
        }
        /* MATTRESS_COST added neha(11-1-2018) */
        $real_values = array(
            $full_name,
            $express_payment_notice,
            substr($client['setup_fee'], 0, -3) != ".00" ? $client['setup_fee'] . ".00" : $client['setup_fee'],
            $client['setup_fee_date'],
            $client['reoccur_fee'],
            $client['reoccur_fee_date'],
            $client['MATTRESS_COST']
        );
    
        $placeholders = array(
            '[NAME]',
            '[EXPRESS_PAYMENT_NOTICE]',
            '[SETUP_FEE]',
            '[SETUP_FEE_DATE]',
            '[REOCCUR_FEE]',
            '[REOCCUR_FEE_DATE]',
            '[MATTRESS_COST]'
        );
    
        $client_body = file_get_contents('client_mail_template.html');
        //replace placeholders with real values in agreement ile
        $client_body = str_replace($placeholders, $real_values, $client_body);
    
        $clientmail->Subject = "Rest Easy - You are approved !!!";
        $clientmail->Body = $client_body;
    
        $clientmail->addAddress($client['email']);
        //$clientmail->addBCC("it@canadacreditfix.com");
        $clientmail->addBCC("docs@skycapfinancial.com");
        //$clientmail->addBCC("hardik-p@furnitureomni.com");
       // $clientmail->addBCC("apps@mattressomni.ca");
        //$clientmail->addBCC('apps@creditcanada.net');
        $clientmail->addBCC("edump@furniture7.com");
        //$clientmail->addBCC("it@furnitureomni.com");
        if (!$clientmail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $clientmail->ErrorInfo;
        }
    }
}