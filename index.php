<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Spyfall</title>
    <meta name="author" content="Giorgio Bonvicini">
</head>
<body>

<h1>Spyfall</h1>

<h4>Crea una nuova partita</h4>
<form method="POST" action="newgame.php">
    <label for="name">Nome:</label><br>
    <input type="text" name="name" />
    <br><br>
    <label for="language">Lingua:</label><br>
    <select name="language">
        <option value="IT">Italiano</option>
    </select><br><br>
    <input type="submit" value="Nuova partita"/>
</form>

<br><br>

<h4>Unisciti ad un'altra partita</h4>
<form method="GET" action="play.php">
    <label for="name">Nome:</label><br>
    <input type="text" name="name" />
    <br><br>
    <label for="key">Chiave:</label><br>
    <input type="text" name="key" />
    <br><br>
    <label for="language">Lingua:</label><br>
    <select name="language">
        <option value="IT">Italiano</option>
    </select><br><br>
    <input type="submit" value="Unisciti"/>
</form>

</body>
</html>
