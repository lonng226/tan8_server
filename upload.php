<?php
$ds = DIRECTORY_SEPARATOR; // 1
$storeFolder = 'uploads'; // 2
$path = $_POST ["path"];
$name = $_POST ["name"];
$storeFolder = $storeFolder . $ds . $path . $ds . $name;
if (! empty ( $_FILES )) {
    $tempFile = $_FILES ['file'] ['tmp_name']; // 3
    $targetPath = dirname ( __FILE__ ) . $ds . $storeFolder . $ds; // 4
    
    mkdir ( $targetPath );
    $targetFile = $targetPath . $_FILES ['file'] ['name']; // 5
    move_uploaded_file ( $tempFile, $targetFile ); // 6
    if ($path == "����") {
        write_melody_to_db ( $path, $name, $_FILES ['file'] ['name'] );
    } else {
        compressfile ( $_FILES ['file'] ['name'], $targetPath );
        write_file_to_db ( $path, $name, $_FILES ['file'] ['name'] );
    }
}
/**
 * Write the data to database
 *
 * @param str $type
 *            type
 *            str $albumname
 *            albumname
 *            str $filename
 *            filename
 */
function write_file_to_db($type, $albumname, $filename) {
    // Connect to database
    $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
    mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
    // �������ݵ��ַ���utf-8
    mysql_query ( "set names 'utf8' " );
    mysql_query ( "set character_set_client=utf8" );
    mysql_query ( "set character_set_results=utf8" );
    
    $query = "select id from pre_album_tbl where type = '$type' and name = '$albumname'";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    
    if (mysql_num_rows ( $result ) == 0) {
        $insertquery = "insert into pre_album_tbl (type, name) values ('$type', '$albumname')";
        $result = mysql_query ( $insertquery ) or die ( 'Query failed: ' . mysql_error () );
        $lastquery = "SELECT LAST_INSERT_ID();";
        $result = mysql_query ( $lastquery ) or die ( 'Query failed: ' . mysql_error () );
        $row = mysql_fetch_array ( $result );
        $aid = $row [0];
    } else {
        $row = mysql_fetch_array ( $result );
        $aid = $row [0];
    }
    if (isset ( $aid )) {
        $query = "insert into pre_track_tbl (aid, name, type, albumname) values ('$aid', '$filename', '$type', '$albumname')";
        $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    }
    mysql_close ( $link );
}
/**
 * Write the data to database
 *
 * @param str $type
 *            type
 *            str $melodyname
 *            melodyname
 *            str $filename
 *            filename
 */
function write_melody_to_db($type, $path, $filename) {
    // Connect to database
    $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
    mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
    // �������ݵ��ַ���utf-8
    mysql_query ( "set names 'utf8' " );
    mysql_query ( "set character_set_client=utf8" );
    mysql_query ( "set character_set_results=utf8" );
    
    $query = "insert into pre_melody_tbl (name, type, path) values ('$filename', '$type', '$path')";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    
    mysql_close ( $link );
}
/**
 * Compress file.
 *
 * @param
 *            filename targetfile
 *            path targetpath
 */
function compressfile($filename, $path) {
    // This process is to avoid file name has space.
    $file = "\"" . $filename . "\"";
    $fileorg = "\"" . $filename . "." . "org" . "\"";
    
    $targetfile = $path . $file;
    $targetfileorg = $path . $fileorg;
    $copycmd = "cp " . $targetfile . " " . $targetfileorg;
    shell_exec ( $copycmd );
    $compresscmd = "/home/guoliawa/ffmpeg/ffmpeg -y -i " . $targetfileorg . " -b:v 400k -r 15 -movflags faststart " . $targetfile;
    shell_exec ( $compresscmd );
    $rmcmd = "rm " . $targetfileorg;
    shell_exec ( $rmcmd );
}
?>