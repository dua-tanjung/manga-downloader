<?php
    $err = error_reporting();
    //error_reporting(0);
    
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $chapter = isset($_GET['ch']) ? $_GET['ch'] : "^_^";
    $schap = isset($_GET['str']) ? $_GET['str'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    $dbfile = "__data/{$dbname}.mga";
    
    if (!file_exists($dbfile)) {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.<br>{$dbfile}";
        exit();
    }
    
    $dbh = new PDO("sqlite:{$dbfile}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $lastchap = current($dbh->query("select coalesce(count(FValue),0) from MANGA where FKey='LAST_CHAPTER'")->fetch());

    if ($chapter == "^_^") {
        if ($lastchap==0) {
            $chapter = current($dbh->query("select chapterid from chapters order by chapterid limit 1")->fetch());
        } else {
            $chapter = current($dbh->query("select FValue from MANGA where FKey = 'LAST_CHAPTER'")->fetch());
            $lastchap = 1;
        }
    }
    
    if ($lastchap==0) {
        $dbh->query("insert into MANGA (FKey, FValue) values ('LAST_CHAPTER','{$chapter}')");
    } else {
        $dbh->query("update MANGA set FValue = '{$chapter}' where FKey = 'LAST_CHAPTER'");
    }
    
    if ($schap == "^_^") {
        $schap = current($dbh->query("select chapter from chapters where chapterid={$chapter}")->fetch());
    }
    
    $qry = "select PAGEID, PAGENUM from pages where chapterid={$chapter} and done=1 order by pageid";
    $rows = $dbh->query($qry);
    $pages = array();
    $pnums = array();
    $cbx = "<select id='cbxpage' onchange='load(this.value)'>";
    $cbn = 0;
    foreach ($rows as $row) {
        $pages[] = $row['PAGEID'];
        //$pnums[] = $row['PAGENUM'];
        $cbx .= "<option value='{$cbn}'>{$row['PAGENUM']}</option>";
        $cbn++;
    }
    $arr = implode(",", $pages);
    $cbx .= "</select>";
?><html>
    <head>
        <title>Web Manga Reader</title>
        <link rel="stylesheet" type="text/css" href="default.css">
        <script type="text/javascript" src="default.js"></script>
        <script type="text/javascript" src="ajaxObject.js"></script>
        <script type="text/javascript" src="ajaxParam.js"></script>
        <script type="text/javascript">
            var DB = "<?php echo $dbname; ?>";
            var PAGES = [<?php echo $arr; ?>];
            var CHP = "<?php echo $chapter; ?>";
            var IDX = 0;
            var MAX = PAGES.length;
            
            var jxNext = new ajaxObject("__getnextchapter.php");
            jxNext.callback = function(responseText, responseStatus, responseXML) {
                newchap = parseInt(responseText);
                if (newchap>0) {
                    window.location = "read.php?db="+encodeURIComponent(DB)+"&ch="+newchap;
                } else {
                    alert("End of downloaded manga.");
                }
            }

            
            function load(num) {
                //$("pgn").innerHTML = 1+parseInt(num);
                $("preview").style.opacity = 0.4;
                $("cbxpage").value = parseInt(num);
                $("preview").src = "file.php?db="+encodeURIComponent(DB)+"&id="+PAGES[parseInt(num)];
                IDX = parseInt(num);
            }
            
            function next() {
                if (IDX == MAX-1) {
                    var p = new ajaxParams();
                    p.addParam("db", DB);
                    p.addParam("ch", CHP);
                    jxNext.update(p.toString());
                    $("preview").style.opacity = 0.4;
                }
                var old = IDX;
                if (IDX<MAX-1) IDX = IDX+1;
                if (old!=IDX) load(IDX);
            }
            
            function prev() {
                var old = IDX;
                if (IDX>0) IDX = IDX-1;
                if (old!=IDX) load(IDX);
            }
            
            function startup() {
                $("ctr").innerHTML = PAGES.length;
                load(IDX);
            }
        </script>
    </head>
    <body onload="startup()">
        <div><a href="index.php">[Home]</a> <a href="manga.php?db=<?php echo urlencode($dbname); ?>">[<?php echo $dbname; ?>]</a></div>
        <hr>
        <div>
            <br><div><b>Chapter : <?php echo $schap; ?></b> ; Page <!--[<span id="pgn">1</span>]--><?=$cbx?> of [<span id="ctr"></span>] <a href="javascript:prev()">[ &lt;&lt; Prev Page ]</a> <a href="javascript:next()">[ Next Page &gt;&gt; ]</a></div>
            <br><div id="chp" style="text-align: center;"><a href="javascript:next()" style="outline:none;"><img id="preview" src="" onload="window.scrollTo(0,0); this.style.opacity = 1;" style="border-style: solid; border-width:3px; border-color:black;"></a></div>
        </div>
        <div id="debug"></div>
    </body>
</html>