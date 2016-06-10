<?php
header('Content-Type: application/json; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

// Exit if no key.
if (! isset($_GET['key']))
{
    $output['error'] = true;
    $output['status'] = "err-bad-params";
    echo json_encode($output);
    exit;
}

// Check if key is valid.
if (strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = $_GET['key'];
}
else
{
    $output['error'] = true;
    $output['status'] = "err-bad-key";
    echo json_encode($output);
    exit;
}

$dbPath = "db/" . $keyCode . ".db";

// Check database exists.
if(! file_exists($dbPath))
{
    $output['error'] = true;
    $output['status'] = "err-bad-key";
    echo json_encode($output);
    exit;
}

if ($db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE))
{
    // Check if the player is in the match (using session_id).
    $stmt = $db->prepare("SELECT * FROM Players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $player = $stmt->execute()->fetchArray();
    if (! $player)
    {
        $output['error'] = true;
        $output['status'] = "err-bad-auth";
        echo json_encode($output);
        exit;
    }
    
    // Send only relevant data to the player.
    $stmt = $db->prepare("SELECT Name,First FROM Players");
    $res = $stmt->execute();
    while ($r = $res->fetchArray())
    {
        $players[] = $r;
    }
    $stmt = $db->prepare("SELECT Location,Playing,EndTime,Paused FROM Match LIMIT 1");
    $match = $stmt->execute()->fetchArray();
    $match['TimeLeft'] = $match['EndTime'] - time();
    $output['error'] = false;
    $output['player'] = $player;
    $output['players'] = $players;
    $output['match'] = $match;
    echo json_encode($output);
    exit;
}
else
{
    $output['error'] = true;
    $output['status'] = "err-database-unknown";
    echo json_encode($output);
    exit;
}

?>
