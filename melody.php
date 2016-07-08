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

switch ($verb) {
    case 'GET' :
        $index = 0;
        $melodylist = array ();
        
        $query = "select * from pre_melody_tbl";
        $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
        while ( $row = mysql_fetch_array ( $result ) ) {
            $array = array (
                    "id" => $row ['id'],
                    "type" => $row ['type'],
                    "name" => $row ['name'],
                    "description" => $row ['description'],
                    "path" => $basepath . $row ['type'] . $ds . $row ['path'] . $ds . $row ['name'] 
            );
            
            $now = array (
                    $index => $array 
            );
            
            $melodylist = array_merge ( $melodylist, $now );
            
            $index ++;
        }
        $data = $melodylist;
        
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
