<?php
// Little dirty hack to clean up older databases: this script, which is called
// everytime anyone fires up this page deletes all the files in "db" folder
// which have not been modified since 24 hours.
include_once("cleanup.php");

$keyCode = "";
// Check if a key was specified.
if (isset($_GET['key']) && strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = strtolower($_GET['key']);
    $dbPath = "db/" . $keyCode . ".db";

    // Check if the database exists.
    if(! file_exists($dbPath))
    {
        $keyCode = "";
    }
}

if (! isset($_COOKIE['language']) || ! file_exists("lang/" . $_COOKIE['language'] . ".json"))
{
    setcookie("language", "EN", time() + 60*60*24*30);
}

?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Spyfall</title>
    <meta name="author" content="Giorgio Bonvicini">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' type='text/css' href='https://fonts.googleapis.com/css?family=Marvel:700,400'>
    <link rel='stylesheet' type='text/css' href="main.css">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="cookies.js"></script>
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

<h1>Spyfall</h1>
<div class="container" id="error-msg">
<?php
if (isset($_GET['error']))
{
    $error = $_GET['error'];
    $resource = json_decode(file_get_contents("lang/{$_COOKIE['language']}.json"), TRUE);
    if (isset($resource['text'][$error]))
    {
        echo "<p>" . $resource['text'][$error] . "</p>";
    }    
}
?>
</div>
<div class="container">
    <div>
        <h2 id="new-match-header">New game</h2>
        <form method="POST" action="newgame.php">
            <input type="text" name="name" placeholder="Name">
            <br><br>
            <input id="btn-newgame" type="submit" value="New game">
        </form>
    </div>
    <div>
        <h2 id="join-match-header">Join an existing match</h2>
        <form method="GET" action="play.php">
            <input type="text" name="name" placeholder="Name">
            <br><br>
            <input type="text" name="key" placeholder="Key" value="<?php echo $keyCode;?>">
            <br><br>
            <input id="btn-join" type="submit" value="Join">
    </form>
    </div>
</div>
<div class="container">
<?php include("language-select.php"); ?>
</div>
<br>
<div class="container">
<button id="btn-help">Help</button>
</div>
<div id="help" hidden>
    <p>Welcome to Spyfall! In this game one of the players will be chosen to be the spy: he will have to infiltrate among the other player using his <i>intuition and spying skills</i>: everyone will be in a common location and everyone except the spy will know the location (e.g. "the supermarket") and the role they are assigned (e.g. "cashier" or "customer").<br>One of the players is randomly chosen to be the first, and he/she will start the game by asking a question to another player about the location; that player will answer the question and then proceed to ask another question to another player and so on. The spy wins if he/she can guess the location from the questions and the asnswers, while the "normal" players win if they guess who the spy is.<br>The difficulty (and the fun) of the game lies in the choice of the right questions, precise enough to call the bluff of the spy, but vague or difficult enough so that the spy does not understand the location.</p>
    <p>The spy can try to guess the location at any time (for example by asking "Are we in the military base?"). If that's correct the spy wins, if it's wrong everybody else wins. Any player can call a vote against any other player by stating who he thinks the spy is: if the majority of the players agrees the suspected player is <b>accused</b> and must reveal if he was or not the spy. If he was not then the spy wins, but if he was he can try a wild guess; if he gets it right he wins, otherwise everybody else wins. The spy automatically wins if the timer reaches "00:00".</p>
    <p>Remember the social nature of this game: don't be distracted by your smartphone and use it only when necessary: focus on people around you and try to call the spy's bluff! (Pro-tip: it also makes you more <i>spyish</i> if you constantly look down and the locations never interacting and avoiding eye contact)</p>
</div>
<footer id="footer">
<p>This project's source code is available on <a href="https://github.com/GioBonvi/Spyfall">GitHub</a></p>
<p><a href="http://international.hobbyworld.ru/catalog/25-spyfall/">Spyfall</a> is designed by Alexandr Ushan, published by <a href="http://international.hobbyworld.ru/">Hobby World</a></p>
</footer>

<script>
    // Localize the page controls.
    $("#btn-help").click(function() {
        $("#help").toggle("medium");
    });
    $("#new-match-header").html(getResource("new-match-header"));
    $('input[name="name"]').attr("placeholder", getResource("name"));
    $("#btn-newgame").val(getResource("btn-newgame"));
    $("#join-match-header").html(getResource("join-match-header"));
    $('input[name="key"]').attr("placeholder", getResource("key"));
    $("#btn-join").val(getResource("btn-join"));
    $('label[for="language"').html(getResource("language") + "&nbsp");
    $("#btn-help").html(getResource("btn-help"));
    $("#help").html(getResource("help"));
    $("#footer").html(getResource("footer"));

    
    
    // Get a localized resource out of the list.
    function getResource(res)
    {
        return resource.text[res];
    }
</script>
</body>
</html>
