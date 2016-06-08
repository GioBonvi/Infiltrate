<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Spyfall</title>
    <meta name="author" content="Giorgio Bonvicini">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' type='text/css' href='https://fonts.googleapis.com/css?family=Marvel:700,400'>
    <link rel='stylesheet' type='text/css' href="main.css">
</head>
<body>

<h1>Spyfall</h1>

<h2>Crea una nuova partita</h2>
<form method="POST" action="newgame.php">
    <input type="text" name="name" placeholder="Nome">
    <br><br>
    <label for="language">Lingua:</label> 
    <select name="language">
        <option value="IT">Italiano</option>
    </select><br><br>
    <input type="submit" value="Nuova partita">
</form>

<br><br>

<h2>Unisciti ad un'altra partita</h2>
<form method="GET" action="play.php">
    <input type="text" name="name" placeholder="Nome">
    <br><br>
    <input type="text" name="key" placeholder="Chiave">
    <br><br>
    <label for="language">Lingua:</label> 
    <select name="language">
        <option value="IT">Italiano</option>
    </select><br><br>
    <input type="submit" value="Unisciti">
</form>

</body>
</html>
