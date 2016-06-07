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

switch ($verb) {
    case 'GET' :
        $albumlist = array ();
        $tracklist = array ();
        $index = 0;
        if ($type == "教学" || $type == "赏析") {
            if (isset ( $album )) {
                $query = "select * from pre_track_tbl where type = '$type' and albumname = '$album'";
                $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
                while ( $row = mysql_fetch_array ( $result ) ) {
                    $array = array (
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
        } elseif (isset ( $album )) {
            $query = "select * from pre_album_tbl where type = '$type' and albumname = '$album'";
            $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
            while ( $row = mysql_fetch_array ( $result ) ) {
                $now = array (
                        $index => $row [0] 
                );
                
                $albumlist = array_merge ( $albumlist, $now );
                
                $index ++;
            }
            $data = $albumlist;
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

?>
