<?php
include("./functions.php");

// OPTIONSが最初に送信される
if($_SERVER["REQUEST_METHOD"] === "GET"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("店舗情報取得API////////////////////////////////////////////////////");

try {
    $dbh = db_connect();

    $sql = "SELECT stoid, REPLACE(sto_name, 'タリーズ', '') as sto_name FROM kin_store ";
    $data = [];
    $stmt = sql_execute($dbh, $sql, $data);
    $row = $stmt->fetchAll();

    error_log("row:". print_r($row, true));

    echo json_encode(
        [
            "error" => false,
            "message" => '店舗情報取得成功',
            "store" => $row
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





