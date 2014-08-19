<?php
    $err = error_reporting();
    error_reporting(0);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $hasil = array('id'=>0, 'pages'=>0, 'done'=>0, 'error'=>0, 'msg'=>'');

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
            order by chapter
            limit 1";
    $rst = $dbh->query($qry)->fetch(PDO::FETCH_ASSOC);
    
    if ($rst == false) {
        $hasil['error'] = 3;
        $hasil['msg'] = "No next page.";
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    $CHAPTERID = $rst['CHAPTERID'];
    $PAGES = isset($rst['PAGES']) ? (null!=$rst['PAGES'] ? $rst['PAGES'] : "0") : "0";
    $DONE = 0;
    
    $hasil['pages'] = $PAGES;
    
    if ($PAGES == "0") {
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
        
        $stm = $dbh->prepare("insert into chapters (chapter, link) values (?,?)");
        $stn = $dbh->prepare("insert into pages (PAGENUM, CHAPTERID, LINK, DONE) values (?, ?, ?, 0)");
        $stp = $dbh->prepare("update chapters set pages=? where chapterid=?");
        try {
            $page = get_data($BASE.$dbname."/".$rst['CHAPTER']."/");
        } catch (Exception $e) {
            $hasil['error'] = 2;
            $hasil['msg'] = "Error loading page. ".$e;
            error_reporting($err);
            die(json_encode($hasil));
        }
        
        $ccount = 0;
        $pcount = 0;
        $cid = 0;
        $cstr = "";
        
        $dom = new DOMDocument;
        $dom->loadHTML($page);
        $combos = $dom->getElementsByTagName('select');
        foreach ($combos as $combo) {
            $nama = $combo->getAttribute('name');
            if ($nama == "chapter") {
                if ($ccount==0) {
                    $opts = $combo->getElementsByTagName('option');
                    $sel = "";
                    $arrch = array();
                    foreach($opts as $opt) {
                        if ($sel == "") $sel = null!==$opt->getAttribute('selected') ? $opt->getAttribute('value') : "";
                        $ch = $opt->getAttribute('value');
                        $lnk= "{$BASE}{$dbname}/{$ch}/";
                        $arrch[] = "{$ch}|{$lnk}";
                    }
                    $charr = array_reverse($arrch);
                    foreach ($charr as $chs) {
                        $arr = explode('|',$chs);
                        $stm->bindParam(1, $arr[0]);
                        $stm->bindParam(2, $arr[1]);
                        try {
                            $stm->execute();
                            if ($sel == $arr[0]) {
                                $cid = $dbh->lastInsertId();
                                $cstr = $arr[0];
                            }
                        } catch (Exception $e) {
                            // do nothing
                        }
                    }
                    $ccount++;
                }
            }
            if ($nama == "page") {
                if ($pcount==0) {
                    $pgs = array();
                    $opts = $combo->getElementsByTagName('option');
                    $sel = "";
                    foreach($opts as $opt) {
                        if ($sel == "") $sel = null!==$opt->getAttribute('selected') ? $opt->getAttribute('value') : "";
                        $pg = $opt->getAttribute('value');
                        $lnk= "{$BASE}{$dbname}/{$rst['CHAPTER']}/{$pg}";
                        $stn->bindParam(1, $pg);
                        $stn->bindParam(2, $CHAPTERID);
                        $stn->bindParam(3, $lnk);
                        try {
                            $stn->execute();
                            if ($sel != "") {
                                $pid = $dbh->lastInsertId();
                                $sel = "";
                            }
                        } catch (Exception $e) {
                            // do nothing
                        }
                        $pgs[] = 0+$pg;
                    }
    
                    $stp->bindParam(1, count($pgs));
                    $stp->bindParam(2, $CHAPTERID);
                    $stp->execute();
    
                    if ($SAVE_ALL) {
                        $comstr = gzcompress($page, 9);
                        $sto->bindParam(1, $comstr);
                        $sto->bindParam(2, $pid);
                        $sto->execute();
                    }
                    $PAGES = count($pgs);
                    $pcount++;
                }
            }
        }
    } else {
        $PAGES = $rst['PAGES'];
        $DONE = isset($rst['DONECOUNT']) ? $rst['DONECOUNT'] : 0;
    }
    
    $qry = "select PAGEID, PAGENUM, CHAPTERID, LINK, IMGLINK, DONE
        from pages
        where CHAPTERID={$CHAPTERID} and coalesce(DONE,0)=0
        order by pagenum
        limit 1";
        
    $rst = $dbh->query($qry)->fetch(PDO::FETCH_ASSOC);
    
    if ($rst == false) {
        $hasil['error'] = 3;
        $hasil['msg'] = "No next page.";
        error_reporting($err);
        die(json_encode($hasil));
    }

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

    try {
        $page = get_data($rst['LINK']);
    } catch (Exception $e) {
        $hasil['error'] = 4;
        $hasil['msg'] = "Error loading page. ".$e;
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    if ($page=="") {
        $hasil['error'] = 5;
        $hasil['msg'] = "Error loading page.";
        error_reporting($err);
        die(json_encode($hasil));
    }

    $dom = new DOMDocument;
    $dom->loadHTML($page);
    $images = $dom->getElementsByTagName('img');
    $imageLink = "";
    foreach ($images as $image) {
        if ($imageLink == "") {
            $imageLink = "picture"==$image->getAttribute('class') ? $BASE.myEncode($image->getAttribute('src')) : "";
        }
    }
    
    $qry = "update pages set imglink=? where pageid=".$rst['PAGEID'];
    $stq = $dbh->prepare($qry);
    $stq->bindParam(1, $imageLink);
    $stq->execute();
    
    try {
        $imgf = get_data($imageLink, $rst['LINK']);
    } catch (Exception $e) {
        $hasil['error'] = 9;
        $hasil['msg'] = "Error loading page. ".$e;
        error_reporting($err);
        die(json_encode($hasil));
    }
    
    if (strlen($imgf)<10) {
        $hasil['error'] = 19;
        $hasil['msg'] = "Error loading page. Empty image file";
        error_reporting($err);
        die(json_encode($hasil));
    } else {
        $stt = $dbh->prepare("update pages set img=?, done=1 where pageid=".$rst['PAGEID']);
        $stt->bindParam(1, $imgf);
        $stt->execute();
    }
    
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
