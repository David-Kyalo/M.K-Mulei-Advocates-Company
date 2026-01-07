<?php
/**
 * Consultation Request Handler
 * Handles form submissions, saves to database, and sends email notifications
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Database configuration
$db_host = 'localhost';
$db_name = 'law_firm_db';
$db_user = 'root'; // Change this to your database username
$db_pass = ''; // Change this to your database password

// Email configuration
$to_email = 'mkmulei2023@gmail.com';
$from_email = 'noreply@mkmulei.com'; // Change this to your domain email

// Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$practice_area = isset($_POST['practiceArea']) ? trim($_POST['practiceArea']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$appointment_date = isset($_POST['appointmentDate']) ? trim($_POST['appointmentDate']) : '';
$appointment_time = isset($_POST['appointmentTime']) ? trim($_POST['appointmentTime']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($phone)) {
    $errors[] = 'Phone is required';
}

if (empty($practice_area)) {
    $errors[] = 'Practice area is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (empty($appointment_date)) {
    $errors[] = 'Appointment date is required';
}

if (empty($appointment_time)) {
    $errors[] = 'Appointment time is required';
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Combine date and time
$appointment_datetime = $appointment_date . ' ' . $appointment_time;

// Validate datetime format
$datetime_obj = DateTime::createFromFormat('Y-m-d H:i', $appointment_datetime);
if (!$datetime_obj || $datetime_obj->format('Y-m-d H:i') !== $appointment_datetime) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date/time format']);
    exit;
}

// Format datetime for display
$formatted_datetime = $datetime_obj->format('F j, Y \a\t g:i A');

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO consultation_requests 
        (name, email, phone, practice_area, message, appointment_datetime, created_at) 
        VALUES (:name, :email, :phone, :practice_area, :message, :appointment_datetime, NOW())
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':practice_area' => $practice_area,
        ':message' => $message,
        ':appointment_datetime' => $appointment_datetime
    ]);
    
    $request_id = $pdo->lastInsertId();
    
    // Prepare email content
    $email_subject = "New Consultation Request - " . $name;
    $email_body = "A client with the name " . htmlspecialchars($name) . " has booked an appointment on " . $formatted_datetime . ".\n\n";
    $email_body .= "Contact Details:\n";
    $email_body .= "Email: " . htmlspecialchars($email) . "\n";
    $email_body .= "Phone: " . htmlspecialchars($phone) . "\n";
    $email_body .= "Practice Area: " . htmlspecialchars($practice_area) . "\n\n";
    $email_body .= "Message:\n" . htmlspecialchars($message) . "\n\n";
    $email_body .= "Request ID: #" . $request_id;
    
    // Email headers
    $headers = "From: " . $from_email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    $mail_sent = mail($to_email, $email_subject, $email_body, $headers);
    
    if ($mail_sent) {
        echo json_encode([
            'success' => true, 
            'message' => 'Your consultation request has been submitted successfully. We will contact you soon.',
            'request_id' => $request_id
        ]);
    } else {
        // Even if email fails, the request is saved in database
        echo json_encode([
            'success' => true, 
            'message' => 'Your consultation request has been saved. We will contact you soon.',
            'request_id' => $request_id,
            'warning' => 'Email notification could not be sent, but your request was saved.'
        ]);
    }
    
} catch (PDOException $e) {
    // Log error (in production, log to file instead of exposing details)
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again later!'
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
?>

