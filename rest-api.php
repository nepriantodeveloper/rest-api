<?php
session_start();
// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$post = json_decode(file_get_contents('php://input'),true);
if (!$post) $post = array();

// connect to the mysql database
$link = mysqli_connect('localhost', 'root', 'pass', 'dbku');
mysqli_set_charset($link,'utf8');
$token = @$post['token'];
$query = mysqli_query($link,"SELECT * FROM login WHERE token='5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5'");
$ketemu = mysqli_num_rows($query);
if ($ketemu ==true) {
    // retrieve the table and key from the path
    $table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
    $key = array_shift($request)+0;

    // escape the columns and values from the input object
    $columns = preg_replace('/[^a-z0-9_]+/i','',array_keys($post));
    $values = array_map(function ($value) use ($link) {
    if ($value===null) return null;
    return mysqli_real_escape_string($link,(string)$value);
    },array_values($post));

    // build the SET part of the SQL command
    $set = '';
    for ($i=0;$i<count($columns);$i++) {
    $set.=($i>0?',':'').'`'.$columns[$i].'`=';
    $set.=($values[$i]===null?'NULL':'"'.$values[$i].'"');
    }

    // create SQL based on HTTP method
    switch ($method) {
    case 'GET':
        $sql = "select * from `$table`".($key?" WHERE id=$key":''); break;
    case 'PUT':
        $sql = "update `$table` set $set where id=$key"; break;
    case 'POST':
        $sql = "insert into `$table` set $set"; break;
    case 'DELETE':
        $sql = "delete from `$table` where id=$key"; break;
    }

    // execute SQL statement
    $result = mysqli_query($link,$sql);

    // die if SQL statement failed
    if (!$result) {
    http_response_code(404);
    die(mysqli_error($link));
    }

    // print results, insert id or affected row count
    if ($method == 'GET') {
    if (!$key) echo '[';
    for ($i=0;$i<mysqli_num_rows($result);$i++) {
        echo ($i>0?',':'').json_encode(mysqli_fetch_object($result));
    }
    if (!$key) echo ']';
    } elseif ($method == 'POST') {
    echo mysqli_insert_id($link);
    } else {
    echo mysqli_affected_rows($link);
    }

    // close mysql connection
    mysqli_close($link);
}else{
    echo json_encode("maaf belum login kak:)");
}
