<?php
// Little dirty hack to clean up older databases: this script, which is called
// everytime anyone fires up this page deletes all the files in "db" folder
// which have not been modified since 24 hours.
include_once("cleanup.php");

$keyCode = "";
// Check if a key was specified.
if (isset($_GET['key']) && strlen($_GET['key']) == 6 && ctype_alnum($_GET['key']))
{
    $keyCode = $_GET['key'];
    $dbPath = "db/" . $keyCode . ".db";

    // Check if the database exists.
    if(! file_exists($dbPath))
    {
        $keyCode = "";
    }
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

<h1>Spyfall</h1>

<h2 id="new-match-header">New game</h2>
<form method="POST" action="newgame.php">
    <input type="text" name="name" placeholder="Name">
    <br><br>
    <label for="language">Language:</label> 
    <select name="language">
        <option value="IT">Italiano</option>
        <option value="EN">English</option>
    </select><br><br>
    <input type="submit" value="New game">
</form>

<br><br>

<h2>Join an existing match</h2>
<form method="GET" action="play.php">
    <input type="text" name="name" placeholder="Name">
    <br><br>
    <input type="text" name="key" placeholder="Key" value="<?php echo $keyCode;?>">
    <br><br>
    <label for="language">Language:</label> 
    <select name="language">
        <option value="IT">Italiano</option>
        <option value="EN">English</option>
    </select><br><br>
    <input type="submit" value="Join">
</form>

</body>
</html>
