<?php
declare(strict_types=1);

/*
 * get_students.php - sends every saved student to the frontend as JSON,
 * with a grade for each student and the class average.
 */

require __DIR__ . '/config.php';

$students = load_students();
$output   = [];
$total    = 0;

foreach ($students as $s) {
    $marks = (int) ($s['marks'] ?? 0);
    $total += $marks;

    // Text fields are escaped with htmlspecialchars() because the
    // frontend inserts them into the page as HTML.
    $output[] = [
        'enrollment' => clean($s['enrollment'] ?? ''),
        'name'       => clean($s['name'] ?? ''),
        'course'     => clean($s['course'] ?? ''),
        'gender'     => clean($s['gender'] ?? ''),
        'marks'      => $marks,
        'grade'      => grade($marks),
    ];
}

$count   = count($output);
$average = $count > 0 ? round($total / $count, 1) : 0;

send_json([
    'count'    => $count,
    'average'  => $average,
    'students' => $output,
]);
