<?php

echo "Parser starting...\n";

// Load saved HTML
$files = glob(__DIR__ . '/../storage/html/*.html');
if (empty($files)) {
    die("No HTML files found.\n");
}

$htmlFile = end($files);
$html = file_get_contents($htmlFile);

echo "Loaded HTML file: " . basename($htmlFile) . "\n";

// Parse DOM
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// USC-style job links
$nodes = $xpath->query("//a[contains(@href, '/postings/')]");

echo "Found {$nodes->length} potential job links\n";

foreach ($nodes as $node) {
    $title = trim($node->textContent);

    // ---- Skip junk links ----
    if ($title === '' || strtolower($title) === 'view details') continue;
    if (preg_match('/^\d+$/', $title)) continue; // page numbers
    if (strtolower($title) === 'next') continue;

    // ---- Scoring setup ----
    $lowerTitle = strtolower($title);
    $score = 0;

    $positiveKeywords = [
        'software', 'developer', 'application', 'engineer',
        'php', 'javascript', 'web', 'frontend', 'backend',
        'junior', 'entry', 'associate'
    ];

    $negativeKeywords = [
        'senior', 'lead', 'director', 'manager',
        'assistant', 'clinical', 'nursing', 'professor',
        'coordinator', 'custodial'
    ];

    // ---- Positive scoring ----
    foreach ($positiveKeywords as $word) {
        if (strpos($lowerTitle, $word) !== false) {
            $score += 2;
        }
    }

// ---- Negative scoring ----
foreach ($negativeKeywords as $word) {
    if (strpos($lowerTitle, $word) !== false) {
        $score -= 3;
    }
}

// ---- Minimum score filter (JOB-LEVEL) ----
if ($score < 2) {
    continue; // skip this job entirely
}

// ---- Output ----
echo "[{$score}] {$title}\n";
}