<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// BigFix server details
$bigfix_server = "server.bigfix.com";
$bigfix_port = "52311";
$username = "USERNAME";
$password = "PASSWORD";

// Function to make API request
function makeApiRequest($url) {
    global $username, $password;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        throw new Exception("HTTP Error: " . $httpCode . " - Response: " . $response);
    }
    
    return $response;
}

// Get file location from URL parameter and decode it
$fileLocation = isset($_GET['location']) ? urldecode($_GET['location']) : '';
$error = null;
$fileContent = null;

if (!empty($fileLocation)) {
    try {
        // Construct the URL for file content
        $url = "https://$bigfix_server:$bigfix_port/api/archivemanager/file" . $fileLocation;
        $fileContent = makeApiRequest($url);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = "No file location provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Content</title>
    <style>
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f4f4f4;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>File Content</h1>
    <?php if ($error): ?>
        <p class="error">Error: <?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($fileContent !== null): ?>
        <h2>File Location: <?php echo htmlspecialchars($fileLocation); ?></h2>
        <pre><?php echo htmlspecialchars($fileContent); ?></pre>
    <?php else: ?>
        <p>No file content available.</p>
    <?php endif; ?>
    <a href="javascript:history.back()">Back to file list</a>
</body>
</html> 
