<?php
    $DEFAULT_SERVER = "tenmanga";
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $chid = isset($_GET['ch']) ? $_GET['ch'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    $dbfile = "{$dbdir}{$separator}__data{$separator}{$dbname}.mga";
    
    $dbh = new PDO("sqlite:{$dbfile}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $server = current($dbh->query("select coalesce(FValue,'') from MANGA where FKey = 'SERVER'")->fetch());
    if ($server=="")
        $server = $DEFAULT_SERVER;
    $server = strtolower($server);
    
    $qry = "select pageid, pagenum, imglink, done, length(hex(img))/2 isi from pages where chapterid={$chid}";
    $pags = $dbh->query($qry);
    $tbl = "<table class='data'>
        <tr><th>ID</th><th>Page</th><th>Link</th><th>Img Size</th><th>Done</th></tr>";
    foreach($pags as $pag) {
        $tbl .= "<tr><td>{$pag['PAGEID']}</td><td>{$pag['PAGENUM']}</td><td>{$pag['IMGLINK']}</td><td>{$pag['isi']}</td><td>{$pag['DONE']}</td></tr>\n";
    }
    $tbl .= "</table>";
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
            
            function startup() {
                //loadChapList();
            }
        </script>
    </head>
    <body onload="startup()">
        <div>
            <a href="index.php">[Home]</a>
            <a href="manga.php?db=<?php echo urlencode($dbname); ?>">[<?php echo $dbname; ?>]</a>
            [<?php echo $_SERVER["HTTP_HOST"]; ?>]
        </div>
        <hr>
        <div>
            <br><div><b><?php echo $_GET['db']; ?> @ <?=$server?></b></div>
            <br><div id="chp"><?=$tbl?></div>
        </div>
        <div id="debug"></div>
    </body>
</html>