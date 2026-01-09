<?php
// Load .env file
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Test if PDF_API_KEY is set in the environment
$apiKey = getenv('PDF_API_KEY');
$apiBase = getenv('PDF_API_BASE');

// Check .env file
$envExists = file_exists($envPath);

echo "<h1>Environment Variables Check</h1>\n";
echo "<p><strong>.env file exists:</strong> " . ($envExists ? "✅ YES at $envPath" : "❌ NO") . "</p>\n";
echo "<p><strong>PDF_API_KEY from getenv():</strong> " . ($apiKey ? "✅ YES (first 20 chars: " . substr($apiKey, 0, 20) . "...)" : "❌ NO") . "</p>\n";
echo "<p><strong>PDF_API_BASE from getenv():</strong> " . ($apiBase ? "✅ YES ($apiBase)" : "❌ NO (will use default)") . "</p>\n";

if ($apiKey) {
    echo "<p style='color:green;'><strong>✅ Environment is configured correctly.</strong></p>\n";
    echo "<p>You can now upload PDFs and thumbnails should be generated.</p>\n";
} else {
    echo "<p style='color:red;'><strong>⚠️ PDF_API_KEY is not configured!</strong></p>\n";
    echo "<p>Check your .env file:</p>\n";
    if ($envExists) {
        echo "<pre>";
        echo htmlspecialchars(file_get_contents($envPath));
        echo "</pre>\n";
    }
}
?>
