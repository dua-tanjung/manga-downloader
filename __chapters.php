<?php
    $dbname = isset($_GET['db']) ? $_GET['db'] : "^_^";
    $dbdir = dirname(__file__);
    $separator = (substr($dbdir, 0, 1)=='/') ? '/' : "\\";
    
    $fname = "{$dbdir}{$separator}__data{$separator}{$dbname}.mga";
    if (!file_exists("{$fname}")) {
        die("Database file not found.\n{$fname} not found in filesystem.");
    }
    
    $dbh = new PDO("sqlite:{$fname}");
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stm = $dbh->query("select CHAPTERID, CHAPTER, LINK, PAGES, coalesce(DONECOUNT,0) DONE from chapters order by ChapterId");
    //$rst = $stm->fetch(PDO::FETCH_ASSOC);
    
    echo "<table class='data'>\n<tr><th>ID</th><th>Chapter</th><th>Link</th><th>Pages</th><th>Done</th><th>Action</th></tr>\n";
    foreach ($stm as $cep) {
        $id = $cep['CHAPTERID'];
        echo "<tr>
            <td id='id{$id}'>{$id}</td>
            <td id='ch{$id}'><a href='javascript:readChapter({$id},\"".urlencode($cep['CHAPTER'])."\")'>{$cep['CHAPTER']}</a></td>
            <td id='lnk{$id}'>{$cep['LINK']}</td>
            <td id='pg{$id}'>{$cep['PAGES']}</td>
            <td id='don{$id}'>{$cep['DONE']}</td>".
            "<td>
                <a href='javascript:skipChapter({$id})'>[skip]</a>
                <a href='javascript:openPages({$id})'>[view pages]</a>
                <a href='javascript:downloadChapter({$id},\"{$cep['CHAPTER']}\")'>[zip]</a>
            </td></tr>\n";
    }
    echo "</table>\n";
?>