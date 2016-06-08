<?php
header('Content-Type: text/html; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

include_once('settings.php');

// Exit if no name or key
if (! (isset($_GET['name']) && isset($_GET['key'])))
{
    header("Location: index.php");
}

// Check user's name.
if (preg_match("/^[a-zA-Z0-9]+$/", $_GET['name']))
{
    $name = $_GET['name'];
}
else
{
    header("Location: index.php?error=bad-name");
    exit;
}

// Check users's language.
if (isset($_GET['language']) && in_array($_GET['language'], $okLangs))
{
    $language = $_GET['language'];
}
else
{
    $language = $okLangs[0];
}

// Check key is valid.
if (strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = $_GET['key'];
}
else
{
    header("Location: index.php?error=bad-key");
    exit;
}

$dbPath = "db/" . $keyCode . ".db";

// Check database exists.
if(! file_exists($dbPath))
{
    header("Location: index.php?error=bad-key");
    exit;
}

if ($db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE))
{
    // Check the match is not going on.
    $stmt = $db->prepare("SELECT Playing FROM Match WHERE Playing=1");
    $res = $stmt->execute();
    $res = $res->fetchArray();
    //TODO: remove after debugging.
    /*if ($res)
    {
        header("Location: index.php?error=match-active");
        exit;
    }*/
    

    // Check if this player is already registered in this match (using session_id).
    $stmt = $db->prepare("SELECT * FROM players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $res = $stmt->execute();
    $res = $res->fetchArray();
    
    if ($res)
    {
        // Already registered in this match.
        // Update the values.
        $stmt = $db->prepare("UPDATE players SET Name=:name,Lang=:lang WHERE SessID=:sessID");
        $stmt->bindValue(":name", $name);
        $stmt->bindValue(":lang", $language);
        $stmt->bindValue(":sessID", session_id());
        if (! $stmt->execute())
        {
            header("Location: index.php?error=relogin-database-update");
            exit;
        }
    }
    else
    {
        // New player in this match.
        
        // Register him.
        $stmt = $db->prepare("INSERT INTO players (SessID,Name,Host,Role,Lang) VALUES (:sessID,:name,0,:role,:lang)");
        $stmt->bindValue(":sessID", session_id());
        $stmt->bindValue(":name", $name);
        $stmt->bindValue(":role", rand(1, $numberOfRoles));
        $stmt->bindValue(":lang", $language);
        if (! $stmt->execute())
        {
            header("Location: index.php?error=database-register-player");
            exit;
        }
    }
    
    // Get player infos.
    $stmt = $db->prepare("SELECT * FROM players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $player = $stmt->execute()->fetchArray();
    if (! $player)
    {
        header("Location: index.php?error=database-get-player-infos");
        exit;
    }
}
else
{
    header("Location: index.php?error=database-unknown-error");
    exit;
}

// Now the page will GET getUpdate.php?key=$key every x seconds.
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Spyfall</title>
    <meta name="author" content="Giorgio Bonvicini">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
</head>
<body>

<h1>Spyfall</h1>

<p id="timer"></p>

<h4>Dati personaggio</h4>
<div id="player-data">
</div>

<h4>Giocatori</h4>
<ul id="players-list">
</ul>

<button id="btn-start">Inizio partita</button>

<button id="btn-stop" hidden>Termina partita</button>

<script>
resource = {};
endTime = 0;
// Setup language.
$.get("lang/<?php echo $language;?>.json")
.done(function(data){
    resource = data;
});

getUpdate();
setInterval(getUpdate, 10000);
setInterval(setTimer, 500);

$("#btn-start").click(function()
{
    $.get("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "play", timestamp: Math.floor(Date.now() / 1000)})
    .done(function(data)
    {
        console.log(data);
    });
});

$("#btn-stop").click(function()
{
    $.get("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "stop"})
    .done(function(data)
    {
        console.log(data);
    });
});

function setTimer()
{
    if (endTime == 0)
    {
        $("#timer").empty();
    }
    else if (endTime < Math.floor(Date.now()/1000))
    {
        $("#timer").html("00:00");
    }
    else
    {
        var diff = endTime - Math.floor(Date.now() / 1000)
        var m = Math.floor(diff / 60);
        var s = diff % 60;
        $("#timer").html(m + ":" + s);
    }
}

function getUpdate()
{
    $.get("getUpdate.php", {key: "<?php echo $keyCode;?>"})
    .done(function(data) {
        console.log(data);
        if (data['error'])
        {
            console.log(data['message']);
            return;
        }
        
        // Set status
        if (data['match']['Playing'])
        {
            endTime = Math.floor(Date.now()/1000) + data['match']['TimeLeft'];
            $("#player-data").empty();
            var role = "<p>Ruolo: " + getRole(data['match']['Location'], data['player']['Role']) + "</p>";
            var location = (data['player']['Role'] != 0 ? "<p>Luogo: " + getLocation(data['match']['Location']) + "</p>" : "");
            $("#player-data").html(role + location);
            if (data['player']['Host'])
            {
                $("#btn-start").hide();
                $("#btn-stop").show();
            }
            else
            {
                $("#btn-start").hide();
                $("#btn-stop").hide();
            }
        }
        else
        {
            endTime = 0;
            $("#player-data").empty();
            $("#player-data").html("Non stai ancora giocando...");
            if (data['player']['Host'])
            {
                $("#btn-start").show();
                $("#btn-stop").hide();
            }
            else
            {
                $("#btn-start").hide();
                $("#btn-stop").hide();
            }              
        }
        
        // Update player list.
        var oldplayersOff = [];
        // Compare with the old list of players.
        $("#players-list li").each(function() {
            if ($(this).attr("data") == "off")
            {
                oldplayersOff.push($(this).text());
            }
        });
        
        $("#players-list").empty();
        
        data['players'].forEach(function(player, index) {
            var name = player['Name'];
            if (oldplayersOff.indexOf(name) >= 0)
            {
                $("#players-list").append('<li data="off">' + name + '</li>');
            }
            else
            {
                $("#players-list").append('<li data="on">' + name + '</li>');
            }
        });
    })
}

function getLocation(index)
{
    return resource["locations"][index]["name"];
}

function getRole(location, index)
{
    return resource["locations"][location]['roles'][index];
}
</script>

</body>
</html>
