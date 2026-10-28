<?php
/**
 * Broad Street Boxing — Contact Form Mailer
 * Receives POST from the contact form and emails coach@broadstreetboxing.co.uk
 */

// ── Security headers ──────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed.']);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// ── Collect & sanitise inputs ─────────────────────────────────────────────────
$name    = clean($_POST['name']      ?? '');
$email   = trim($_POST['email']      ?? '');
$phone   = clean($_POST['phone']     ?? '');
$age     = clean($_POST['age_group'] ?? '');
$source  = clean($_POST['source']    ?? '');
$message = clean($_POST['message']   ?? '');

// ── Server-side validation ────────────────────────────────────────────────────
$errors = [];

if ($name === '') {
    $errors[] = 'Full name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
} else {
    $email = clean($email); // safe to sanitise after validation
}
if ($age === '') {
    $errors[] = 'Age group is required.';
}
if ($message === '') {
    $errors[] = 'A message is required.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => implode(' ', $errors)]);
    exit;
}

// ── Build the email ───────────────────────────────────────────────────────────
$to      = 'coach@broadstreetboxing.co.uk';
$subject = 'New Enquiry: ' . $name . ' — Broad Street Boxing';

$sourceLabels = [
    'friend' => 'Friend or family',
    'search' => 'Google / internet search',
    'social' => 'Social media',
    'local'  => 'Local flyer / poster',
    'event'  => 'Saw us at an event',
    'other'  => 'Other',
];
$sourceText = $sourceLabels[$source] ?? 'Not specified';

$ageLabels = [
    'u16'   => 'Under 16',
    '16-30' => '16–30',
    '31-50' => '31–50',
    '50+'   => '50+',
];
$ageText = $ageLabels[$age] ?? $age;

$body  = "NEW WEBSITE ENQUIRY — BROAD STREET BOXING\n";
$body .= str_repeat('=', 46) . "\n\n";
$body .= "Name       : {$name}\n";
$body .= "Email      : {$email}\n";
$body .= "Phone      : " . ($phone !== '' ? $phone : 'Not provided') . "\n";
$body .= "Age Group  : {$ageText}\n";
$body .= "Heard via  : {$sourceText}\n\n";
$body .= "MESSAGE\n";
$body .= str_repeat('-', 46) . "\n";
$body .= $message . "\n\n";
$body .= str_repeat('=', 46) . "\n";
$body .= "Sent from broadstreetboxing.co.uk\n";

// ── Mail headers ──────────────────────────────────────────────────────────────
// From must be a valid address on THIS domain to avoid Fasthosts spam filters
$headers  = "From: Broad Street Boxing Website <noreply@broadstreetboxing.co.uk>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "MIME-Version: 1.0\r\n";

// ── Send ──────────────────────────────────────────────────────────────────────
$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(['ok' => true, 'msg' => 'Message sent.']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server could not send the email. Please try again or call us directly.']);
}
