<?php
// mail helper: uses PHPMailer if available, otherwise fallback to mail()
function send_booking_emails(array $booking, array $config){
    // $booking: associative array with keys name, email, phone, travel_date, people, package_title, package_id, requirements
    $siteEmail = $config['site_email'] ?? 'mistymunnartours@gmail.com';
    $fromName = $config['mail_from_name'] ?? 'Misty Munnar Tours';
    $fromEmail = $config['mail_from'] ?? $siteEmail;

    // Build customer message
    $customerSubject = "Booking confirmation - " . ($booking['package_title'] ?? 'Misty Munnar Tours');
    $customerBody = "Hello " . htmlspecialchars($booking['name'] ?? '') . ",\n\n";
    $customerBody .= "Thank you for your booking request. Here are the details we received:\n\n";
    $customerBody .= "Package: " . ($booking['package_title'] ?? '') . "\n";
    $customerBody .= "Travel Date: " . ($booking['travel_date'] ?? '') . "\n";
    $customerBody .= "People: " . ($booking['people'] ?? '') . "\n";
    $customerBody .= "Phone: " . ($booking['phone'] ?? '') . "\n";
    $customerBody .= "Additional requirements: " . ($booking['requirements'] ?? '') . "\n\n";
    $customerBody .= "We will contact you shortly to confirm the booking. For urgent queries, message us on WhatsApp or call us at the numbers on the site.\n\n";
    $customerBody .= "Best regards,\nMisty Munnar Tours";

    // Build admin message
    $adminSubject = "New booking received";
    $adminBody = "New booking details:\n\n";
    foreach($booking as $k=>$v){
        $adminBody .= ucfirst($k) . ": " . ($v ?? '') . "\n";
    }

    // Try PHPMailer if composer autoload exists
    $sent = ['customer'=>false,'admin'=>false];
    if(file_exists(__DIR__ . '/../vendor/autoload.php')){
        try{
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            // SMTP config if provided
            if(!empty($config['smtp']['host'])){
                $mail->isSMTP();
                $mail->Host = $config['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp']['user'];
                $mail->Password = $config['smtp']['pass'];
                $mail->SMTPSecure = $config['smtp']['encryption'] ?? 'tls';
                $mail->Port = $config['smtp']['port'] ?? 587;
            }
            $mail->setFrom($fromEmail, $fromName);

            // send to customer if email provided
            if(!empty($booking['email'])){
                $mail->addAddress($booking['email'], $booking['name'] ?? '');
                $mail->Subject = $customerSubject;
                $mail->Body = $customerBody;
                $mail->AltBody = strip_tags($customerBody);
                $mail->send();
                $sent['customer'] = true;
                $mail->clearAddresses();
            }

            // send admin
            $mail->addAddress($siteEmail);
            $mail->Subject = $adminSubject;
            $mail->Body = $adminBody;
            $mail->AltBody = strip_tags($adminBody);
            $mail->send();
            $sent['admin'] = true;
        }catch(Exception $e){
            // log to file
            error_log("PHPMailer error: " . $e->getMessage());
        }
    }else{
        // fallback to mail()
        if(!empty($booking['email'])){
            $headers = "From: {$fromName} <{$fromEmail>}\r\n" . "Content-Type: text/plain; charset=utf-8\r\n";
            $sent['customer'] = mail($booking['email'], $customerSubject, $customerBody, $headers);
        }
        $headers = "From: {$fromName} <{$fromEmail>}\r\n" . "Content-Type: text/plain; charset=utf-8\r\n";
        $sent['admin'] = mail($siteEmail, $adminSubject, $adminBody, $headers);
    }

    // Log failures
    if(!$sent['admin'] || (!$sent['customer'] && !empty($booking['email']))){
        $log = sprintf("[%s] email_send: admin=%s customer=%s booking=%s\n", date('c'), var_export($sent, true), var_export($booking['email'] ?? '', true), json_encode($booking));
        @file_put_contents(__DIR__ . '/../logs/email.log', $log, FILE_APPEND);
    }

    return $sent;
}
