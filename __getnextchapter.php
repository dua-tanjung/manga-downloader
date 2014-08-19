<?php
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $chid = isset($_GET['ch']) ? $_GET['ch'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $fname = "{$dbdir}{$separator}__data{$separator}{$dbname}.mga";
    if (!file_exists("{$fname}")) {
        die("Database file not found.\n{$fname} not found in filesystem.");
    }
    
    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $chap = current($dbh->query("select coalesce(chapterid,0) from pages where chapterid>{$chid} order by coalesce(chapterid,0) limit 1")->fetch());
    echo $chap;
?>