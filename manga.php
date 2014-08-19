<?php
    $DEFAULT_SERVER = "tenmanga";
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    $dbfile = "{$dbdir}{$separator}__data{$separator}{$dbname}.mga";
    
    $dbh = new PDO("sqlite:{$dbfile}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $server = current($dbh->query("select coalesce(FValue,'') from MANGA where FKey = 'SERVER'")->fetch());
    if ($server=="")
        $server = $DEFAULT_SERVER;
    $server = strtolower($server);
?>
<html>
    <head>
        <title>Web Manga Downloader</title>
        <link rel="stylesheet" type="text/css" href="default.css">
        <script type="text/javascript" src="default.js"></script>
        <script type="text/javascript" src="ajaxObject.js"></script>
        <script type="text/javascript" src="ajaxParam.js"></script>
        <script type="text/javascript">
            var DB = "<?php echo $_GET['db']; ?>";
            var DOWN = false;
            var RETRY = 0;
            var SKIPCID = 0;
            
            function setStatus(txt) { $("sts").innerHTML = txt; }
            function clrStatus() { $("sts").innerHTML = "Idle."; }

            var jxSkip = new ajaxObject("__skipchapter.php");
            jxSkip.callback = function(responseText, responseStatus, responseXML) {
                $("pg"+SKIPCID).innerHTML = "1";
                $("don"+SKIPCID).innerHTML = "1";
                SKIPCID = 0;
                startTimer();
            }
            function skipChapter(cid) {
                if (confirm('Skip this chapter?')) {
                    var p = new ajaxParams();
                    p.addParam('db', DB);
                    p.addParam('id', cid);
                    jxSkip.update(p.toString());
                    setStatus("Skipping chapter...");
                    SKIPCID = cid;
                }
            }

            var jxReset = new ajaxObject("__skipchapter.php");
            jxReset.callback = function(responseText, responseStatus, responseXML) {
                $('debug').innerHTML = responseText;
                loadChapList();
            }
            function resetSkipped() {
                if (confirm('Reset all skipped pages?')) {
                    var p = new ajaxParams();
                    p.addParam('db', DB);
                    p.addParam('reset','yes');
                    jxReset.update(p.toString());
                    setStatus("Reset skipped pages...");
                }
            }
            
            var jxChaps = new ajaxObject("__chapters.php");
            jxChaps.callback = function(responseText, responseStatus, responseXML) {
                $("chp").innerHTML = responseText;
                clrStatus();
            }
            
            function loadChapList() {
                if (!jxChaps.updating) {
                    setStatus("Reading db...");
                    var p = new ajaxParams();
                    p.addParam("db",DB);
                    jxChaps.update(p.toString());
                }
            }
            
            var jxLoad = new ajaxObject("<?="__servers/".$server?>/__readchapters.php");
            jxLoad.callback = function(responseText, responseStatus, responseXML) {
                //alert(responseText);
                $('debug').innerHTML = responseText;
                clrStatus();
                loadChapList();
            }
            
            function loadChapter() {
                if (!jxLoad.updating) {
                    setStatus("Downloading chapter list...");
                    var p = new ajaxParams();
                    p.addParam("db",DB);
                    //jxLoad.update("db="+DB);
                    jxLoad.update(p.toString());
                }
            }
            
            var jxPage = new ajaxObject("<?="__servers/".$server?>/__readpageauto.php");
            jxPage.callback = function(responseText, responseStatus, responseXML) {
                var data;
                $('debug').innerHTML="";
                try {
                    eval('data=' + responseText);
                } catch(e) {
                    $('debug').innerHTML=responseText;
                    RETRY = 1+RETRY;
                    startTimer();
                    return ;
                }
                if (data.error!=0) {
                    //alert(data.msg);
                    $('debug').innerHTML =responseText;
                    RETRY = 1+RETRY;
                    if (data.error==3)
                        DOWN=false;
                } else {
                    $("pg"+data.id).innerHTML = data.pages;
                    $("don"+data.id).innerHTML = data.done;
                    RETRY = 0;
                }
                startTimer();
            }
            
            function loadPages() {
                if (!DOWN) {
                    DOWN = true;
                    setStatus("Downloading manga pages...");
                    $('btnDownload').innerHTML = '[Stop]';
                    RETRY = 0;
                    startTimer();
                } else {
                    DOWN = false;
                    setStatus("Downloading manga pages... (stopping)");
                    $('btnDownload').style.display = 'none';
                }
            }
            
            function startTimer() {
                if (DOWN) {
                    setTimeout(function(){doLoadPages();}, 50);
                } else {
                    clrStatus();
                    $('btnDownload').innerHTML = '[Download]';
                    $('btnDownload').style.display = '';
                }
            }
            
            function doLoadPages() {
                if (!jxPage.updating) {
                    setStatus("Downloading manga pages ("+(1+RETRY)+" tries)...");
                    var p = new ajaxParams();
                    p.addParam("db",DB);
                    p.addParam("retry", RETRY);
                    jxPage.update(p.toString());
                }
            }
            
            function readLastChapter() {
                window.location = 'read.php?db='+encodeURIComponent(DB);
            }
            
            function readChapter(id,txt) {
                window.location = 'read.php?db='+encodeURIComponent(DB)+'&ch='+id+'&str='+txt;
            }
            
            function downloadChapter(id,txt) {
                window.location = 'download.php?db='+encodeURIComponent(DB)+'&ch='+id+'&str='+txt;
            }
            
            function openPages(ch) {
                window.location = 'pages.php?db='+encodeURIComponent(DB)+'&ch='+ch;
            }
            
            function checkJx() {
                if (jxPage.updating) {
                    $("jPage").className = "running";
                } else {
                    $("jPage").className = "stopping";
                }
                if (jxLoad.updating) {
                    $("jLoad").className = "running";
                } else {
                    $("jLoad").className = "stopping";
                }
                if (jxChaps.updating) {
                    $("jChaps").className = "running";
                } else {
                    $("jChaps").className = "stopping";
                }
            }
            
            function startup() {
                loadChapList();
            }
            
            var si = setInterval(function(){checkJx();}, 100);
        </script>
    </head>
    <body onload="startup()">
        <div>
            <a href="index.php">[Home]</a>
            <a href="phpinfo.php">[PHP Info]</a>
            [<?php echo $_SERVER["HTTP_HOST"]; ?>]
            [<span id="jPage" class="stopping">jxPage</span> | <span id="jLoad" class="stopping">jxLoad</span> | <span id="jChaps" class="stopping">jxChaps</span>]
        </div>
        <hr>
        <div>
            <br><div><b><?php echo $_GET['db']; ?> @ <?=$server?></b></div>
            <br><div>
                <a href="javascript:loadChapter()">[Chapters]</a>
                <a id="btnDownload" href="javascript:loadPages()">[Download]</a>
                <a href="javascript:readLastChapter()">[Read]</a>
                <a href="javascript:resetSkipped()">[Reset Skipped Pages]</a>
            </div>
            <br><div>Status :: <span id="sts">Idle.</span></div>
            <br><div id="chp"></div>
        </div>
        <div id="debug"></div>
    </body>
</html>