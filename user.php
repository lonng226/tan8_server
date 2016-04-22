<?php
// assume JSON, handle requests by verb and path
$verb = $_SERVER ['REQUEST_METHOD'];
$debug = TRUE;

$positivepath = "/var/www/html/tanqin";
$userpicdir = "/var/www/html/tanqin/data/user/";

switch ($verb) {
    case 'GET' :
        $uid = $_GET ["uid"];
        $type = $_GET ["type"];
        
        // Connect to database
        $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
        mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
        
        if ($type == "self") {
            $query = "select userpic, username from pre_user_tbl where userid = $uid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            if (mysql_num_rows ( $result ) == 0) {
                $data = array (
                        'result' => "No such user, please check the uid." 
                );
            } else {
                $rows = mysql_fetch_array ( $result );
                
                $data = array (
                        'userpic' => process_userpic ( $rows [0] ),
                        'username' => $rows [1],
                        'sumpost' => get_sumpost_from_uid ( $uid ),
                        'sumfollowers' => get_sumfollowers_from_uid ( $uid ),
                        'sumattentions' => get_sumattentions_from_uid ( $uid ) 
                );
            }
        } elseif ($type == "another") {
            $opuid = $_GET ["opuid"];
            $query = "select userpic, username from pre_user_tbl where userid = $opuid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            if (mysql_num_rows ( $result ) == 0) {
                $data = array (
                        'result' => "No such user, please check the uid." 
                );
            } else {
                $rows = mysql_fetch_array ( $result );
                $userpic = process_userpic ( $rows [0] );
                $username = $rows [1];
                
                $query = "select * from pre_attention_tbl where uid = $opuid and followerid = $uid";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                
                $attentioned = false;
                if (mysql_num_rows ( $result ) != 0) {
                    $attentioned = true;
                }
                
                $data = array (
                        'userpic' => $userpic,
                        'username' => $username,
                        'attentioned' => $attentioned,
                        'sumpost' => get_sumpost_from_uid ( $opuid ),
                        'sumfollowers' => get_sumfollowers_from_uid ( $opuid ),
                        'sumattentions' => get_sumattentions_from_uid ( $opuid ) 
                );
            }
        } else if ($type == "followers") {
            $query = "select followerid from pre_attention_tbl where uid = $uid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            $followers = array ();
            $index = 0;
            
            while ( $row = mysql_fetch_array ( $result ) ) {
                $followerid = $row ['followerid'];
                $userinfo = get_userinfo_from_uid ( $followerid );
                
                $array = array (
                        'followerid' => $followerid,
                        'userpic' => $userinfo ['userpic'],
                        'username' => $userinfo ['username'] 
                );
                
                $now = array (
                        $index => $array 
                );
                $followers = array_merge ( $followers, $now );
                // print_r($comments);
                $index ++;
            }
            
            $data = $followers;
        } else if ($type == "attentions") {
            $query = "select uid from pre_attention_tbl where followerid = $uid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            $attentions = array ();
            $index = 0;
            
            while ( $row = mysql_fetch_array ( $result ) ) {
                $attentionid = $row ['uid'];
                $userinfo = get_userinfo_from_uid ( $attentionid );
                
                $array = array (
                        'attentionid' => $attentionid,
                        'userpic' => $userinfo ['userpic'],
                        'username' => $userinfo ['username'] 
                );
                
                $now = array (
                        $index => $array 
                );
                $attentions = array_merge ( $attentions, $now );
                // print_r($comments);
                $index ++;
            }
            
            $data = $attentions;
        } else if ($type == "sumpost") {
            $query = "select uptbl.sumup, posttbl.userid, posttbl.userpic, posttbl.username, posttbl.sumpost from 
                    (select sum(sumup) as sumup, temp.authorid as userid from 
                    (select count(tid) as sumup, tid, authorid from pre_up_tbl group by tid) as temp group by temp.authorid) 
                    as uptbl right join 
                    (select a.sumpost, b.* from 
                    (select count(tid) as sumpost, authorid as uid from pre_forum_post group by authorid) 
                    AS a right join pre_user_tbl AS b ON a.uid = b.userid) 
                    as posttbl on uptbl.userid = posttbl.userid 
                    order by sumpost desc";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            $statistics = array ();
            $index = 0;
            
            while ( $row = mysql_fetch_array ( $result ) ) {
                $array = array (
                        'sumpost' => $row ['sumpost'],
                        'userid' => $row ['userid'],
                        'userpic' => process_userpic ( $row ['userpic'] ),
                        'username' => $row ['username'],
                        'sumup' => $row ['sumup'] 
                );
                
                $now = array (
                        $index => $array 
                );
                $statistics = array_merge ( $statistics, $now );
                // print_r($comments);
                $index ++;
            }
            
            $data = $statistics;
        }
        mysql_close ( $link );
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
                    $uprofile = $row ['userpic'];
                    if (is_null ( $uprofile )) {
                        $uprofile = "";
                    } else {
                        $uprofile = substr ( $row ['userpic'], strlen ( $positivepath ) );
                    }
                    
                    $data = array (
                            "uid" => $row ['userid'],
                            "uname" => $row ['username'],
                            "uprofile" => $uprofile 
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
        } elseif ($action == "attention") {
            $followerid = $_POST ['uid'];
            $attentionid = $_POST ['attentionid'];
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            // Check if this uid has paid attention to this attentionid
            $query = "select * from pre_attention_tbl where followerid = $followerid and uid = $attentionid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            if (mysql_num_rows ( $result ) == 0) {
                $query = "insert into pre_attention_tbl (uid, followerid) values ($attentionid, $followerid)";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            }
            
            $data = array (
                    'result' => "success" 
            );
            
            mysql_close ( $link );
        }
        break;
    case 'DELETE' :
        $type = $_GET ["type"];
        $uid = $_GET ["uid"];
        $attentionid = $_GET ["attentionid"];
        
        // Connect to database
        $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
        mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
        
        if ($type == "attention") {
            $uid = $_GET ["uid"];
            $attentionid = $_GET ["attentionid"];
            
            $query = "delete from pre_attention_tbl where followerid = $uid and uid = $attentionid";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            $data = array (
                    'result' => "success" 
            );
        }
        
        mysql_close ( $link );
        break;
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

/**
 * 由uid得到用户信息头像路径和username
 *
 * @param str $uid
 *            用户id
 * @return array 头像路径和username
 *        
 */
function get_userinfo_from_uid($uid) {
    $query = "select userpic, username from pre_user_tbl where userid = $uid";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    $positivepath = "/var/www/html/tanqin";
    $rows = mysql_fetch_array ( $result );
    $userpic = $rows [0];
    if (! isset ( $userpic )) {
        $data = array (
                'userpic' => "",
                'username' => $rows [1] 
        );
    } else {
        $userpic = substr ( $userpic, strlen ( $positivepath ) );
        $data = array (
                'userpic' => $userpic,
                'username' => $rows [1] 
        );
    }
    return $data;
}
function process_userpic($userpic) {
    $positivepath = "/var/www/html/tanqin";
    if (! isset ( $userpic )) {
        return "";
    } else {
        $userpic = substr ( $userpic, strlen ( $positivepath ) );
        return $userpic;
    }
}

/**
 * 由uid得到用户发帖总数
 *
 * @param str $uid
 *            用户id
 * @return 发帖总数
 *
 */
function get_sumpost_from_uid($uid) {
    $query = "select count(tid) as sumpost, authorid from pre_forum_post where authorid = $uid";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    
    $rows = mysql_fetch_array ( $result );
    
    return $rows [0];
}

/**
 * 由uid得到用户粉丝数
 *
 * @param str $uid
 *            用户id
 * @return 粉丝数
 *
 */
function get_sumfollowers_from_uid($uid) {
    $query = "select count(followerid) as sumfollowers from pre_attention_tbl where uid = $uid";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    
    $rows = mysql_fetch_array ( $result );
    
    return $rows [0];
}

/**
 * 由uid得到用户关注数
 *
 * @param str $uid
 *            用户id
 * @return 关注数
 *
 */
function get_sumattentions_from_uid($uid) {
    $query = "select count(uid) as sum from pre_attention_tbl where followerid = $uid";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    
    $rows = mysql_fetch_array ( $result );
    
    return $rows [0];
}

?>

