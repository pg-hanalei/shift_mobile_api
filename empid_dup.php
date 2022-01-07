<?php
include("./functions.php");

// OPTIONSが最初に送信される
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("社員番号重複チェック////////////////////////////////////////////////////");

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

//送信内容サニタイズ
$empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");

if(empty($_POST['empid'])){
    return;
}

try {

    $dbh = db_connect();

    $sql = "SELECT empid FROM kin_employee ";
    $sql .= "WHERE empid = :empid";
    $data = [":empid" => $empid];
    $stmt = sql_execute($dbh, $sql, $data);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("row:". print_r($row, true));

    if($row){
        echo json_encode(
            [
                "error" => true,
                "message" => '登録済み番号',
                "store" => $row["empid"]
            ]
        );
    }else{
        echo json_encode(
            [
                "error" => false,
                "message" => '未登録番号',
            ]
        );
    }
} catch (PDOException $e){
    error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "通信エラー",
            ]
        );
}





