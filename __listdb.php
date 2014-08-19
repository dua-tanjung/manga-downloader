<?php
    //$files = scandir(getcwd());
    $files = scandir("__data");
    echo "<ol>";
    foreach ($files as $file) {
        $x = explode(".", $file);
        if ($x[count($x)-1]=="mga") {
            /*
            $fname = "__data/{$file}";
            $dbh = new PDO("sqlite:{$fname}");
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            try {
                $server = current($dbh->query("select FValue from MANGA where FKey='SERVER'")->fetch());
            } catch (Exception $e) {
                $server = "unknown";
            }
            */
            $fdb = substr($file, 0, strlen($file)-4);
            echo "<li>@{$server} <a href=\"manga.php?db=".urlencode($fdb)."\">{$file}</a></li>\n";
        }
    }
    echo("</ol>");
?>
