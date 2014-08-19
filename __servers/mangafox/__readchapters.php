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

    $stm = $dbh->prepare("insert into chapters (chapter, link, pages, donecount) values (?,?,0,0)");
    try {
        $page = get_data($BASE."manga/".$dbname."/");
    } catch (Exception $e) {
        die("Error loading page. ".$e);
    }
    
    if ($page=="") {
        die("Error loading page.");
    }
    
    $arrChapter = array();
    
    $dom = new DOMDocument;
    $dom->loadHTML($page);
    
    $uls = $dom->getElementsByTagName("ul");
    foreach ($uls as $uli) {
        if ($uli->getAttribute("class")=="chlist") {
            $lis = $uli->getElementsByTagName("li");
            foreach ($lis as $lii) {
                $Link = "";
                $Chap = "";
                $Title = "";
                $aas = $lii->getElementsByTagName("a");
                foreach ($aas as $aai) {
                    if ($aai->getAttribute("class")=="tips") {
                        $Link = $aai->getAttribute("href");
                        $Chap = DOMinnerHTML($aai);
                    }
                }
                $spans = $lii->getElementsByTagName("span");
                foreach ($spans as $spani) {
                    if ($spani->getAttribute("class")=="title nowrap")
                        $Title = DOMinnerHTML($spani);
                }
                $arrChapter[] = (object) array('link'=>$Link, 'chapter'=>$Chap, 'title'=>$Title);
            }
        }
    }
    $arrChap = array_reverse($arrChapter);
    foreach($arrChap as $chap) {
        $c1 = $chap->chapter." - ".$chap->title;
        $stm->bindParam(1, $c1);
        $stm->bindParam(2, $chap->link);
        try {
            $stm->execute();
        } catch (Exception $e) {
            //
        }
    }
    
    echo "Success.";
    error_reporting($err);
?>
