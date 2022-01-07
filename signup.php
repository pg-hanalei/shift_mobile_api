<?php
include('./functions.php');

// OPTIONSが最初に送信される
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ブラウザからHTMLページを要求された場合
} else {
    return;
}

error_log("新規ユーザー登録////////////////////////////////////////////////////");

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

// POST送信 内容
error_log("POST:" . print_r(array($_POST), true));

// empidとpasswordがPOST送信されていれば通常のログイン作業
if (!empty($_POST['auth_signup_password'])) {
    error_log("ユーザー新規登録開始");

    $auth_signup_password = htmlspecialchars($_POST['auth_signup_password'], ENT_QUOTES, "UTF-8");

    if($auth_signup_password !== "4649"){
        echo json_encode(
            [
                "error" => true,
                "message" => '登録者確認 不一致',
            ]
        );
        return;
    }


    // 送信内容サニタイズ
    $empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");
    $emp_name = htmlspecialchars($_POST['emp_name'], ENT_QUOTES, "UTF-8");
    $stoid = htmlspecialchars($_POST['stoid'], ENT_QUOTES, "UTF-8");
    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, "UTF-8");
    

    // パスワード ハッシュ化  bcrypt アルゴリズム
    $password = password_hash($password, PASSWORD_DEFAULT);

    // 入力チェック
    if (empty($empid) || empty($password) || empty($stoid) || empty($emp_name)) {
        echo json_encode(
            [
                "error" => true,
                "message" => '入力必須です',
            ]
        );
        return;
    }

    error_log("入力チェック完了");

    try {

        // DB接続
        $dbh = db_connect();

        // SQL文作成
        $sql = 'INSERT INTO `kin_employee`(`empid`, `stoid`, `emp_name`, `password`, `updated_at`, `created_at`)';
        $sql .= ' VALUES (:empid, :stoid, :emp_name, :password, :updated_at, :created_at) ';

        // プリペアードステートメント 代入
        $data = [
            ':empid' => $empid, ':stoid' => $stoid, ':emp_name' => $emp_name, ':password' => $password,
            ':updated_at' => date("Y/m/d H:i:s"), ':created_at' => date("Y/m/d H:i:s")
        ];

        // SQL実行
        $stmt = sql_execute($dbh, $sql, $data);
        error_log(print_r($stmt, true));

        // SQL実行結果の配列を確認（この段階ではempidがDBに登録されているのかの確認）
        if ($stmt) {

            // 認証トークンを発行してDBに登録
            $token = create_user_token($dbh, $empid);

            echo json_encode(
                [
                    "error" => false,
                    "message" => '新規ユーザー登録成功',
                ]
            );
        } else {
            echo json_encode(
                [
                    "error" => true,
                    "message" => '新規ユーザー登録失敗',
                ]
            );
        }
    } catch (PDOException $e) {
        error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "新規ユーザー登録失敗",
            ]
        );
    }
}
