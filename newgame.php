<?php
header('Content-Type: text/html; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

// Exit if some required parameter is missing.
if (! (isset($_POST['name']) && isset($_POST['language'])))
{
    header("Location: index.php?error=param-not-set");
    exit;
}

// Check host's name.
if (preg_match("/^[a-zA-Z0-9]+$/", $_POST['name']))
{
    $name = $_POST['name'];
}
else
{
    header("Location: index.php?error=bad-name");
    exit;
}

// Check users's language.
if (isset($_GET['language']) && file_exists("lang/" . $_GET['language'] . ".json"))
{
    $language = $_GET['language'];
}
else
{
    $language = "EN";
}

// Generate the keyCode for the match (6 alphanumeric random characters).
do
{
    $keyCode = substr(md5(rand()), 0, 6);
    $dbPath = "db/" . $keyCode . ".db";
} while (file_exists($dbPath));

// Check host's name.
$name = preg_match("/^[a-z0-9]+$/i", $_POST['name']) ? $_POST['name'] : "admin";
// Check host's language.
if (isset($_POST['language']) && file_exists("lang/" . $_POST['language'] . ".json"))
{
    $language = $_POST['language'];
}
else
{
    $language = $_POST['language'];
}

// Create and initialize the SQLite database.
if ($db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE))
{ 
    $db->exec("CREATE TABLE 'Players' ('SessID' TEXT NOT NULL UNIQUE, 'Name' TEXT NOT NULL UNIQUE, 'Host' INTEGER NOT NULL DEFAULT 0, 'Role' INTEGER NOT NULL, 'First' INTEGER NOT NULL, 'Lang' TEXT NOT NULL);");
    $db->exec("CREATE TABLE 'Match' ('Location' INTEGER NOT NULL, 'Playing' INTEGER NOT NULL DEFAULT 0, 'EndTime' INTEGER NOT NULL, 'Paused' INTEGER NOT NULL);");
    echo "Database was created and initialized.<br>";   
    
    // Insert host's data into database.
    $stmt = $db->prepare("INSERT INTO Players (SessID,Name,First,Host,Role,Lang) VALUES (:sessID,:name,0,1,:role,:lang)");
    $stmt->bindValue(":sessID", session_id());
    $stmt->bindValue(":name", $name);
    $stmt->bindValue(":role", -1);
    $stmt->bindValue(":lang", $language);
    if ($stmt->execute())
    {
        echo "Host's data insertion completed successfully<br>";
        header("Location: play.php?key=$keyCode&name=$name&language=$language");
        exit;
    }
    else
    {
        header("Location: index.php?error=database-insert-host");
        exit;
    }
}
else
{
    header("Location: index.php?error=database-unknown");
    exit;
}
?>
