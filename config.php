<?php
declare(strict_types=1);

/*
 * config.php - shared settings and helper functions for the backend.
 * Included by submit.php and get_students.php with: require __DIR__ . '/config.php';
 */

// Every backend response is JSON.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Location of the JSON "database".
define('DATA_FILE', __DIR__ . '/data/students.json');

// Allowed values for the select / radio fields.
const COURSES = ['B.Tech CSE', 'BCA', 'MCA', 'B.Sc IT'];
const GENDERS = ['Male', 'Female', 'Other'];

/**
 * Send a JSON response and stop the script.
 */
function send_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Read all students from the JSON file.
 * Returns an empty array if the file is missing, empty or invalid.
 */
function load_students(): array
{
    if (!is_file(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json === false ? '' : $json, true);
    if (!is_array($data)) {
        return [];
    }
    // Keep only proper student records.
    return array_values(array_filter($data, 'is_array'));
}

/**
 * Write all students to the JSON file. Returns true on success.
 */
function save_students(array $students): bool
{
    $json = json_encode(array_values($students), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return file_put_contents(DATA_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

/**
 * Convert marks into a grade.
 *   90-100 = A+, 80-89 = A, 70-79 = B, 60-69 = C, 0-59 = D
 */
function grade($marks): string
{
    $marks = (float) $marks;
    if ($marks >= 90) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    return 'D';
}

/**
 * Clean raw user input: convert to string, remove control characters, trim spaces.
 * Used before validation and saving.
 */
function clean_input($value): string
{
    $value = is_scalar($value) ? (string) $value : '';
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    return trim($value);
}

/**
 * Make a value safe to display in HTML (blocks HTML / script injection).
 */
function clean($value): string
{
    return htmlspecialchars(trim((string) ($value ?? '')), ENT_QUOTES, 'UTF-8');
}
