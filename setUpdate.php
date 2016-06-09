<?php
header('Content-Type: application/json; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

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
        // Start a match if there is not another match going on.
        
        // Check that no other match is active.
        $stmt = $db->prepare("SELECT Playing FROM Match LIMIT 1");
        $res = $stmt->execute()->fetchArray();
        if ($res['Playing'] == 1)
        {
            $output['error'] = true;
            $output['status'] = "already-playing";
            echo json_encode($output);
            exit;
        }
        
        // Choose a random location.
        // Use EN.json, but it would be the same with every other file.
        $array = json_decode(file_get_contents("lang/EN.json"));
        $numberOfLocations = sizeof($array->locations); 
        $locationIndex = rand(0, $numberOfLocations - 1);
        // Thh duration depends on the number of players.
        $stmt = $db->prepare("SELECT Name FROM Players");
        $res = $stmt->execute();
        $playersN = 0;
        while ($res->fetchArray())
        {
            $playersN = $playersN + 1;
        }
        // Duration = 10 minutes if 5 or less players.
        // Duration = number of players + 5 if more than 5 players.
        $duration = ($playersN <= 5 ? 10 : $playerN + 5) * 60;
        
        $stmt = $db->prepare("INSERT INTO Match (Location,Playing,EndTime,Paused) VALUES (:loc,1,:endTime,0)");
        $stmt->bindValue(":loc", $locationIndex);
        $stmt->bindValue(":endTime", time() + $duration);
        $stmt->execute();
        
        // Assign a random role to everyone.
        $array = json_decode(file_get_contents("lang/EN.json"));
        $numberOfLocations = sizeof($array->locations); 
        $numberOfRoles = sizeof($array->locations[$locationIndex]->roles); // Use EN, but it would be the same with every other file.
        $stmt = $db->prepare("SELECT Name FROM Players");
        $res = $stmt->execute();
        $players = array();
        while ($r = $res->fetchArray())
        {
            $stmt1 = $db->prepare("UPDATE Players SET Role=:role,First=0 WHERE Name=:name");
            $stmt1->bindValue(":role", rand(1, $numberOfRoles)); // 0 is the spy.
            $stmt1->bindValue(":name", $r['Name']);
            $stmt1->execute();
            $players[] = $r['Name'];
        }
        
        // Choose a random player to be the spy.
        $stmt = $db->prepare("UPDATE Players SET Role=0 WHERE Name=:name");
        $stmt->bindValue(":name", $players[rand(0, sizeof($players) - 1)]);
        $stmt->execute();
        
        // Choose a random player to be the first.
        $stmt = $db->prepare("UPDATE Players SET First=1 WHERE Name=:name");
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
    else if ($_GET['action'] == "pause")
    {
        // Pause or resume the clock.
        
        // Check that a match is going on and is not already paused.
        $stmt = $db->prepare("SELECT Playing,Paused,EndTime FROM Match LIMIT 1");
        $res = $stmt->execute();
        $match = $res->fetchArray();
        if ($match['Playing'] != 1 || $match['Paused'] != 0)
        {
            $output['error'] = true;
            $output['status'] = "error";
            echo json_encode($output);
            exit;
        }
        
        $endTime = $match['EndTime'];
        $timeRemaining = $endTime - time();
        // Check if game is already over or not.
        if($timeRemaining > 0)
        {
            $stmt = $db->prepare("UPDATE Match SET Paused=:remaining WHERE EndTime=:endTime");
            $stmt->bindValue(":remaining", $timeRemaining);
            $stmt->bindValue(":endTime", $endTime);
            $stmt->execute();
            
            $output['error'] = false;
            $output['message'] = "game-paused";
            echo json_encode($output);
            exit;
        }
        else
        {
            $output['error'] = false;
            $output['message'] = "game-over";
            echo json_encode($output);
            exit;
        }
    }
    else if ($_GET['action'] == "resume")
    {
        // Resume a paused game.
        
        // Check that a match is going on and is paused.
        $stmt = $db->prepare("SELECT Playing,Paused,EndTime FROM Match LIMIT 1");
        $res = $stmt->execute();
        $match = $res->fetchArray();
        if ($match['Playing'] != 1 || $match['Paused'] == 0)
        {
            $output['error'] = true;
            $output['status'] = "error";
            echo json_encode($output);
            exit;
        }
        
        $endTime = time() + $match['Paused'];
        
        $stmt = $db->prepare("UPDATE Match SET Paused=0,EndTime=:endTime WHERE EndTime=:oldEndTime");
        $stmt->bindValue(":endTime", $endTime);
        $stmt->bindValue(":oldEndTime", $match['EndTime']);
        $stmt->execute();
        
        $output['error'] = false;
        $output['message'] = "game-resumed";
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
