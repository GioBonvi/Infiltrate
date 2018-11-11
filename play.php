<?php
header('Content-Type: text/html; charset=utf-8');
session_start(['cookie_lifetime' => 86400]);

// Exit if no name or key
if (! (isset($_GET['name']) && isset($_GET['key'])))
{
    header("Location: index.php?err-bad-params");
}

// Check users's language.
if (! isset($_COOKIE['language']) || ! file_exists("lang/" . $_COOKIE['language'] . ".json"))
{
    $_COOKIE['language'] = "EN";
}

// Check if key is valid.
if (strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = strtolower($_GET['key']);
}
else
{
    header("Location: index.php?error=err-bad-key");
    exit;
}

$dbPath = "db/" . $keyCode . ".db";

// Check database exists.
if(! file_exists($dbPath))
{
    header("Location: index.php?error=err-bad-key");
    exit;
}

if ($db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE))
{
    // Check user's name is valid and not already taken by some other player.
    if (preg_match("/^[a-zA-Z0-9]+$/", $_GET['name']))
    {
        $name = $_GET['name'];
        
        $stmt = $db->prepare("SELECT Count(*) FROM Players WHERE (Name=:name AND SessID<>:sessID)");
        $stmt->bindValue(":name", $name);
        $stmt->bindValue(":sessID", session_id());
        $res = $stmt->execute()->fetchArray();
        if ($res['Count(*)'] != 0)
        {
            header("Location: index.php?error=err-bad-name");
            exit;
        }
    }
    else
    {
        header("Location: index.php?error=err-bad-name");
        exit;
    }
    
    
    // If the match is going on the player can connect only if he
    // was already in the match.
    $stmt = $db->prepare("SELECT Playing FROM Match LIMIT 1");
    $match = $stmt->execute()->fetchArray();
    $stmt = $db->prepare("SELECT Count(*) FROM Players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $player = $stmt->execute()->fetchArray();
    if ($match['Playing'] == 1)
    {
        if ($player['Count(*)'] != 1)
        {
            header("Location: index.php?error=err-match-active");
            exit;
        }
    }
    

    // Check if this player is already registered in this match (using session_id).
    $stmt = $db->prepare("SELECT * FROM players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $res = $stmt->execute();
    $res = $res->fetchArray();
    
    if ($res)
    {
        // Already registered in this match.
        // Update the values.
        $stmt = $db->prepare("UPDATE players SET Name=:name WHERE SessID=:sessID");
        $stmt->bindValue(":name", $name);
        $stmt->bindValue(":sessID", session_id());
        if (! $stmt->execute())
        {
            header("Location: index.php?error=err-relogin-database-update");
            exit;
        }
    }
    else
    {
        // New player in this match.
        
        // Register him.
        $stmt = $db->prepare("INSERT INTO players (SessID,Name,First,Host,Role) VALUES (:sessID,:name,0,0,:role)");
        $stmt->bindValue(":sessID", session_id());
        $stmt->bindValue(":name", $name);
        $stmt->bindValue(":role", -1);
        if (! $stmt->execute())
        {
            header("Location: index.php?error=err-database-register-player");
            exit;
        }
    }
    
    // Get player infos.
    $stmt = $db->prepare("SELECT * FROM players WHERE SessID=:sessID");
    $stmt->bindValue(":sessID", session_id());
    $player = $stmt->execute()->fetchArray();
    if (! $player)
    {
        header("Location: index.php?error=err-database-get-player-infos");
        exit;
    }
}
else
{
    header("Location: index.php?error=err-database-unknown");
    exit;
}

// Now the page will GET getUpdate.php?key=$key every x seconds.
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Infiltrate</title>
    <meta name="author" content="Giorgio Bonvicini">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f1ba">
    
    <link rel='stylesheet' type='text/css' href='https://fonts.googleapis.com/css?family=Marvel:700,400'>
    <link rel='stylesheet' type='text/css' href="main.css">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
</head>
<body>
<script>
// Setup language.
$.ajax({
    url:"lang/<?php echo $_COOKIE['language'];?>.json",
    async: false
})
.done(function(data){
    // Save localized strings in resource.
    resource = data;
});
</script>
<h1>Infiltrate</h1>
<script>shareLink = window.location.href.replace("play.php", "index.php").replace(/&*name=[a-zA-Z0-9]*&*/g, "");</script>
<p id="share-link">To invite other players share this link:</p>
<p><script>document.write(shareLink);</script></p>

<div id="share">
<a id="share-whatsapp"><button>Whatsapp</button></a> <a id="share-email" target="_blank"><button>E-mail</button></a> <button id="share-copy">Copy</button> <button id="share-qr">QR Code</button>
</div>

<div id="qr-code" hidden>

</div>

<script>
$("#qr-code").qrcode({ maxVersion: 20, background: "#fcf9e2", text: shareLink});
</script>

<br>

<?php include_once("language-select.php"); ?>&nbsp;

<button id="btn-start">Start game</button>

<button id="btn-stop" hidden>Stop game</button>

<br>

<?php if ($player['Host']) { ?>
<div id="kick-player">
    <p id="kick-player-label"></p>
    <select id="kick-player-list">

    </select>
    <button id="kick-player-button">
</div>
<?php } ?>

<br>

<button id="change-name-button">
Change name
</button>

<div id="error-msg">
</div>

<p id="timer"></p>


<h2 id="player-data-header">Player data</h2>
<div id="toggle-player-data">Show/hide</div>
<div id="player-data">
</div>

<h2 id="player-list-header">Players</h2>
<ul id="players-list">
</ul>

<h2 id="location-list-header">Locations</h2>
<ul id="location-list">
</ul>

<br><br>

<button id="show-music">Show the music</button>

<footer id="footer">
<p>This project's source code is available on <a href="https://github.com/GioBonvi/Infiltrate">GitHub</a></p>
<p>Infiltrate is inspired by <a href="http://international.hobbyworld.ru/catalog/25-spyfall/">Spyfall</a>, designed by Alexandr Ushan, published by <a href="http://international.hobbyworld.ru/">Hobby World</a></p>
</footer>

<script>
// Localize the page using the saved strings in "lang" folder.
localize();
// Print out the list  of the locations (localized).
resource['locations'].forEach(function(location, index) {
    $("#location-list").append('<li data="on">' + location['name'] + '</li>');
});
// Clicking on a location strikes it out.
$("#location-list li").click(function() {
    if ($(this).attr("data") == "on")
    {
        $(this).attr("data", "off")
    }
    else
    {
        $(this).attr("data", "on")
    }
});

// This will contain the unix timestamp of the end of the match (for the clock).
endTime = 0;

// Update the page every 5 seconds and the clock every half second.
getUpdate();
setInterval(getUpdate, 5000);
setInterval(setTimer, 500);

// BUTTONS

// Copy link to share to clipboard.
$("#share-copy").click(function() {
    copyTextToClipboard(shareLink);
});

// Show or hide QR Code with link to share.
$("#share-qr").click(function() {
    $("#qr-code").toggle();
});

// Start a new match.
$("#btn-start").click(function()
{
    $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "play"})
    .done(function(data)
    {
        console.log(data);
        if (data['error'])
        {
            console.log(data['status']);
            displayError(getResource(data['status']));
            return;
        }
        getUpdate();
    });
});

// End the current match.
$("#btn-stop").click(function()
{
    $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "stop"})
    .done(function(data)
    {
        console.log(data);
        if (data['error'])
        {
            console.log(data['status']);
            displayError(getResource(data['status']));
            return;
        }
        getUpdate();
    });
});

$("#kick-player-button").click(function() {
    var target = $("#kick-player-list option:selected").val();
    if (target != "")
    {
        $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "kick", target: target})
        .done(function(data)
        {
            console.log(data);
            if (data['error'])
            {
                console.log(data['status']);
                displayError(getResource(data['status']));
                return;
            }
            getUpdate();
        });
    }
});

$("#change-name-button").click(function() {
    var newname = window.prompt(getResource("change-name-text"), "");
    if (newname == null)
    {
        return;
    }
    if (! /^[a-zA-Z0-9]+$/.test(newname))
    {
        displayError(getResource("err-bad-name"));
        return
    }
    
    $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "change-name", name: newname})
    .done(function(data)
    {
        console.log(data);
        if (data['error'])
        {
            console.log(data['status']);
            displayError(getResource(data['status']));
            return;
        }
        getUpdate();
    });
});

// Hide sensitive data (role and location).
$("#toggle-player-data").click(function() {
    $("#player-data").toggle("medium");
});

<?php if($player['Host'] == 1) { ?>
// Pause/resume the timer by clicking on it (host only).
$("#timer").click(function() {
    if ($(this).attr("data") != "paused")
    {
        $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "pause"})
        .done(function(data)
        {
            console.log(data);
            if (data['error'])
            {
                console.log(data['status']);
                displayError(getResource(data['status']));
                return;
            }
            getUpdate();
        });
        $(this).attr("data", "paused");
    }
    else
    {
        $.post("setUpdate.php" , {key: "<?php echo $keyCode;?>", action: "resume"})
        .done(function(data)
        {
            console.log(data);
            if (data['error'])
            {
                console.log(data['status']);
                displayError(getResource(data['status']));
                return;
            }
            getUpdate();
        });
        $(this).attr("data", "playing");
    }
});
<?php }?>
// The music is added to the page only when the user requests it.
// By doing this the user does not have to download 10 MB of data each time he opens this page.
$("#show-music").click(function() {
    $(this).after('<div id="music">\n<div style="text-align: center">\n<p>Skyfall</p>\n<audio style="width:90%" src="media/Skyfall.mp3" controls></audio>\n</div>\n<div style="text-align: center">\n<p>Skyfall (instrumental)</p>\n<audio style="width:90%" src="media/Skyfall (instrumental).mp3" controls></audio>\n</div>\n<div style="text-align: center">\n<p>James Bond theme</p>\n<audio style="width:90%" src="media/James Bond theme.mp3" controls></audio>\n</div>\n</div>');
    $(this).remove();
});

// Update the clock.
function setTimer()
{
    /* endTime can be:
     *  0 -> means the game is not on. The timer is not shown.
     *  -1 -> the game is paused. Don't update the clock, but leave it as it is.
     *  < currentDate -> the game is over: display 00:00
     *  > currentDate -> the game is on: display how much time is left
     */
    if (endTime == 0)
    {
        $("#timer").empty();
    }
    else if (endTime < 0)
    {
        //nop
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
        $("#timer").html(("0" + m).slice (-2) + ":" + ("0" + s).slice (-2));
    }
}

// Get an update from the server.
function getUpdate()
{
    $.get("getUpdate.php", {key: "<?php echo $keyCode;?>"})
    .done(function(data) {
        console.log(data);
        if (data['error'])
        {
            console.log(data['status']);
            displayError(getResource(data['status']));
            if (data['status'] == "err-bad-auth")
            {
                window.location.href = "index.php";
            }
            return;
        }
        
        if (data['match']['Playing'])
        {
            // Show the collected data (game is on).
            if (data['match']['Paused'] == 0)
            {
                endTime = Math.floor(Date.now()/1000) + data['match']['TimeLeft'];
            }
            else
            {
                endTime = -1;
            }
            
            $("#toggle-player-data").show();
            $("#player-data").empty();
            var name = "<p>" + getResource("name") + ": " + data['player']['Name'] + "</p>";
            var role = "<p>" + getResource("role") + ": " + getRole(data['match']['Location'], data['player']['Role']) + "</p>";
            var location = "<p>" + getResource("location") + ": " + (data['player']['Role'] != 0 ? getLocation(data['match']['Location']) : getResource("unknown")) + "</p>";
            $("#player-data").html(name + role + location);
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
            
            $("#kick-player").hide();
        }
        else
        {
            // Show the collected data (game is off).
            endTime = 0;
            
            $("#toggle-player-data").hide();
            $("#player-data").empty();
            $("#player-data").show();
            $("#player-data").html(getResource("not-playing"));
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
            
            // Generate new list of players to kick.
            
            $("#kick-player-list").empty();
            $("#kick-player-list").append("<option data-name=\"\">" + getResource("nobody") + "</option>");
            data['players'].forEach(function (player, index) {
                if (player.Name != "<?php echo $player['Name'];?>")
                {
                    $("#kick-player-list").append("<option data-name=\"" + player.Name + "\">" + player.Name + "</option>");
                }
            });
            $("#kick-player").show();
        }
        
        // Update player list.
        var oldplayersOff = [];
        // Maintain a list of "off" players.
        $("#players-list li").each(function() {
            if ($(this).attr("data") == "off")
            {
                oldplayersOff.push($(this).attr("data-name"));
            }
        });
        $("#players-list").empty();
        // Reapply "off" status to the player in the list.
        data['players'].forEach(function(player, index) {
            var name = player['Name'];
            $("#players-list").append('<li data="' + (oldplayersOff.indexOf(name) >= 0 ? "off" : "on") + '" data-name="' + name + '">' + name + (player['First'] == 1 ? ' (' + getResource("first") + ')' : '') +'</li>');
        });
        // When a player name is clicked it's set to strikethrough.
        $("#players-list li").click(function() {
            if ($(this).attr("data") == "on")
            {
                $(this).attr("data", "off")
            }
            else
            {
                $(this).attr("data", "on")
            }
        });
    })
}

// Get a location from the localized lilst of locations.
function getLocation(index)
{
    return resource["locations"][index]["name"];
}

// Given a location get a role from the localized lilst of roles for that location.
function getRole(location, index)
{
    return resource["locations"][location]['roles'][index];
}

// Localize the page by inserting the localized strings.
function localize()
{
    $("#share-link").html(getResource("share-link"));
    $("#share-whatsapp").attr("href", "whatsapp://send?text=" + shareLink);
    $("#share-email").attr("href", "mailto:?subject=" + getResource("mail-subj") + "&body=" + getResource("mail-body") + shareLink);
    $("#share-copy").html(getResource("copy"));
    $('label[for="language"]').html(getResource("language") + "&nbsp");
    $("#player-data-header").html(getResource("player-data-header"));
    $("#toggle-player-data").html(getResource("toggle-player-data"));
    $("#player-list-header").html(getResource("player-list-header"));
    $("#location-list-header").html(getResource("location-list-header"));
    $("#btn-start").html(getResource("btn-start"));
    $("#btn-stop").html(getResource("btn-stop"));
    $("#kick-player-label").html(getResource("kick-player-label"));
    $("#kick-player-button").html(getResource("kick-player-button"));
    $("#change-name-button").html(getResource("change-name-button"));    
    $("#show-music").html(getResource("show-music"));
    $("#footer").html(getResource("footer"));
}

// Get a localized resource out of the list.
function getResource(res)
{
    return resource.text[res];
}

function displayError(err)
{
    $("#error-msg").append("<p>" + err + "</p>");
    setTimeout(function() {$("#error-msg").children().first().remove();}, 5000);
}

function copyTextToClipboard(text)
{
    var textArea = document.createElement("textarea");

    //
    // *** This styling is an extra step which is likely not required. ***
    //
    // Why is it here? To ensure:
    // 1. the element is able to have focus and selection.
    // 2. if element was to flash render it has minimal visual impact.
    // 3. less flakyness with selection and copying which **might** occur if
    //    the textarea element is not visible.
    //
    // The likelihood is the element won't even render, not even a flash,
    // so some of these are just precautions. However in IE the element
    // is visible whilst the popup box asking the user for permission for
    // the web page to copy to the clipboard.
    //

    // Place in top-left corner of screen regardless of scroll position.
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;

    // Ensure it has a small width and height. Setting to 1px / 1em
    // doesn't work as this gives a negative w/h on some browsers.
    textArea.style.width = '2em';
    textArea.style.height = '2em';

    // We don't need padding, reducing the size if it does flash render.
    textArea.style.padding = 0;

    // Clean up any borders.
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';

    // Avoid flash of white box if rendered for any reason.
    textArea.style.background = 'transparent';


    textArea.value = text;

    document.body.appendChild(textArea);

    textArea.select();

    try
    {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        $("#share-copy").prop("disabled", true);
        if (successful)
        {
            setTimeout(function() {$("#share-copy").prop("disabled", false);}, 1500);
        }
    } catch (err) {
        console.log('Error copying');
    }

    document.body.removeChild(textArea);
}
</script>

</body>
</html>
