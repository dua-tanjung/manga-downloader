<?php
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    $ndb = $_GET['new'];
    $nsv = strtoupper($_GET['server']);
    $fname = "{$dbdir}{$separator}__data{$separator}{$ndb}.mga";
    
    if (!file_exists($fname)) {
        copy($dbdir."{$separator}manga.db3", $fname);
        $dbh = new PDO("sqlite:{$fname}");
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->query("insert into MANGA (FKey, FValue) values ('SERVER','{$nsv}')");
        echo "New database created.";
    } else {
        echo "Database already exists.";
    }
?>
