<?php

$token = "8783194867:AAF4a8tAkN07v9t0o5ewB_CbRsZBDBNaKJw";
$api = "https://api.telegram.org/bot$token/";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['message']['from']['id'] ?? null;
$text = $data['message']['text'] ?? "";

// Load users
$users = json_decode(file_get_contents("users.json"), true);

// Create user
if (!isset($users[$user_id])) {
    $users[$user_id] = [
        "balance" => 500,
        "step" => ""
    ];
}

// Start
if ($text == "/start") {
    send($user_id, "Welcome 🎉\nBalance: ₦".$users[$user_id]['balance']);
}

// Deposit
elseif ($text == "deposit") {
    $users[$user_id]['step'] = "deposit";
    send($user_id, "Enter amount:");
}

// Handle numbers
elseif (is_numeric($text)) {

    if ($users[$user_id]['step'] == "deposit") {
        $amount = intval($text);
        $users[$user_id]['balance'] += $amount;
        $users[$user_id]['step'] = "";

        send($user_id, "Deposited ₦$amount");
    }
}

// Balance
elseif ($text == "balance") {
    send($user_id, "Balance: ₦".$users[$user_id]['balance']);
}

// Save
file_put_contents("users.json", json_encode($users));

// Send function
function send($id, $msg) {
    global $api;
    file_get_contents($api."sendMessage?chat_id=$id&text=".urlencode($msg));
}
