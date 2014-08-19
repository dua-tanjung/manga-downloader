<?php
    $err = error_reporting();
    error_reporting(E_ERROR);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $retry = isset($_GET['retry']) ? $_GET['retry'] : 0;
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $hasil = array('id'=>0, 'pages'=>0, 'done'=>0, 'error'=>0, 'msg'=>'', 'retry'=>$retry);
    $fname = "../../__data/{$dbname}.mga";

    if (!file_exists($fname)) {
        $hasil['error'] = 1;
        $hasil['msg'] = "Database file not found.\n{$fname} not found in filesystem.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    require_once "vars.php";
    require_once "../../functions.php";

    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $qry = "select * from chapters where coalesce(pages,0)>coalesce(donecount,0) or coalesce(pages,0)=0
            order by chapterid
            limit 1";
    $rst = $dbh->query($qry)->fetch(PDO::FETCH_ASSOC);
    
    if ($rst == false) {
        $hasil['error'] = 3;
        $hasil['msg'] = "No next page.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    $CHAPTERID = $rst['CHAPTERID'];
    $PAGES = isset($rst['PAGES']) ? (null!=$rst['PAGES'] ? $rst['PAGES'] : 0) : 0;
    $DONE = isset($rst['DONECOUNT']) ? (null!=$rst['DONECOUNT'] ? $rst['DONECOUNT'] : 0) : 0;
    $LINK = isset($rst['LINK']) ? (null!=$rst['LINK'] ? $rst['LINK'] : "") : "";
    
    if ($LINK=="") {
        $hasil['error'] = 5;
        $hasil['msg'] = "No link for chapter.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    $qry = "select PAGEID, PAGENUM, CHAPTERID, LINK, IMGLINK, DONE, coalesce(content, '') ISI
        from pages
        where CHAPTERID={$CHAPTERID} and coalesce(DONE,0)=0
        order by pageid
        limit 1";
        
    $rst = $dbh->query($qry)->fetch(PDO::FETCH_ASSOC);
    
    $ambil = "";
    $page="";
    if ($rst==false) {
        if ($retry >= $RETRY) {
            // skip chapter
            $dbh->query("update chapters set pages=1, donecount=1 where chapterid={$CHAPTERID}");
            $hasil['id'] = $CHAPTERID;
            $hasil['pages'] = 1;
            $hasil['done'] = 1;
            $hasil['err'] = 0;
            $hasil['msg'] = "Skipped.";
            error_reporting($err);
            die(json_encode($hasil));
        }
        $ambil = $LINK;
        try {
            $page = get_data($LINK);
        } catch (Exception $e) {
            $hasil['error'] = 11;
            $hasil['msg'] = "Error loading page. {$e}";
            error_reporting($err);
            die(json_encode($hasil));
        }
    } else {
        if ($retry >= $RETRY) {
            // skip page
            $dbh->query("update pages set done=1 where pageid={$rst['PAGEID']}");
            $hasil['id'] = $CHAPTERID;
            $hasil['pages'] = current($dbh->query("select count(pageid) from pages where chapterid={$CHAPTERID}")->fetch());
            $hasil['done'] = current($dbh->query("select count(pageid) from pages where chapterid={$CHAPTERID} and done=1")->fetch());
            $hasil['err'] = 0;
            $hasil['msg'] = "Skipped.";
            $dbh->query("update chapters set DONECOUNT={$hasil['done']} where chapterid={$CHAPTERID}");
            error_reporting($err);
            die(json_encode($hasil));
        }
        $ambil = $rst['LINK'];
        try {
            $page = get_data($rst['LINK']);
        } catch (Exception $e) {
            $hasil['error'] = 12;
            $hasil['msg'] = "Error loading page. {$e}";
            error_reporting($err);
            die(json_encode($hasil));
        }
    }

    if ($page=="") {
        $hasil['error'] = 13;
        $hasil['msg'] = "Empty page when loading {$ambil}.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    //$page = file_get_contents("tes.html");
    //file_put_contents("tes.html", $page);

    $dom = new DOMDocument;
    $dom->loadHTML($page);
    
    $curpage=0;
    
    // isi pages
    $st1 = $dbh->prepare("insert into pages (pagenum, chapterid, link, done) values (?,?,?,0)");
    $sels = $dom->getElementsByTagName("select");
    $scount = 0;
    //$arro = array();
    foreach ($sels as $sel) {
        if ($sel->getAttribute("id")=="page" && $scount==0) {
            $opts = $sel->getElementsByTagName("option");
            foreach($opts as $opt) {
                $pnum = DOMinnerHTML($opt);
                $plink = $opt->getAttribute("value");
                $st1->bindParam(1, $pnum);
                $st1->bindParam(2, $CHAPTERID);
                $st1->bindParam(3, $plink);
                try {
                    $st1->execute();
                } catch (Exception $e) {
                    //
                }
            }
            $scount++;
        }
    }

    $PAGES = current($dbh->query("select count(pageid) from pages where chapterid={$CHAPTERID}")->fetch());
    $dbh->query("update chapters set pages={$PAGES} where chapterid={$CHAPTERID}");
        
    if ($rst == false) {
        $tss = $dom->getElementsByTagName("title");
        foreach($tss as $ts) {
            $strts = DOMinnerHTML($ts);
            $arr = explode(" ", $strts);
            $strpage = $arr[count($arr)-1];
        }
        $curpage = current($dbh->query("select pageid from pages where chapterid={$CHAPTERID} and pagenum='{$strpage}'")->fetch());
    } else {
        $curpage=$rst['PAGEID'];
    }
    
    $IMGLINK = "";
    
    $domimgs = $dom->getElementsByTagName("img");
    foreach($domimgs as $domimg) {
        if ($domimg->getAttribute("id")=="comicpic")
            $IMGLINK = $domimg->getAttribute("src");
    }

    $qry = "update pages set imglink=? where pageid=".$curpage;
    $st2 = $dbh->prepare($qry);
    
    $st2->bindParam(1, $IMGLINK);
    $st2->execute();
    
    try {
        $imgf = get_data($IMGLINK);
    } catch (Exception $e) {
        $hasil['error'] = 9;
        $hasil['msg'] = "Error loading page. ".$e;
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    if (strlen($imgf)<20) {
        $hasil['error'] = 19;
        $hasil['msg'] = "Error loading image from {$IMGLINK}.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    $qry = "update pages set img=?, done=1 where pageid=".$curpage;
    //echo "$qry<br><br>";
    $stt = $dbh->prepare($qry);
    $stt->bindParam(1, $imgf);
    $stt->execute();
    
    $count = current($dbh->query("select count(pageid) from pages where chapterid={$CHAPTERID} and done=1")->fetch());
    $dbh->query("update chapters set DONECOUNT={$count} where chapterid={$CHAPTERID}");
    
    $hasil['error'] = 0;
    $hasil['msg'] = "Success.";
    $hasil['id'] = $CHAPTERID;
    $hasil['pages'] = $PAGES;
    $hasil['done'] = $count;
    
    error_reporting($err);
    die(json_encode($hasil));
?>
