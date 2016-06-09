<?php
$databases = scandir("db");
foreach($databases as $db)
{
    $file = "db/$db";
    $olderThanOneday = (time() - filemtime($file)) > 86400;
    // Exclude hidden files and symlinks.
    if (substr($db, 0, 1) != "." && $olderThanOneday)
    {
        unlink($file);
    } 
}
?>
