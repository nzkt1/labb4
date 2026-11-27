<?php
declare(strict_types=1);

/*
 * submit.php - receives the registration form (POST), validates it,
 * saves a valid student to data/students.json and answers with JSON.
 */

require __DIR__ . '/config.php';

// 1. Accept POST requests only.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    send_json(['success' => false, 'errors' => ['Only POST requests are allowed.']], 405);
}

// 2. Read the form data.
$name       = clean_input($_POST['name'] ?? '');
$enrollment = strtoupper(clean_input($_POST['enrollment'] ?? ''));
$email      = clean_input($_POST['email'] ?? '');
$course     = clean_input($_POST['course'] ?? '');
$gender     = clean_input($_POST['gender'] ?? '');
$marksRaw   = clean_input($_POST['marks'] ?? '');

// 3. Validate every field.
$errors = [];

if ($name === '') {
    $errors[] = 'Full name is required.';
} elseif (!preg_match("/^\p{L}[\p{L}\s.'\-]{1,59}$/u", $name)) {
    $errors[] = 'Name must be 2-60 characters (letters, spaces, . \' - only).';
}

if ($enrollment === '') {
    $errors[] = 'Enrollment number is required.';
} elseif (!preg_match('/^[A-Z0-9\-]{3,20}$/', $enrollment)) {
    $errors[] = 'Enrollment number must be 3-20 letters, digits or hyphens.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}

if (!in_array($course, COURSES, true)) {
    $errors[] = 'Please select a valid course.';
}

if (!in_array($gender, GENDERS, true)) {
    $errors[] = 'Please select a gender.';
}

$marks = filter_var($marksRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
if ($marksRaw === '') {
    $errors[] = 'Marks are required.';
} elseif ($marks === false) {
    $errors[] = 'Marks must be a whole number between 0 and 100.';
}

// 4. Reject duplicate enrollment numbers.
$students = load_students();
if ($enrollment !== '' && !$errors) {
    foreach ($students as $s) {
        if (strcasecmp((string) ($s['enrollment'] ?? ''), $enrollment) === 0) {
            $errors[] = 'A student with this enrollment number already exists.';
            break;
        }
    }
}

if ($errors) {
    send_json(['success' => false, 'errors' => $errors], 422);
}

// 5. Save the student.
$students[] = [
    'enrollment' => $enrollment,
    'name'       => $name,
    'email'      => $email,
    'course'     => $course,
    'gender'     => $gender,
    'marks'      => $marks,
];

if (!save_students($students)) {
    send_json(['success' => false, 'errors' => ['The server could not write to students.json. Check folder permissions.']], 500);
}

send_json(['success' => true, 'message' => "Student {$name} saved successfully!"]);
