<?php
// Centralized PDF -> image preview helper using external REST API (cURL).
// Requires environment variables: PDF_API_KEY and optional PDF_API_BASE (defaults to https://api.pdf.co/v1)
// The function will attempt to upload the PDF, request conversion of the FIRST page to JPG/PNG,
// download the resulting image and optionally resize it (using GD if available).

// Load .env file if it exists
if (!function_exists('loadEnvFile')) {
    function loadEnvFile($filePath) {
        if (!file_exists($filePath)) return;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // Skip comments
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) { // Only set if not already set
                putenv("$key=$value");
            }
        }
    }
}

// Load .env from project root
$envPath = realpath(__DIR__ . '/../.env');
if ($envPath) {
    loadEnvFile($envPath);
}

if (!function_exists('generateDocumentPreview')) {
    function generateDocumentPreview($filePath, $previewPath) {
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Only handle PDFs here
        if ($fileExtension !== 'pdf') {
            error_log("PDF preview: Unsupported file type: $fileExtension");
            // Let caller fall back to other handlers if needed
            return 'placeholder';
        }

        // Basic file validation
        if (!file_exists($filePath) || !is_readable($filePath) || filesize($filePath) < 100) {
            error_log("PDF preview: file not accessible or too small: $filePath");
            createPlaceholderPreview($previewPath, 'PDF Document', 'File not accessible');
            return 'placeholder';
        }

        $apiKey = getenv('PDF_API_KEY');
        $apiBase = getenv('PDF_API_BASE') ?: 'https://api.pdf.co/v1';

        if (!$apiKey) {
            error_log("PDF preview: API key is not configured (PDF_API_KEY)");
            createPlaceholderPreview($previewPath, 'PDF Document', 'Preview service not configured');
            return 'placeholder';
        }

        error_log("PDF preview: Starting conversion for: $filePath (API base: $apiBase)");

        // Ensure preview directory exists
        $previewDir = dirname($previewPath);
        if (!is_dir($previewDir)) {
            @mkdir($previewDir, 0755, true);
        }

        // Step 1: Upload the PDF to the API (many services require an upload step that returns a URL)
        $uploadUrl = rtrim($apiBase, '/') . '/file/upload';
        error_log("PDF preview: Uploading to: $uploadUrl");

        $uploadResponse = apiPostMultipart($uploadUrl, ['file' => new CURLFile($filePath)], $apiKey);
        error_log("PDF preview: Upload response: " . json_encode($uploadResponse));
        if (!$uploadResponse || !is_array($uploadResponse)) {
            error_log("PDF preview: upload failed or invalid response");
            createPlaceholderPreview($previewPath, 'PDF Document', 'Preview upload failed');
            return 'placeholder';
        }

        // Look for an uploaded file URL
        $uploadedFileUrl = null;
        if (!empty($uploadResponse['url'])) {
            $uploadedFileUrl = $uploadResponse['url'];
        } elseif (!empty($uploadResponse['uploadUrl'])) {
            $uploadedFileUrl = $uploadResponse['uploadUrl'];
        }

        if (!$uploadedFileUrl) {
            error_log('PDF preview: could not determine uploaded file URL: ' . json_encode($uploadResponse));
            createPlaceholderPreview($previewPath, 'PDF Document', 'Preview upload failed');
            return 'placeholder';
        }

        error_log("PDF preview: Uploaded URL: $uploadedFileUrl");

        // Step 2: Request conversion of the FIRST page to JPG
        // pdf.co uses 0-based page numbering: page 0 = first page
        $convertEndpoint = rtrim($apiBase, '/') . '/pdf/convert/to/jpg';
        error_log("PDF preview: Converting at: $convertEndpoint");
        $payload = [
            'url' => $uploadedFileUrl,
            'pages' => '0',  // 0 = first page (0-based indexing for pdf.co)
            'async' => false
        ];

        $convertResponse = apiPostJson($convertEndpoint, $payload, $apiKey);
        error_log("PDF preview: Convert response: " . json_encode($convertResponse));
        if (!$convertResponse || !is_array($convertResponse)) {
            error_log('PDF preview: convert API returned invalid response');
            createPlaceholderPreview($previewPath, 'PDF Document', 'Preview conversion failed');
            return 'placeholder';
        }

        // The API may return either an array of URLs ("urls"), a single "url", or a base64-encoded result
        $imageUrl = null;
        $imageBase64 = null;

        if (!empty($convertResponse['urls']) && is_array($convertResponse['urls'])) {
            $imageUrl = $convertResponse['urls'][0];
        } elseif (!empty($convertResponse['url'])) {
            $imageUrl = $convertResponse['url'];
        } elseif (!empty($convertResponse['base64'])) {
            $imageBase64 = $convertResponse['base64'];
        }

        if ($imageBase64) {
            $decoded = base64_decode($imageBase64);
            if ($decoded === false || strlen($decoded) < 100) {
                error_log('PDF preview: base64 result invalid');
                createPlaceholderPreview($previewPath, 'PDF Document', 'Preview conversion failed');
                return 'placeholder';
            }
            file_put_contents($previewPath, $decoded);
        } elseif ($imageUrl) {
            if (!downloadFile($imageUrl, $previewPath)) {
                error_log('PDF preview: failed to download converted image from ' . $imageUrl);
                createPlaceholderPreview($previewPath, 'PDF Document', 'Preview download failed');
                return 'placeholder';
            }
        } else {
            error_log('PDF preview: no image url/base64 in convert response: ' . json_encode($convertResponse));
            createPlaceholderPreview($previewPath, 'PDF Document', 'Preview conversion failed');
            return 'placeholder';
        }

        // Optional: Resize the result to a reasonable thumbnail with GD if available
        if (file_exists($previewPath) && filesize($previewPath) > 500) {
            // Attempt to normalize size to 200x (preserving aspect ratio)
            if (function_exists('imagecreatefromstring') && function_exists('imagesx')) {
                $imgData = file_get_contents($previewPath);
                $src = @imagecreatefromstring($imgData);
                if ($src !== false) {
                    $srcW = imagesx($src);
                    $srcH = imagesy($src);

                    $targetW = 200;
                    $targetH = 0; // preserve aspect

                    $ratio = $srcW > 0 ? ($targetW / $srcW) : 1;
                    $targetH = (int) max(1, floor($srcH * $ratio));

                    $dst = imagecreatetruecolor($targetW, $targetH);
                    // Preserve PNG transparency if applicable
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);

                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

                    // Overwrite previewPath as JPEG to ensure broad compatibility
                    imagejpeg($dst, $previewPath, 85);
                    imagedestroy($dst);
                    imagedestroy($src);
                }
            }

            clearstatcache(true, $previewPath);
            if (file_exists($previewPath) && filesize($previewPath) > 1000) {
                error_log('PDF preview: successfully created thumbnail at ' . $previewPath);
                return 'success';
            }
        }

        // If we reach here, thumbnail is invalid
        error_log('PDF preview: final validation failed for ' . $previewPath);
        @unlink($previewPath);
        createPlaceholderPreview($previewPath, 'PDF Document', 'Preview generation failed');
        return 'placeholder';
    }
}

// Helper: Create a placeholder image for failed PDF previews
if (!function_exists('createPlaceholderPreview')) {
    function createPlaceholderPreview($previewPath, $documentType, $additionalText = '') {
        try {
            $previewDir = dirname($previewPath);
            if (!is_dir($previewDir)) {
                @mkdir($previewDir, 0755, true);
            }

            $image = imagecreatetruecolor(200, 280);
            $bgColor = imagecolorallocate($image, 248, 248, 248);
            $textColor = imagecolorallocate($image, 80, 80, 80);
            $borderColor = imagecolorallocate($image, 220, 220, 220);
            $accentColor = imagecolorallocate($image, 70, 130, 180);

            imagefill($image, 0, 0, $bgColor);
            imagerectangle($image, 0, 0, 199, 279, $borderColor);

            $fontSize = 3;
            $text = $documentType;
            $textWidth = imagefontwidth($fontSize) * strlen($text);
            $x = (200 - $textWidth) / 2;
            $y = 140 - (imagefontheight($fontSize) / 2);

            $bgWidth = $textWidth + 20;
            $bgHeight = imagefontheight($fontSize) + 10;
            $bgX = (200 - $bgWidth) / 2;
            $bgY = $y - 5;
            imagefilledrectangle($image, $bgX, $bgY, $bgX + $bgWidth, $bgY + $bgHeight, $accentColor);
            imagestring($image, $fontSize, $x, $y, $text, imagecolorallocate($image, 255, 255, 255));

            if (!empty($additionalText)) {
                $smallFontSize = 2;
                $lines = explode("\n", wordwrap($additionalText, 20, "\n"));
                $lineY = $y + 30;
                foreach ($lines as $line) {
                    $lineWidth = imagefontwidth($smallFontSize) * strlen($line);
                    $lineX = (200 - $lineWidth) / 2;
                    imagestring($image, $smallFontSize, $lineX, $lineY, $line, $textColor);
                    $lineY += imagefontheight($smallFontSize) + 2;
                }
            }

            imagejpeg($image, $previewPath, 85);
            imagedestroy($image);
            error_log('PDF preview: placeholder created at ' . $previewPath);
            return true;
        } catch (Exception $e) {
            error_log('PDF preview: failed to create placeholder: ' . $e->getMessage());
            return false;
        }
    }
}

// Helper: POST multipart/form-data (file upload)
function apiPostMultipart($url, $fields, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno || $httpCode >= 400) {
        error_log("apiPostMultipart error: $err (HTTP $httpCode)");
        return null;
    }

    $json = json_decode($resp, true);
    return $json ?: null;
}

// Helper: POST JSON and return parsed JSON
function apiPostJson($url, $payload, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("apiPostJson: URL=$url, HTTP=$httpCode, error=$err, response=" . substr($resp, 0, 500));

    if ($errno) {
        error_log("apiPostJson curl error: $err (errno $errno)");
        return null;
    }
    
    if ($httpCode >= 400) {
        error_log("apiPostJson HTTP error: $httpCode - full response: $resp");
        return null;
    }

    $json = json_decode($resp, true);
    return $json ?: null;
}

// Helper: download remote URL to local path
function downloadFile($url, $dest) {
    $ch = curl_init($url);
    $fp = fopen($dest, 'w');
    if (!$fp) return false;
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($errno || $httpCode >= 400) {
        error_log("downloadFile error: $err (HTTP $httpCode) - url: $url");
        @unlink($dest);
        return false;
    }
    return true;
}
