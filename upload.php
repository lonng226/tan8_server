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
    write_file_to_db($path, $name, $_FILES ['file'] ['name']);
}

/**
 * Write the data to database
 *
 * @param str $type
 *            type
 *        str $albumname
 *            albumname
 *        str $filename
 *            filename
 */
function write_file_to_db($type, $albumname, $filename) {
    // Connect to database
    $link = mysql_connect ( 'localhost', 'root', 'welcome1' ) or die ( 'Could not connect: ' . mysql_error () );
    mysql_select_db ( 'tanqindb' ) or die ( 'Could not select database' );

    $query = "insert into pre_album_tbl (type, albumname, filename) values ('$type', '$albumname', '$filename')";
    $result = mysql_query ( $query ) or die ( 'Query failed: ' . mysql_error () );
    mysql_close ( $link );
}

?>