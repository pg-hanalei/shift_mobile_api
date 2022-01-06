<?php
include("./functions.php");

// OPTIONSが最初に送信される origin確認
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("シフト登録・編集////////////////////////////////////////////////////");

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

//送信内容確認
error_log("POST:". print_r(array($_POST), true));

// 社員番号、トークン、登録種類（新規orアップデート）が無ければ終了
if(empty($_POST['empid']) && empty($_COOKIE['token'] && empty($_POST['register_kind']))){
    return;
}

// 送信内容サニタイズ 社員番号 日付 時間
$empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");
$date = htmlspecialchars($_POST['date'], ENT_QUOTES, "UTF-8");
$stoid = htmlspecialchars($_POST['stoid'], ENT_QUOTES, "UTF-8");   

if($_POST['register_kind'] !== 3){
    $start_time = htmlspecialchars($_POST['start_time'], ENT_QUOTES, "UTF-8");
    $end_time = htmlspecialchars($_POST['end_time'], ENT_QUOTES, "UTF-8");
}

$register_kind = htmlspecialchars($_POST['register_kind'], ENT_QUOTES, "UTF-8");

try {
    $dbh = db_connect();

    // 締め日を取得して、対象の日付($date)が登録可能か確認する //stoidもPOST送信する
    $sql = "SELECT `id`, `stoid`, `year`, `month`, `period`, `created_at` FROM `kin_shime` WHERE stoid = :stoid ORDER BY year DESC, month DESC, period DESC LIMIT 1";
    $data = [":stoid" => $stoid];
    $stmt = sql_execute($dbh, $sql, $data);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log(print_r($row, true));

    $shime = $row['year'].'-'.$row['month'];

    if($row['period'] === "1"){
        $shime .= "-"."16";
    }else{
        $shime .= "-"."31";
    }

    error_log($shime);
    error_log(strtotime($date));
    error_log(strtotime($shime));

    if(strtotime($date) < strtotime($shime)) {
        header ("HTTP/1.1 200");
        error_log("締め切り");

        echo json_encode( [
            "error" => true,
            "message" => '本期間は締め切りました',
        ]);
        return;
    }

    // 新規
    if($register_kind === "1"){
    
        $sql = "INSERT INTO `kin_shift`(`empid`, `date`, `stoid`, `start_time`, `end_time`, `updated_at`, `created_at`) VALUES ";
        $sql .= "(:empid, :date, :stoid, :start_time, :end_time, :updated_at, :created_at )";

        $data = [":empid" => $empid, ":date" => $date, ":stoid" => $stoid, ":start_time" => $start_time, ":end_time" => $end_time, ":updated_at" => date("Y/m/d H:i:s"), ":created_at" => date("Y/m/d H:i:s")];
    
    }elseif($register_kind === "2"){
        
        // 更新
        $sql = "UPDATE kin_shift SET start_time = :start_time, end_time = :end_time, updated_at = :updated_at ";
        $sql .= "WHERE empid = :empid AND date = :date";

        $data = [":empid" => $empid, ":date" => $date, ":start_time" => $start_time, ":end_time" => $end_time, ":updated_at" => date("Y/m/d H:i:s")];

    }elseif($register_kind === "3"){

        //削除
        $sql = "DELETE FROM kin_shift WHERE empid = :empid AND date = :date";

        $data = [":empid" => $empid, ":date" => $date];
    }
    
    $stmt = sql_execute($dbh, $sql, $data);

    echo json_encode(
        [
            "error" => false,
            "message" => 'シフト登録成功',
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





