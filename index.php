<?php
$dirs = array_filter(glob('__servers/*'), 'is_dir');
$cbx_server = "<select id='servers' name='servers'>";
foreach($dirs as $dir) {
    $svr = strtoupper(str_replace("__servers/","",$dir));
    $cbx_server .= "<option value='{$svr}'>{$svr}</value>";
}
$cbx_server .= "</select>";
?>
<html>
    <head>
        <title>Komik Downloader</title>
        <link rel="stylesheet" type="text/css" href="default.css">
        <script type="text/javascript" src="default.js"></script>
        <script type="text/javascript" src="ajaxObject.js"></script>
        <script type="text/javascript" src="ajaxParam.js"></script>
        <script type="text/javascript">
            var jxListdb = new ajaxObject('__listdb.php');
            jxListdb.callback = function(responseText, responseStatus, responseXML) {
                $('dbs').innerHTML = responseText;
            }
            
            var jxCreatedb = new ajaxObject('__createdb.php');
            jxCreatedb.callback = function(responseText, responseStatus, responseXML) {
                //alert(responseText);
                $("debug").innerHTML = responseText;
                startup();
                $("new").focus();
            }

            function doCreate() {
                var v = new ajaxParams();
                v.addParam("new",$("new").value);
                v.addParam("server",$("servers").value);
                jxCreatedb.update(v.toString());
                $("new").value = "";
            }
            
            function startup() {
                $("dbs").innerHTML = "loading data...";
                jxListdb.update();
            }
        </script>
    </head>
    <body onload="startup()">
        <div><a href="index.php">[Home]</a> <a href="phpinfo.php">[PHP Info]</a> [<?php echo $_SERVER["HTTP_HOST"]; ?>]</div>
        <hr>
        <big><big><big>Web Manga Downloader</big></big></big>
        <div>Create new database : <input type="text" id="new" name="new" value=""> <?=$cbx_server?> <input type="button" value="create!" onclick="doCreate();"></div>
        <hr>
        <div>
            Available data:
            <div id="dbs">loading data...</div>
        </div>
    </body>
</html>
