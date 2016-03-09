<?php
// assume JSON, handle requests by verb and path
$verb = $_SERVER ['REQUEST_METHOD'];
$debug = TRUE;

$positivepath = "/var/www/html/tanqin";
$userpicdir = "/var/www/html/tanqin/data/user/";

switch ($verb) {
    case 'GET' :
        break;
    // two cases so similar we'll just share code
    case 'POST' :
    case 'PUT' :
        $action = $_GET ["action"];
        if ($action == "register") {
            // new create one comments
            $username = $_POST ['uname'];
            $password = $_POST ['userpassword'];
            $email = $_POST ['email'];
            
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            // Check if username, email has been register
            $query = "select * from pre_user_tbl where username='$username' or email='$email'";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            if (mysql_num_rows ( $result ) == 0) {
                $query = "insert into pre_user_tbl (username, email, password) values ('$username', '$email', '$password')";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                $query = "select userid from pre_user_tbl where username='$username'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                
                while ( $row = mysql_fetch_array ( $result ) ) {
                    $data = array (
                            'uid' => $row ['userid'] 
                    );
                }
            } else {
                $data = array (
                        'uid' => "-1" 
                );
            }
            
            mysql_close ( $link );
        } else if ($action == "login") {
            $username = $_POST ['uname'];
            $password = $_POST ['userpassword'];
            
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            // Check if username, email has been register
            $query = "select * from pre_user_tbl where username='$username' and password='$password'";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            if (mysql_num_rows ( $result ) == 0) {
                $data = array (
                        'uid' => "-1" 
                );
            } else {
                while ( $row = mysql_fetch_array ( $result ) ) {
                    $data = array (
                            "uid" => $row ['userid'],
                            "uname" => $row ['username'],
                            "uprofile" => substr ( $row ['userpic'], strlen ( $positivepath ) ) 
                    );
                }
            }
        } else if ($action == "createheadportrait") {
            // new create one comments
            $userid = $_POST ['userid'];
            $userpicpath = $userpicdir . $userid . "/";
            $userpic = $userpicdir . $userid . "/" . $userid . ".jpg";
            
            if (isset ( $_FILES ['userpic'] )) {
                mkdir ( $userpicpath );
                move_uploaded_file ( $_FILES ['userpic'] ["tmp_name"], $userpic );
            }
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            $userquery = "update pre_user_tbl set userpic = '$userpic' where userid = '$userid'";
            $result = mysql_query ( $userquery ) or die ( 'Query failed: ' . mysql_error () );
            $data = array (
                    'result' => "success" 
            );
            mysql_close ( $link );
        }
        break;
    case 'DELETE' :
        echo "processing delete\n";
    default :
        throw new Exception ( 'Method Not Supported', 405 );
}

// this is the output handler
header ( "Content-Type: application/json" );
echo json_encode ( $data );

/**
 * 列出目录下的所有文件
 *
 * @param str $path
 *            目录
 * @param str $exts
 *            后缀
 * @param array $list
 *            路径数组
 * @return array 返回路径数组
 *        
 */
function dir_list($path, $exts = '', $list = array()) {
    $path = dir_path ( $path );
    $files = glob ( $path . '*' );
    foreach ( $files as $v ) {
        if (! $exts || preg_match ( "/\.($exts)/i", $v )) {
            $list [] = $v;
            if (is_dir ( $v )) {
                $list = dir_list ( $v, $exts, $list );
            }
        }
    }
    return $list;
}
function dir_path($path) {
    $path = str_replace ( '\\', '/', $path );
    if (substr ( $path, - 1 ) != '/')
        $path = $path . '/';
    return $path;
}

?>

