<?php
    $err = error_reporting();
    error_reporting(0);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $chapter = isset($_GET['ch']) ? $_GET['ch'] : "^_^";
    $schap = isset($_GET['str']) ? $_GET['str'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    if (!file_exists("{$dbdir}{$separator}{$dbname}.mga")) {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        exit();
    }
    
    require_once "ZipStream.php";
    require_once "vars.php";

    $dbh = new PDO("sqlite:{$dbdir}{$separator}{$dbname}.mga");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $zip = new ZipStream("{$dbname}_{$schap}.zip");
    
    $qry = "select * from pages where chapterid={$chapter} order by pagenum";
    $rows = $dbh->query($qry);
    foreach ($rows as $row) {
        $arr = explode(".", $row['IMGLINK']);
        $isi = $row['IMG'];
        $nama = $row['PAGENUM'].".".$arr[count($arr)-1];
        $zip->addFile($isi,$nama);
    }
    $zip->finalize();
?>