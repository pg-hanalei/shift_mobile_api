<?php
include("./functions.php");

// OPTIONSが最初に送信される
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("シフト取得////////////////////////////////////////////////////");

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

//送信内容確認
error_log("POST:". print_r(array($_POST), true));


if(empty($_POST['empid']) && empty($_COOKIE['token'])){
    return;
}


//送信内容サニタイズ
$empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");
$year = htmlspecialchars($_POST['year'], ENT_QUOTES, "UTF-8");
$month = htmlspecialchars($_POST['month'], ENT_QUOTES, "UTF-8");

//2021-12-01 00:00:00
$start = $year . "-" . $month . "-" . "01" . " 00:00:00";
$end = $year . "-" . $month . "-" . "31" . " 23:59:59";

error_log($start);
error_log($end);

if(empty($empid)){
    return;
}

try {
    $dbh = db_connect();

    //SELECT * FROM `kin_shift` WHERE start_time >= '2021-12-01 00:00:00'
    $sql = "SELECT date, start_time, end_time FROM kin_shift ";
    $sql .= "WHERE empid = :empid AND date >= :start AND date <= :end";

    $data = [":empid" => $empid, ":start" => $start, ":end" => $end];

    $stmt = sql_execute($dbh, $sql, $data);

    $row = $stmt->fetchAll();

    error_log("row:". print_r($row, true));

    echo json_encode(
        [
            "error" => false,
            "message" => 'シフト取得成功',
            "shift" => $row
        ]
    );

} catch (PDOException $e){
    error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "通信エラー",
            ]
        );
}





