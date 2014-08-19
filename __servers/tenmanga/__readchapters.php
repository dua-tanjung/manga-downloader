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
    
    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    require_once "vars.php";
    require_once "../../functions.php";

    $stm = $dbh->prepare("insert into chapters (chapter, link) values (?,?)");
    $stn = $dbh->prepare("insert into pages (PAGENUM, CHAPTERID, LINK, DONE) values (?, ?, ?, 0)");
    $stp = $dbh->prepare("update chapters set pages=? where chapterid=?");
    try {
        $page = get_data($BASE."book/".$dbname.".html?waring=1");
    } catch (Exception $e) {
        die("Error loading page. ".$e);
    }
    
    if ($page=="") {
        die("Error loading page.");
    }
    
    $ccount = 0;
    $pcount = 0;
    $cid = 0;
    $cstr = "";
    
    $dom = new DOMDocument;
    $dom->loadHTML($page);
    $lnks = $dom->getElementsByTagName('a');
    $baselink = substr($BASE, 0, strlen($BASE)-1);
    $arro = array();
    $ark = array();
    foreach ($lnks as $link) {
        $ref = $link->getAttribute('href');
        if (substr($ref, 0, 9)=="/chapter/") {
            $arr = explode("/", $ref);
            $strlink = "{$baselink}{$ref}";
            $chname = DOMinnerHTML($link);
            if (!isset($ark[$chkey])) {
                $ark[$chkey] = 1;
                $arro[] = $chname.'|'.$strlink;
            }
        }
    }
    $rev = array_reverse($arro);
    foreach ($rev as $itm) {
        $arr = explode('|', $itm);
        $stm->bindParam(1, $arr[0]);
        $stm->bindParam(2, $arr[1]);
        try {
            $stm->execute();
        } catch (Exception $e) {
            //
        }
    }

    echo "Success.";
    error_reporting($err);
?>
