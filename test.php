<?php
// Replace with your own keys
$botToken = "7794888133:AAFdGB1uEu3kEGifpleEZLUID5ORGOnh3WA";
$removeBgApiKey = "7BQN82rEmfPEtFAAm8HrreT7";
$apiURL = "https://api.telegram.org/bot$botToken/";

$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Check if it's a photo message
if (isset($update["message"]["photo"])) {
    $chatId = $update["message"]["chat"]["id"];
    $fileId = end($update["message"]["photo"])["file_id"];

    // Step 1: Get file path
    $filePathResponse = file_get_contents($apiURL . "getFile?file_id=$fileId");
    $filePathData = json_decode($filePathResponse, true);
    $filePath = $filePathData["result"]["file_path"];

    $fileUrl = "https://api.telegram.org/file/bot$botToken/$filePath";

    // Step 2: Send to remove.bg
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.remove.bg/v1.0/removebg');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'image_url' => $fileUrl,
        'size' => 'auto'
    ]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $removeBgApiKey
    ]);

    $result = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus == 200) {
        $filename = "no_bg_" . time() . ".png";
        file_put_contents($filename, $result);

        // Step 3: Send the result back
        $sendUrl = $apiURL . "sendPhoto";
        $postFields = [
            'chat_id' => $chatId,
            'photo' => new CURLFile($filename)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_URL, $sendUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_exec($ch);
        curl_close($ch);

        unlink($filename); // Clean up
    } else {
        file_get_contents($apiURL . "sendMessage?chat_id=$chatId&text=Failed to remove background. Try again later.");
    }
} else {
    // Handle non-photo messages
    $chatId = $update["message"]["chat"]["id"];
    file_get_contents($apiURL . "sendMessage?chat_id=$chatId&text=Please send a photo to remove its background.");
}
?>
