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

// Process form submission
$computerId = "549251038"; // Default computer ID
$error = null;
$data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $computerId = $_POST["computerId"];
}

try {
    $url = "https://$bigfix_server:$bigfix_port/api/archivemanager?computerId=$computerId&output=json";
    $response = makeApiRequest($url);
    $data = json_decode($response, true);
    
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Decode Error: " . json_last_error_msg());
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BigFix Archive Manager</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>BigFix Archive Manager</h1>
    <form method="post">
        <label for="computerId">Enter Computer ID:</label>
        <input type="text" id="computerId" name="computerId" value="<?php echo htmlspecialchars($computerId); ?>" required>
        <input type="submit" value="Submit">
    </form>

    <?php if ($error): ?>
        <p class="error">Error: <?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($data && !empty($data)): ?>
        <h2>Files for Computer ID: <?php echo htmlspecialchars($computerId); ?></h2>
        <table>
            <tr>
                <th>File Name</th>
                <th>File Location</th>
                <th>File Size</th>
                <th>Sequence</th>
                <th>File Received At</th>
            </tr>
            <?php foreach ($data as $file): ?>
                <tr>
                    <td><a href="bigfix_decoded_display_file.php?location=<?php echo urlencode($file['FileLocation']); ?>"><?php echo htmlspecialchars($file['FileName']); ?></a></td>
                    <td><?php echo htmlspecialchars($file['FileLocation']); ?></td>
                    <td><?php echo htmlspecialchars($file['FileSize']); ?></td>
                    <td><?php echo htmlspecialchars($file['Sequence']); ?></td>
                    <td><?php echo date('Y-m-d H:i:s', $file['FileReceivedAt'] / 1000000); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No files found for the given Computer ID.</p>
    <?php endif; ?>
</body>
</html> 
