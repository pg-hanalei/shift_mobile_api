<?php
include('./functions.php');

// OPTIONSが最初に送信される
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("ログアウト////////////////////////////////////////////////////");
error_log(print_r($_COOKIE, true));

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

// POST送信 内容
error_log("POST:" . print_r(array($_POST), true));

// empidとpasswordがPOST送信されていれば通常のログイン作業
if (!empty($_POST['empid'])) {

    //送信内容サニタイズ
    $empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");

    try {

        // DB接続
        $dbh = db_connect();

        // 認証トークンを新規発行してDBに登録 古いtokenがブラウザに残ってもログイン不可
        $token = create_user_token($dbh, $empid);

        create_header("");

        echo json_encode(
            [
                "error" => false,
                "message" => 'ログアウト成功',
            ]
        );

    } catch (PDOException $e) {
        error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "通信エラー",
            ]
        );
    }
}
