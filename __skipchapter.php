<?php
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $chid = isset($_GET['id']) ? $_GET['id'] : "^_^";
    $rest = isset($_GET['reset']) ? $_GET['reset'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $fname = "{$dbdir}{$separator}__data{$separator}{$dbname}.mga";
    if (!file_exists("{$fname}")) {
        die("Database file not found.\n{$fname} not found in filesystem.");
    }
    
    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($rest != '^_^') {
        try {    
            $dbh->query("update pages set done=0 where length(hex(img))/2=0 and done=1");
            //$dbh->query("update chapters set donecount = dc from (select chapterid, count(pageid) from pages where done=1 group by chapterid) pg where chapters.chapterid=pg.chapterid");
            $cps = $dbh->query("select distinct chapterid from pages where done=0");
            foreach($cps as $cpi) {
                $dbh->query("update chapters set donecount=(select count(pageid) from pages where chapterid={$cpi['CHAPTERID']} and done=1) where chapterid={$cpi['CHAPTERID']}");
            }
            echo "Success.";
        } catch (Exception $e) {
            echo "Error. ".$e;
        }
    } else {
        try {
            $sud = current($dbh->query("select coalesce(pages,0)+coalesce(donecount,0) jum from chapters where chapterid={$chid}")->fetch());
            if ($sud == 2) {
                $dbh->query("update chapters set pages=0, donecount=0 where chapterid={$chid} and coalesce(pages,0)=1 and coalesce(donecount,0)=1");
            } else {
                $dbh->query("update chapters set pages=1, donecount=1 where chapterid={$chid} and coalesce(pages,0)=0 and coalesce(donecount,0)=0");
            }
            echo "Success.";
        } catch (Exception $e) {
            echo "Error. ".$e;
        }
    }
?>