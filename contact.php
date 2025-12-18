<?php
/**
 * CONTACT FORM HANDLER
 * 
 * SETUP INSTRUCTIONS:
 * 1. Set the recipient email below ($to variable)
 * 2. Set your site name ($siteName variable)
 * 3. Upload this file to your website's root directory (same folder as index.html)
 * 4. Test by submitting the contact form on your website
 * 
 * UPLOAD LOCATION:
 * Upload contact.php to the same directory as your index.html file
 * (usually the public_html or www folder on your FTP server)
 * 
 * QUICK TEST:
 * 1. Fill out the contact form on your website
 * 2. Submit the form
 * 3. Check your email inbox (info@royalgardenrestaurant.rs)
 * 4. You should receive a notification email
 */

// ============================================
// CONFIGURATION - EDIT THESE VALUES
// ============================================

$to = "info@royalgardenrestaurant.rs";
$siteName = "Royal Garden Restaurant";
$successRedirect = "/thank-you.html";
$errorRedirect = "/error.html";

// ============================================
// END CONFIGURATION
// ============================================

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die('Method Not Allowed. This form only accepts POST requests.');
}

// Get server domain for From header
$serverDomain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
if (empty($serverDomain) || $serverDomain === 'localhost') {
    // Try to get from SERVER_NAME as fallback
    $serverDomain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'noreply.local';
}

// Sanitize and get form fields
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
$subject = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';
$honeypot = isset($_POST['company']) ? trim($_POST['company']) : '';

// Honeypot check - if filled, it's spam
if (!empty($honeypot)) {
    // Return 200 OK but don't send email (silent fail for spam)
    http_response_code(200);
    header('Location: ' . $successRedirect);
    exit;
}

// Validate required fields
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

// If validation errors, redirect to error page
if (!empty($errors)) {
    header('Location: ' . $errorRedirect);
    exit;
}

// Get additional information
$timestamp = date('Y-m-d H:i:s');
$userIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct access';

// Build email subject
$emailSubject = "[WEBSITE] New Contact Form Submission - " . $subject;

// Build email body (plain text)
$emailBody = "Website contact form submission\n\n";
$emailBody .= "========================================\n\n";
$emailBody .= "Name: " . $name . "\n";
$emailBody .= "Email: " . $email . "\n";
$emailBody .= "Subject: " . $subject . "\n\n";
$emailBody .= "Message:\n";
$emailBody .= str_repeat("-", 40) . "\n";
$emailBody .= $message . "\n";
$emailBody .= str_repeat("-", 40) . "\n\n";
$emailBody .= "========================================\n";
$emailBody .= "Additional Information:\n";
$emailBody .= "Timestamp: " . $timestamp . "\n";
$emailBody .= "User IP: " . $userIP . "\n";
$emailBody .= "Source URL: " . $referer . "\n";
$emailBody .= "========================================\n";

// Prepare email headers
$headers = [];
$headers[] = "From: " . $siteName . " Website <no-reply@" . $serverDomain . ">";
$headers[] = "Reply-To: " . $email;
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers[] = "X-Mailer: PHP/" . phpversion();

// Send email
$mailSent = @mail($to, $emailSubject, $emailBody, implode("\r\n", $headers));

// Check if request is AJAX (for JSON response)
$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($isAjax) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again.']);
    }
    exit;
} else {
    // Redirect for regular form submissions
    if ($mailSent) {
        header('Location: ' . $successRedirect);
    } else {
        header('Location: ' . $errorRedirect);
    }
    exit;
}
?>

