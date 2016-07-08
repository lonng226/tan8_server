<?php
// assume JSON, handle requests by verb and path
$verb = $_SERVER ['REQUEST_METHOD'];
$debug = TRUE;

$basepath = "http://120.24.16.24/tanqin/uploads/";
$ds = DIRECTORY_SEPARATOR;
$link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );
// 设置数据的字符集utf-8
mysql_query ( "set names 'utf8' " );
mysql_query ( "set character_set_client=utf8" );
mysql_query ( "set character_set_results=utf8" );

$type = $_GET ["type"];
$album = $_GET ["album"];
$albumid = $_GET ["albumid"];
$trackid = $_GET ["trackid"];
$method = $_GET ["method"];

switch ($verb) {
    case 'GET' :
        $albumlist = array ();
        $tracklist = array ();
        $index = 0;
        
        if ($method == "delete") {
            if (isset ( $albumid )) {
                $query = "select * from pre_album_tbl where id = '$albumid'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                if (mysql_num_rows ( $result ) != 0) {
                    $row = mysql_fetch_array ( $result );
                    $albumname = $row ['name'];
                    $albumtype = $row ['type'];
                    $path = "/var/www/html/tanqin/uploads" . DIRECTORY_SEPARATOR . $albumtype . DIRECTORY_SEPARATOR . $albumname;
                    deleteDir ( $path );
                    $query = "delete from pre_track_tbl where aid = '$albumid'";
                    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                    $query = "delete from pre_album_tbl where id = '$albumid'";
                    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                    $data = "success deleted.";
                }
            } elseif (isset ( $trackid )) {
                $query = "select * from pre_track_tbl where id = '$trackid'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                if (mysql_num_rows ( $result ) != 0) {
                    $row = mysql_fetch_array ( $result );
                    $trackname = $row ['name'];
                    $albumname = $row ['albumname'];
                    $albumtype = $row ['type'];
                    $path = "/var/www/html/tanqin/uploads" . DIRECTORY_SEPARATOR . $albumtype . DIRECTORY_SEPARATOR . $albumname . DIRECTORY_SEPARATOR . $trackname;
                    unlink ( $path );
                    $query = "delete from pre_track_tbl where id = '$trackid'";
                    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                    $data = "success deleted.";
                }
            }
        } else if ($type == "教学" || $type == "赏析") {
            if (isset ( $album )) {
                $query = "select * from pre_track_tbl where type = '$type' and albumname = '$album'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                while ( $row = mysql_fetch_array ( $result ) ) {
                    $array = array (
                            "id" => $row ['id'],
                            "type" => $row ['type'],
                            "name" => $row ['name'],
                            "albumname" => $row ['albumname'],
                            "description" => $row ['description'],
                            "preview" => $row ['preview'],
                            "path" => $basepath . $row ['type'] . $ds . $row ['albumname'] . $ds . $row ['name'] 
                    );
                    
                    $now = array (
                            $index => $array 
                    );
                    
                    $tracklist = array_merge ( $tracklist, $now );
                    
                    $index ++;
                }
                $data = $tracklist;
            } else {
                $query = "select * from pre_album_tbl where type = '$type'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                while ( $row = mysql_fetch_array ( $result ) ) {
                    $array = array (
                            "id" => $row ['id'],
                            "type" => $row ['type'],
                            "name" => $row ['name'],
                            "description" => $row ['description'],
                            "preview" => $row ['preview'] 
                    );
                    
                    $now = array (
                            $index => $array 
                    );
                    
                    $albumlist = array_merge ( $albumlist, $now );
                    
                    $index ++;
                }
                $data = $albumlist;
            }
        } elseif (isset ( $albumid )) {
            $query = "select * from pre_track_tbl where aid = '$albumid'";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            while ( $row = mysql_fetch_array ( $result ) ) {
                $array = array (
                        "id" => $row ['id'],
                        "type" => $row ['type'],
                        "name" => $row ['name'],
                        "albumname" => $row ['albumname'],
                        "description" => $row ['description'],
                        "preview" => $row ['preview'],
                        "path" => $basepath . $row ['type'] . $ds . $row ['albumname'] . $ds . $row ['name'] 
                );
                
                $now = array (
                        $index => $array 
                );
                
                $tracklist = array_merge ( $tracklist, $now );
                
                $index ++;
            }
            $data = $tracklist;
        }
        break;
    // two cases so similar we'll just share code
    case 'POST' :
    case 'PUT' :
        break;
    case 'DELETE' :
        break;
    default :
        throw new Exception ( 'Method Not Supported', 405 );
}

mysql_close ( $link );
// this is the output handler
header ( "Content-Type: application/json" );
echo json_encode ( $data );
function deleteDir($dir) {
    $delcmd = "rm -r " . $dir;
    shell_exec ( $delcmd );
}

?>
