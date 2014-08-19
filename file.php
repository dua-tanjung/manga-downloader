<?php
    $err = error_reporting();
    error_reporting(0);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $pageid = isset($_GET['id']) ? $_GET['id'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    $fname = "__data/{$dbname}.mga";
    
    if (!file_exists($fname)) {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        exit();
    }
    
    require_once "functions.php";

    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $qry = "select * from pages where pageid={$pageid}";
    $row = $dbh->query($qry)->fetch();
    
    $arr = explode(".", $row['IMGLINK']);
    $mime = mime_content_type("apaaja.".$arr[count($arr)]);
    $img = $row['IMG'];
    header("Content-Type: ".$mime);
    header("Content-Length: ".strlen($img));
    echo $img;
?>