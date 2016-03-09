<?php
// assume JSON, handle requests by verb and path
$verb = $_SERVER ['REQUEST_METHOD'];
$debug = TRUE;

$storage = "/var/www/html/tanqin/data/attachments/";
$positivepath = "/var/www/html/tanqin";
$userpicdir = "/var/www/html/tanqin/data/user/";

switch ($verb) {
    case 'GET' :
        $tid = $_GET ["tid"];
        $fid = $_GET ["fid"];
        $frompage = $_GET ["from"];
        $topage = $_GET ["to"];
        $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
        mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
        
        if ($tid == null) {
            if ($frompage == null && $topage == null) {
                $frompage = 0;
                $topage = 20;
            }
            $offset = $topage - $frompage;
            
            if ($fid == null) {
                $query = "select tid, fid, authorid, author, message from pre_forum_post order by position desc LIMIT $frompage, $offset";
            } else {
                $query = "select tid, fid, authorid, author, message from pre_forum_post where fid=$fid order by position desc LIMIT $frompage, $offset";
            }
            $tinfo = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            
            $data = array ();
            $tindex = 0;
            
            while ( $row = mysql_fetch_array ( $tinfo ) ) {
                $tid = $row ['tid'];
                
                // Generate comments
                $comments = get_comments_from_tid ( $tid );
                
                // Generate ups
                $up = get_up_from_tid ( $tid );
                
                // Generate attachments
                $attachmentquery = "select * from pre_attachments where tid = $tid";
                $attachmentresult = mysql_query ( $attachmentquery ) or die ( 'Query failed: ' . mysql_error () );
                
                // Generate pics and video
                $pictures = array ();
                $videopath = "";
                $previewimage = "";
                $index = 0;
                $videopattern = '/video/';
                $previewpattern = '/videopreview/';
                
                while ( $attachmentrow = mysql_fetch_array ( $attachmentresult ) ) {
                    $path = $attachmentrow ['dir'];
                    $attachfiles = dir_list ( $path );
                    $arrlength = count ( $attachfiles );
                    for($x = 0; $x < $arrlength; $x ++) {
                        $file = $attachfiles [$x];
                        if (is_dir ( $file ) || preg_match ( $videopattern, $file ) || preg_match ( $previewpattern, $file )) {
                            if ($file == $path . "video") {
                                $videopath = dir_list ( $file ) [0];
                                $videopath = substr ( $videopath, strlen ( $positivepath ) );
                            }
                            if ($file == $path . "videopreview") {
                                $previewimage = dir_list ( $file ) [0];
                                $previewimage = substr ( $previewimage, strlen ( $positivepath ) );
                            }
                        } else {
                            $now = array (
                                    $index => substr ( $file, strlen ( $positivepath ) ) 
                            );
                            $pictures = array_merge ( $pictures, $now );
                            $index ++;
                        }
                    }
                }
                
                $array = array (
                        "tid" => $row ['tid'],
                        "fid" => $row ['fid'],
                        "authorid" => $row ['authorid'],
                        "authorname" => $row ['author'],
                        "message" => $row ['message'],
                        "comments" => $comments,
                        "up" => $up,
                        "videopath" => $videopath,
                        "previewimage" => $previewimage,
                        "pics" => $pictures 
                );
                
                $now = array (
                        $tindex => $array 
                );
                $data = array_merge ( $data, $now );
                $tindex ++;
            }
        } else {
            $tinfoquery = "select tid, fid, authorid,author, message from pre_forum_post where tid = $tid";
            $attachmentquery = "select * from pre_attachments where tid = $tid";
            
            $tinfo = mysql_query ( $tinfoquery ) or die ( 'Query failed: ' . mysql_error () );
            $attachmentresult = mysql_query ( $attachmentquery ) or die ( 'Query failed: ' . mysql_error () );
            
            // Generate comments
            $comments = get_comments_from_tid ( $tid );
            
            // Generate ups
            $up = get_up_from_tid ( $tid );
            
            // Generate pics and video
            $pictures = array ();
            $videopath = "";
            $previewimage = "";
            $index = 0;
            $videopattern = '/video/';
            $previewpattern = '/videopreview/';
            
            while ( $attachmentrow = mysql_fetch_array ( $attachmentresult ) ) {
                $path = $attachmentrow ['dir'];
                $attachfiles = dir_list ( $path );
                $arrlength = count ( $attachfiles );
                for($x = 0; $x < $arrlength; $x ++) {
                    $file = $attachfiles [$x];
                    if (is_dir ( $file ) || preg_match ( $videopattern, $file ) || preg_match ( $previewpattern, $file )) {
                        if ($file == $path . "video") {
                            $videopath = dir_list ( $file ) [0];
                            $videopath = substr ( $videopath, strlen ( $positivepath ) );
                        }
                        if ($file == $path . "videopreview") {
                            $previewimage = dir_list ( $file ) [0];
                            $previewimage = substr ( $previewimage, strlen ( $positivepath ) );
                        }
                    } else {
                        $now = array (
                                $index => substr ( $file, strlen ( $positivepath ) ) 
                        );
                        $pictures = array_merge ( $pictures, $now );
                        $index ++;
                    }
                }
            }
            while ( $row = mysql_fetch_array ( $tinfo ) ) {
                $array = array (
                        "tid" => $row ['tid'],
                        "fid" => $row ['fid'],
                        "authorid" => $row ['authorid'],
                        "authorname" => $row ['author'],
                        "message" => $row ['message'],
                        "comments" => $comments,
                        "up" => $up,
                        "videopath" => $videopath,
                        "previewimage" => $previewimage,
                        "pics" => $pictures 
                );
            }
            
            $data = $array;
        }
        
        mysql_close ( $link );
        break;
    // two cases so similar we'll just share code
    case 'POST' :
    case 'PUT' :
        $action = $_GET ["action"];
        if ($action == "newcomment") {
            // Send one comments
            $tid = $_POST ['tid'];
            $userid = $_POST ['userid'];
            $username = $_POST ['username'];
            $comment = $_POST ['comment'];
            $replyauthor = $_POST ['replyauthor'];
            $replyauthorid = $_POST ['replyauthorid'];
            
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            $insertcommentquery = "insert into pre_comments (tid, authorid, author, comment, replyauthor, replyauthorid) values ('$tid', '$userid','$username', '$comment', '$replyauthor','$replyauthorid')";
            $result = mysql_query ( $insertcommentquery ) or die ( 'Query failed: ' . mysql_error () );
            $data = array (
                    'result' => "success" 
            );
            mysql_close ( $link );
        } elseif ($action == "newup") {
            // click up
            $tid = $_POST ['tid'];
            $userid = $_POST ['userid'];
            $username = $_POST ['username'];
            
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            
            $insertupquery = "insert into pre_up_tbl (tid, authorid, author) values ('$tid', '$userid','$username')";
            $selectupquery = "select * from pre_up_tbl where tid = $tid and authorid = $userid and author= '$username'";
            $removeupquery = "delete from pre_up_tbl where tid = $tid and authorid = $userid and author= '$username'";
            $result = mysql_query ( $selectupquery ) or die ( 'Query failed: ' . mysql_error () );
            $row = mysql_fetch_array ( $result );
            
            if (empty ( $row )) {
                $result = mysql_query ( $insertupquery ) or die ( 'Query failed: ' . mysql_error () );
            } else {
                $result = mysql_query ( $removeupquery ) or die ( 'Query failed: ' . mysql_error () );
            }
            
            $data = array (
                    'result' => "success" 
            );
            mysql_close ( $link );
        } else {
            
            $author = $_POST ['author'];
            $authorid = $_POST ['authorid'];
            $fid = $_POST ['fid'];
            $message = $_POST ['message'];
            
            // Connect to database
            $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
            $tidquery = "select count(*) from pre_forum_post";
            $tidrow = mysql_fetch_array ( mysql_query ( $tidquery ) ) or die ( 'Query failed: ' . mysql_error () );
            $tid = $tidrow [0];
            
            $key = create_guid ();
            $attachmentdir = $storage . $key . "/";
            
            for($num = 1; $num <= 6; $num ++) {
                $filename = "pic" . $num;
                if (isset ( $_FILES [$filename] )) {
                    mkdir ( $attachmentdir );
                    move_uploaded_file ( $_FILES [$filename] ["tmp_name"], $attachmentdir . $_FILES [$filename] ["name"] );
                } else {
                    break;
                }
            }
            
            $video = $_FILES ['video'];
            $videopreviewimage = $_FILES ['videopreviewimage'];
            
            if (isset ( $video )) {
                if (file_exists ( $attachmentdir )) {
                    mkdir ( $attachmentdir . "video/" );
                    move_uploaded_file ( $video ["tmp_name"], $attachmentdir . "video/" . $video ["name"] );
                } else {
                    mkdir ( $attachmentdir );
                    mkdir ( $attachmentdir . "video/" );
                    move_uploaded_file ( $video ["tmp_name"], $attachmentdir . "video/" . $video ["name"] );
                }
            }
            
            if (isset ( $videopreviewimage )) {
                if (file_exists ( $attachmentdir )) {
                    mkdir ( $attachmentdir . "videopreview/" );
                    move_uploaded_file ( $videopreviewimage ["tmp_name"], $attachmentdir . "videopreview/" . $videopreviewimage ["name"] );
                } else {
                    mkdir ( $attachmentdir );
                    mkdir ( $attachmentdir . "videopreview/" );
                    move_uploaded_file ( $videopreviewimage ["tmp_name"], $attachmentdir . "videopreview/" . $videopreviewimage ["name"] );
                }
            }
            
            $attachmentexisting = false;
            if (file_exists ( $attachmentdir )) {
                $attachmentexisting = true;
            }
            
            $query = "insert into pre_forum_post (author, authorid, fid, message, tid, attachment) VALUES ('$author', '$authorid', '$fid', '$message', '$tid', '$attachmentexisting')";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            $data = array (
                    'tid' => $tid 
            );
            
            if ($attachmentexisting == true) {
                $query = "insert into pre_attachments (tid, dir) values ('$tid', '$attachmentdir')";
                mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            }
            
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
 * 生成永远唯一的激活码
 *
 * @return string
 *
 */
function create_guid($namespace = null) {
    static $guid = '';
    $uid = uniqid ( "", true );
    
    $data = $namespace;
    $data .= $_SERVER ['REQUEST_TIME']; // 请求那一刻的时间戳
    $data .= $_SERVER ['HTTP_USER_AGENT']; // 获取访问者在用什么操作系统
    $data .= $_SERVER ['SERVER_ADDR']; // 服务器IP
    $data .= $_SERVER ['SERVER_PORT']; // 端口号
    $data .= $_SERVER ['REMOTE_ADDR']; // 远程IP
    $data .= $_SERVER ['REMOTE_PORT']; // 端口信息
    
    $hash = strtoupper ( hash ( 'ripemd128', $uid . $guid . md5 ( $data ) ) );
    $guid = substr ( $hash, 0, 8 ) . '-' . substr ( $hash, 8, 4 ) . '-' . substr ( $hash, 12, 4 ) . '-' . substr ( $hash, 16, 4 ) . '-' . substr ( $hash, 20, 12 );
    
    return $guid;
}

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
 * 由tid得到所有的这个tid的评论
 *
 * @param str $tid
 *            帖子id
 * @return array 返回评论数组
 *        
 */
function get_comments_from_tid($tid) {
    $commentquery = "select * from pre_comments where tid = $tid";
    $commentresult = mysql_query ( $commentquery ) or die ( 'Query failed: ' . mysql_error () );
    
    // Generate comments
    $comments = array ();
    $index = 0;
    while ( $row = mysql_fetch_array ( $commentresult ) ) {
        $array = array (
                "authorname" => $row ['author'],
                "authorid" => $row ['authorid'],
                "message" => $row ['comment'],
                "replyauthorname" => $row ['replyauthor'],
                "replyauthorid" => $row ['replyauthorid'] 
        );
        $now = array (
                $index => $array 
        );
        $comments = array_merge ( $comments, $now );
        // print_r($comments);
        $index ++;
    }
    
    return $comments;
}
/**
 * 由tid得到所有的这个tid的点赞
 *
 * @param str $tid
 *            帖子id
 * @return array 返回点赞数组
 *        
 */
function get_up_from_tid($tid) {
    $upquery = "select * from pre_up_tbl where tid = $tid";
    $upresult = mysql_query ( $upquery ) or die ( 'Query failed: ' . mysql_error () );
    
    $up = array ();
    $index = 0;
    while ( $row = mysql_fetch_array ( $upresult ) ) {
        $array = array (
                "authorname" => $row ['author'],
                "authorid" => $row ['authorid'] 
        );
        $now = array (
                $index => $array 
        );
        $up = array_merge ( $up, $now );
        $index ++;
    }
    
    return $up;
}

?>

