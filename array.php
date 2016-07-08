<?php
$age = array (
        "Peter" => "35",
        "Ben" => "37",
        "Joe" => "43" 
);
$test = array (
        "tom" => "40",
        "arraytest" => $age 
);

$videopattern = '/video/';
$previewpattern = '/videopreview/';

// insert into pre_comments (tid, author, authorid, comment, replyauthor, replyauthorid) values (1, "guoliang", 123456, "looks good to me", "wgl", 321);
$link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );

$query = "select * from pre_comments where tid=1";

$result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );

$comments = array ();
$index = 0;
while ( $row = mysql_fetch_array ( $result ) ) {
    $array = array (
            "author" => $row ['author'],
            "comment" => $row ['comment'],
            "replyauthor" => $row ['replyauthor'] 
    );
    $now = array (
            $index => $array 
    );
    $comments = array_merge ( $comments, $now );
    // print_r($comments);
    $index ++;
}

$cars = array (
        "Volvo",
        "BMW",
        "SAAB" 
);
$cararray = array (
        "cars" => $cars 
);
$comments = array (
        "comments" => $comments 
);

header ( "Content-Type: application/json" );
echo json_encode ( $comments );
function dir_path($path) {
    $path = str_replace ( '\\', '/', $path );
    if (substr ( $path, - 1 ) != '/')
        $path = $path . '/';
    return $path;
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
$path = "/var/www/html/tanqin/data/attachments/DCFA97BE-ECA9-8013-309A-A9CA5260553C";
echo dir_list ( $path );
?>
