<?php

// ローカルと本番でサーバーで切替が必要
    // 冒頭のAccess-Control-Allow-Origin
    // DBの接続先
    // create_header_token

// またフロントサイドをビルドする時にも.envファイルで送信先を切り替える

//header("Access-Control-Allow-Origin:http://digiphone-master.sakura.ne.jp");
header("Access-Control-Allow-Origin:http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// ログ出力設定
ini_set("log_errors", "on");
ini_set("error_log", "error.log");


// header送信
function create_header($token){
    header ("HTTP/1.1 200");
    header("Set-Cookie: token={$token};max-age=132600;Domain=localhost;Path=/shift_mobile;Sime-Site=none; HttpOnly;"); //本番ではsecure（HTTPS限定）も設定すること Sime-Site=Strictにして同ドメインのみとする
    //header("Set-Cookie: token={$token};max-age=132600;Domain=digiphone-master.sakura.ne.jp;Path=/shift_mobile;Sime-Site=Strict; HttpOnly;"); //本番ではsecure（HTTPS限定）も設定すること
}

//DB接続
function db_connect() {

    $dsn = 'mysql:dbname=shift_request;host=localhost;charset=utf8;';
    $user = 'root';
    $pass = '';

    $dbh = new PDO($dsn, $user, $pass);
    error_log('DB接続に成功');

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    return $dbh;
}

//SQL実行
function sql_execute($dbh, $sql, $data){
    $stmt = $dbh->prepare($sql);
    $stmt->execute($data);
    return $stmt;
}


//トークン発行 
function create_user_token($dbh, $empid){
    //ここで認証トークンを発行してDBとブラウザに返す
    $TOKEN_LENGTH = 32;
    $bytes = random_bytes($TOKEN_LENGTH);
    $token = bin2hex($bytes);

    error_log("token". $token);
    
    //DB登録 トークン
    $sql = 'UPDATE kin_employee SET token = :token WHERE empid = :empid ';       
    $data = [':token'=> $token ,':empid'=> $empid];
    $stmt = $dbh->prepare($sql);
    $stmt->execute($data);

    //token削除
    setcookie("token","", time() - 30);
    $_COOKIE['token'] = '';

    return $token;
}
