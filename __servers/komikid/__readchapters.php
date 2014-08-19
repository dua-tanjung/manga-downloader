<?php
    $err = error_reporting();
    error_reporting(0);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $fname = "../../__data/{$dbname}.mga";
    if (!file_exists($fname)) {
        die("Database file not found.\n{$fname} not found in filesystem.");
    }
    
    require_once "vars.php";
    require_once "../../functions.php";

    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stm = $dbh->prepare("insert into chapters (chapter, link) values (?,?)");
    $stn = $dbh->prepare("insert into pages (PAGENUM, CHAPTERID, LINK, DONE) values (?, ?, ?, 0)");
    $stp = $dbh->prepare("update chapters set pages=? where chapterid=?");
    try {
        $page = get_data($BASE.$dbname);
    } catch (Exception $e) {
        die("Error loading page. ".$e);
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
                    $lnk= "{$BASE}{$dbname}/{$cstr}/{$pg}";
                    $stn->bindParam(1, $pg);
                    $stn->bindParam(2, $cid);
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
                $stp->bindParam(2, $cid);
                $stp->execute();

               $pcount++;
            }
        }
    }

    echo "Success.";
    error_reporting($err);
?>
