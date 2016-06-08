<?php
header('Content-Type: application/json; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

include_once('settings.php');

// Exit if no key or action is specified.
if (! (isset($_GET['key']) && isset($_GET['action'])))
{
    $output['error'] = true;
    $output['status'] = "bad-params";
    echo json_encode($output);
    exit;
}

// Verify that key is valid.
if (strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = $_GET['key'];
}
else
{
    $output['error'] = true;
    $output['status'] = "bad-key";
    echo json_encode($output);
    exit;
}

$dbPath = "db/" . $keyCode . ".db";

// Verify database existence.
if(! file_exists($dbPath))
{
    $output['error'] = true;
    $output['status'] = "bad-key";
    echo json_encode($output);
    exit;
}

// Open database connection.
if ($db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE))
{
    // Verify that the player is the host of match (using session_id).
    $stmt = $db->prepare("SELECT * FROM Players WHERE (SessID=:sessID AND Host=1)");
    $stmt->bindValue(":sessID", session_id());
    $player = $stmt->execute()->fetchArray();
    if (! $player)
    {
        $output['error'] = true;
        $output['status'] = "bad-auth";
        echo json_encode($output);
        exit;
    }

    if ($_GET['action'] == "play")
    {
        // Start the match.
        
        if (! isset($_GET['timestamp']))
        {
            $output['error'] = true;
            $output['status'] = "bad-params";
            echo json_encode($output);
            exit;
        }
        
        // Check if timestamp is valid.
        if (! ctype_digit($_GET['timestamp']))
        {
            $output['error'] = true;
            $output['status'] = "bad-timestamp";
            echo json_encode($output);
            exit;
        }
        
        // Update the match status.
        
        // TODO: Regulate match duration counting players.
        // Set new match to playing and choose the location.
        $stmt = $db->prepare("INSERT INTO Match (Location,Playing,EndTime) VALUES (:loc,1,:endTime)");
        $stmt->bindValue(":loc", rand(0, $numberOfLocations - 1));
        $stmt->bindValue(":endTime", $_GET['timestamp'] + 10*60);
        $stmt->execute();
        
        // TODO: Assign random roles using file with roles.
        // Assign a random role to everyone.
        $stmt = $db->prepare("SELECT Name FROM Players");
        $res = $stmt->execute();
        $players = array();
        while ($r = $res->fetchArray())
        {
            $stmt1 = $db->prepare("UPDATE Players SET Role=:role WHERE Name=:name");
            $stmt1->bindValue(":role", rand(1, $numberOfRoles)); // 0 is the spy.
            $stmt1->bindValue(":name", $r['Name']);
            $stmt1->execute();
            $players[] = $r['Name'];
        }
        
        // Choose a random player to be the spy.
        $stmt = $db->prepare("UPDATE Players SET Role=0 WHERE Name=:name");
        $stmt->bindValue(":name", $players[rand(0, sizeof($players) - 1)]);
        $stmt->execute();
        $output['error'] = false;
        $output['message'] = "game-started";
        echo json_encode($output);
        exit;
    }
    else if ($_GET['action'] == "stop")
    {
        // Stop the match.
        
        // Update to "No match going on".
        $stmt = $db->prepare("DELETE FROM Match");
        $stmt->execute();
        
        $output['error'] = false;
        $output['message'] = "game-stopped";
        echo json_encode($output);
        exit;
    }
    else
    {
        // Wrong action.
        $output['error'] = true;
        $output['status'] = "bad-params";
        echo json_encode($output);
        exit;
    }
}
else
{
    // Unknown error opening the database.
    $output['error'] = true;
    $output['status'] = "database-unknown-error";
    echo json_encode($output);
    exit;
}

?>
