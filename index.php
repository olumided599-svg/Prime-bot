<?php

$token = "8783194867:AAF4a8tAkN07v9t0o5ewB_CbRsZBDBNaKJw";
$api = "https://api.telegram.org/bot$token/";

// 👑 ADMIN ID
$admin = 7510750214;

// 🔐 FORCE JOIN
$channel = "@starfordfreenumbers";
$group = "@Primevestglobalinvestments";

$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("error.log", print_r($data, true), FILE_APPEND);

$user_id = $data['message']['from']['id'] ?? null;
$text = trim($data['message']['text'] ?? "");

// CALLBACK
$callback = $data['callback_query'] ?? null;

// LOAD USERS
$users = json_decode(file_get_contents("users.json"), true);

// FUNCTION: CHECK JOIN
function isJoined($user_id, $chat) {
    global $api;
    $res = json_decode(file_get_contents($api."getChatMember?chat_id=$chat&user_id=$user_id"), true);
    return in_array($res['result']['status'], ['member','administrator','creator']);
}

// CALLBACK HANDLER
if ($callback) {

    $uid = $callback['from']['id'];
    $data_cb = $callback['data'];
    $cid = $callback['id'];

    if ($data_cb == "check_join") {
        if (isJoined($uid, $channel) && isJoined($uid, $group)) {
            answer($cid, "✅ Verified!");
            send($uid, "🎉 You can now use the bot! Send /start");
        } else {
            answer($cid, "❌ Join first!");
        }
    }

    // DEPOSIT APPROVE
    if (strpos($data_cb,"approve_deposit_")===0 && $uid==$admin){
        list(,, $u,$amt)=explode("_",$data_cb);
        $users[$u]['balance']+=$amt;
        send($u,"✅ Deposit ₦$amt approved");
        answer($cid,"Approved");
    }

    // DEPOSIT REJECT
    if (strpos($data_cb,"reject_deposit_")===0 && $uid==$admin){
        list(,, $u,$amt)=explode("_",$data_cb);
        send($u,"❌ Deposit ₦$amt rejected");
        answer($cid,"Rejected");
    }

    // WITHDRAW APPROVE
    if (strpos($data_cb,"approve_withdraw_")===0 && $uid==$admin){
        list(,, $u,$amt)=explode("_",$data_cb);
        send($u,"✅ Withdrawal ₦$amt approved");
        answer($cid,"Approved");
    }

    // WITHDRAW REJECT
    if (strpos($data_cb,"reject_withdraw_")===0 && $uid==$admin){
        list(,, $u,$amt)=explode("_",$data_cb);
        $users[$u]['balance']+=$amt;
        send($u,"❌ Withdrawal rejected, refunded");
        answer($cid,"Rejected");
    }

    exit;
}

// FORCE JOIN
if ($user_id) {
    if (!isJoined($user_id,$channel) || !isJoined($user_id,$group)) {

        $btn = [
            'inline_keyboard'=>[
                [['text'=>"📢 Join Channel",'url'=>"https://t.me/".str_replace("@","",$channel)]],
                [['text'=>"👥 Join Group",'url'=>"https://t.me/".str_replace("@","",$group)]],
                [['text'=>"✅ I Joined",'callback_data'=>"check_join"]]
            ]
        ];

        sendInline($user_id,"🚫 Join channel & group first!",$btn);
        exit;
    }
}

// REFERRAL
$start_param = explode(" ",$text);
$referrer = $start_param[1] ?? null;

// CREATE USER
if (!isset($users[$user_id])) {
    $users[$user_id]=[
        "balance"=>500,
        "step"=>"",
        "referral"=>$referrer,
        "referrals"=>0,
        "investments"=>[],
        "total_profit"=>0,
        "total_earnings"=>0
    ];
}

// START
if ($text=="/start" || strpos($text,"/start")===0){

    $kb=[
        'keyboard'=>[
            ['💼 Invest','💰 Balance'],
            ['📤 Withdraw','💳 Deposit'],
            ['👥 Referral','📊 Packages'],
            ['📜 History']
        ],
        'resize_keyboard'=>true
    ];

    sendKeyboard($user_id,"💼 Prime Vest Global\n\n💰 Balance: ₦".$users[$user_id]['balance'],$kb);
}

// PACKAGES (UPDATED LIKE IMAGE)
elseif ($text=="📊 Packages"){

send($user_id,
"📊 INVESTMENT PACKAGES

━━━━━━━━━━━━━━━━━━
💼 ₦3,000
📈 25% Daily
💵 Daily Profit: ₦750
💰 Total (60 Days): ₦45,000

💼 ₦5,000
📈 25% Daily
💵 Daily Profit: ₦1,250
💰 Total (60 Days): ₦75,000

💼 ₦10,000
📈 25% Daily
💵 Daily Profit: ₦2,500
💰 Total (60 Days): ₦150,000

💼 ₦15,000
📈 25% Daily
💵 Daily Profit: ₦3,750
💰 Total (60 Days): ₦225,000

💼 ₦20,000
📈 25% Daily
💵 Daily Profit: ₦5,000
💰 Total (60 Days): ₦300,000

💼 ₦25,000
📈 25% Daily
💵 Daily Profit: ₦6,250
💰 Total (60 Days): ₦375,000

💼 ₦40,000
📈 25% Daily
💵 Daily Profit: ₦10,000
💰 Total (60 Days): ₦600,000

💼 ₦50,000
📈 25% Daily
💵 Daily Profit: ₦12,500
💰 Total (60 Days): ₦750,000
━━━━━━━━━━━━━━━━━━");
}

// BALANCE
elseif ($text=="💰 Balance"){
send($user_id,
"💰 Balance: ₦".$users[$user_id]['balance']."
🪙 Profit: ₦".$users[$user_id]['total_profit']."
💸 Earnings: ₦".$users[$user_id]['total_earnings']);
}

// INVEST
elseif ($text=="💼 Invest"){
send($user_id,"Send amount to invest");
}

// DEPOSIT
elseif ($text=="💳 Deposit"){
$users[$user_id]['step']="deposit";
send($user_id,"Enter amount");
}

// WITHDRAW
elseif ($text=="📤 Withdraw"){
$users[$user_id]['step']="withdraw";
send($user_id,"Enter amount");
}

// HANDLE NUMBERS
elseif (is_numeric($text)){

$amount=intval($text);

// DEPOSIT
if ($users[$user_id]['step']=="deposit"){

$btn=[
'inline_keyboard'=>[
[
['text'=>"✅ Approve",'callback_data'=>"approve_deposit_{$user_id}_{$amount}"],
['text'=>"❌ Reject",'callback_data'=>"reject_deposit_{$user_id}_{$amount}"]
]
]
];

sendInline($admin,"Deposit Request\nUser: $user_id\n₦$amount",$btn);
send($user_id,"Waiting approval");
$users[$user_id]['step']="";
}

// WITHDRAW
elseif ($users[$user_id]['step']=="withdraw"){

if ($amount>$users[$user_id]['balance']){
send($user_id,"Insufficient balance");
}else{

$users[$user_id]['balance']-=$amount;

$btn=[
'inline_keyboard'=>[
[
['text'=>"✅ Approve",'callback_data'=>"approve_withdraw_{$user_id}_{$amount}"],
['text'=>"❌ Reject",'callback_data'=>"reject_withdraw_{$user_id}_{$amount}"]
]
]
];

sendInline($admin,"Withdraw Request\nUser: $user_id\n₦$amount",$btn);
send($user_id,"Waiting approval");
$users[$user_id]['step']="";
}
}

// INVEST
elseif (in_array($amount,[3000,5000,10000,15000,20000,25000,40000,50000])){

if ($users[$user_id]['balance']<$amount){
send($user_id,"Insufficient balance");
}else{

$users[$user_id]['balance']-=$amount;

$users[$user_id]['investments'][]=[
"amount"=>$amount,
"time"=>time(),
"paid"=>false
];

send($user_id,"Investment started (60 days)");
}
}
}

// CHECK INVESTMENT
foreach ($users[$user_id]['investments'] as $k=>$inv){

if(!$inv['paid']){

$days=(time()-$inv['time'])/86400;

if($days>=60){

$profit=round($inv['amount']*15);

$users[$user_id]['balance']+=$profit;
$users[$user_id]['total_profit']+=$profit;
$users[$user_id]['total_earnings']+=$profit;

$users[$user_id]['investments'][$k]['paid']=true;

send($user_id,"🎉 Completed ₦".$inv['amount']." → ₦$profit");

// REFERRAL
$ref=$users[$user_id]['referral'];
if($ref && isset($users[$ref])){
$bonus=round($inv['amount']*0.18);
$users[$ref]['balance']+=$bonus;
send($ref,"Referral bonus ₦$bonus");
}
}
}
}

// SAVE
file_put_contents("users.json",json_encode($users));

// FUNCTIONS
function send($id,$msg){
global $api;
file_get_contents($api."sendMessage?chat_id=$id&text=".urlencode($msg));
}
function sendKeyboard($id,$text,$kb){
global $api;
file_get_contents($api."sendMessage?".http_build_query([
'chat_id'=>$id,
'text'=>$text,
'reply_markup'=>json_encode($kb)
]));
}
function sendInline($id,$text,$kb){
global $api;
file_get_contents($api."sendMessage?".http_build_query([
'chat_id'=>$id,
'text'=>$text,
'reply_markup'=>json_encode($kb)
]));
}
function answer($cid,$text){
global $api;
file_get_contents($api."answerCallbackQuery?callback_query_id=$cid&text=$text");
}
