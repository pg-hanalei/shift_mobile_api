<?php
include('./functions.php');

// OPTIONSが最初に送信される
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // ブラウザからHTMLページを要求された場合
}else{
    return;
}

error_log("ログイン////////////////////////////////////////////////////");
error_log(print_r($_COOKIE, true));

$rest_json = file_get_contents("php://input"); // JSONでPOSTされたデータを取り出す
$_POST = json_decode($rest_json, true); // JSON文字列をデコード

// POST送信 内容
error_log("POST:" . print_r(array($_POST), true));

// empidとpasswordがPOST送信されていれば通常のログイン作業
if (!empty($_POST['empid']) && !empty($_POST['password'])) {
    error_log("通常ログイン");

    //token削除
    setcookie("token","", time() - 30);
    $_COOKIE['token'] = "";

    //送信内容サニタイズ
    $empid = htmlspecialchars($_POST['empid'], ENT_QUOTES, "UTF-8");
    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, "UTF-8");

    //入力チェック
    if(empty($empid) || empty($password)){
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
        $sql = 'SELECT empid, emp_name, emp.stoid, sto_name, password FROM kin_employee emp '; 
        $sql .= 'LEFT JOIN kin_store sto ON emp.stoid = sto.stoid ';
        $sql .= 'WHERE empid = :empid';
        
        // プリペアードステートメント 代入
        $data = [':empid'=> $empid];
        
        // SQL実行
        $stmt = sql_execute($dbh, $sql, $data);

        // SQL実行結果を配列で取得
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // SQL実行結果の配列を確認（この段階ではempidがDBに登録されているのかの確認）
        if(!empty($row)){

            // empidの社員情報 出力
            error_log(print_r($row, true));    

            // ハッシュ化されたDBのパスワードと入力されたパスワードを照合する
            if(empty(password_verify($password, $row["password"]))){

                // パスワード 不一致でログイン画面に返却
                header ("HTTP/1.1 401");
                echo json_encode(
                    [
                        "error" => true,
                        "message" => 'パスワード不一致',
                    ]
                );
                return;

            }else{
                // 照合ＯＫ パスワードは返却するデータから消去
                unset($row['password']);
            }
            
            // 認証トークンを発行してDBに登録
            $token = create_user_token($dbh, $empid);

            //token クッキーにDBに登録したものをブラウザにも返却する
            create_header($token);

            echo json_encode(
                [
                    "error" => false,
                    "message" => '送信成功',
                    "user" => $row
                ]
            );
        }else{
            echo json_encode(
                [
                    "error" => true,
                    "message" => '認証失敗しました',
                ]
            );
        }

    } catch (PDOException $e) {
        error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "通信エラー",
            ]
        );
    }
// ログイン画面からではなく、ページリロードによるtokenのPOST送信
}else if(!empty($_POST['token'])){
  if(empty($_COOKIE['token'])){
    error_log("再取得失敗");
    throw new ErrorException("再取得失敗");
  }

  error_log('$_COOKIE token');
  error_log($_COOKIE['token']);


  try{
    $dbh = db_connect();
        
    $sql = 'SELECT empid, emp_name, emp.stoid, sto_name FROM kin_employee emp '; 
    $sql .= 'LEFT JOIN kin_store sto ON emp.stoid = sto.stoid ';
    $sql .= 'WHERE token = :token';
    
    $data = [':token'=> $_COOKIE['token']];
    
    $stmt = sql_execute($dbh, $sql, $data);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("count".count($row));
    if(count($row) <= 0){
        error_log("再取得失敗");
        throw new ErrorException("再取得失敗");
    }


    //ここで認証トークンを発行してDBとブラウザに返す
    $token = create_user_token($dbh, $row["empid"]);

    //token クッキーを発行
    create_header($token);

    echo json_encode(
        [
            "user" => $row,
            "message" => "再取得成功",
        ]
    );

  }catch(Exception $e){
    error_log('Error:' . $e->getMessage());
        echo json_encode(
            [
                "error" => true,
                "message" => "通信エラー",
            ]
        );
  }
}
