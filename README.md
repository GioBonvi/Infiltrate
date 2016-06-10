# Spyfall

Spyfall is a social game, meaning it has to be played _in real life_, _vis a vis_, like at a party or with your friends.

You can play it on [my website](http://spyfall.bonvi.atervista.org) or serve it on your own server.

## How to play

It works like this: all the people playing know they are in a certain location (e.g. the supermarket) and have a role (e.g cashier, manager, customer...) execpt for one person, who is the spy. The spy does not have a role nor does know the location.

One of the players is randomly chosen to be the first, and he/she will start the game by asking a question to another player about the location; that player will answer the question and then proceed to ask another question to another player and so on. The spy wins if he/she can guess the location from the questions and the asnswer and the "normal" players win if they guess who the spy is.

The difficulty (and the fun) of the game lies in the choice of the right questions, precise enough to understand if the player answering knows where he/she is, but vague or difficult enough so that the spy does not understand which is the location.

The spy can try to guess the location at any time by asking "Is it the GUESSED LOCATION HERE?". If that's correct the spy wins, if it's wrong everybody else wins.

Any player can call a vote against any other player by stating who he thinks the spy is: if the majority of the players agrees the suspected player is **accused** and must reveal if he was or not the spy. If he was not then the spy wins, but if he was he can try a wild guess; if he gets it right he wins, otherwise everybody else wins.

The spy automatically wins if the timer reaches "00:00" (there could be slight differences between the timers, but nothing more than one second).

The first player needs to create a new game in the main page: he will be the **host** who is in charge of starting/ending the matches and eventually kicking players from the match (coming soon).

Remember the social nature of this game: don't be distracted by your smartphone and use it only when necessary: focus on people around you and try to call the spy's bluff! (Pro-tip: it also makes you more _spyish_ if you constantly look down and the locations never interacting and avoiding eye contact).

## How to use the website

Once the host has created the match he is given a 6 characters (letters and numbers) code which he has to share with the other players: that code must be inserted in the _key_ input of the "Join an existing game" form in the main page. The host is also provided with a link which prefills the form with the code: he only has to send the link to the players.

Once everyone has joined the game it's up to the host starting the match: once he does it the server will automatically pick a random location and a random role for everyone: the roles might be duplicated (two people can have the same role), but only one player can be the spy.

The page will update displaying:
 - a timer stating the time limit for this match (it depends on how many players have joined)
 - a text with player's username, role and location (if the player is not the spy)
 - a list of all players in the match: clicking on one of them will strike him out; this is useful for _good_ players to exclude other _good_ players and guess who is the spy
 - a list of all the possible locations, which can be _erased_ the same way; this is useful for the spy to guess the place through exclusion, but also to _good_ players to keep track of what the spy could know and avoid wrong revelating questions
 
## Where does this come from?

This is a long story: I discovered this game on a Youtube channel ([NODE](https://www.youtube.com/watch?v=zDqlSq6NWSU)) and I immediately fell in love with it. I played it for some time (the site as they say in the video is [Spyfall](http://spyfall.crabhat.com)) with my friends and we had lots of fun, but at a certain point I started to feel that _itch_ on my fingers which, as I learned to understand, meant "Wow, that's great, but shouldn't it be possibile to do this? And what is that? I wish it did this and not that...".

So, knowing that the project was [open source](https://github.com/evanbrumley/spyfall) I read the source code to have a general idea of the project... and ended up doing pretty much everything _my way_ (which means clunky, messy, strange and probably completely impossible to understand code): however thank you [Evan](https://github.com/evanbrumley/) it would have not been possible without you!

I ended up using quite a differente setup for this project as I had some limitations on the software side: I cannot afford a personal server or a paid cloud server so I have to stick with free alternatives: the one I am currently using is [Altervista](http://altervista.org) which offers a good free service without unwanted ads or strong limitations, but it only offers PHP and MySQL database.

By the way Spyfall was not invented nor by me nor by evanbrumley:

"Spyfall is a party game designed by Alexandr Ushan and published by Hobby World. This is an unofficial fan project designed to complement the physical game, and is not endorsed in any way by the designer or publisher."

## How to contribute

The whole project is completely open source and licensed under the [GNU GPL v.3 license](https://github.com/GioBonvi/Spyfall/blob/master/LICENSE) so feel free to create your own version.

If you want to contribute to the translations please use EN.json or IT.json as a base model to create new languages. A translation can be accepted only if it 100% complete, otherwise it won't work. The order of the locations and roles must be the same in every file.

## How to run your on your own server

Simply clone the git repository into your webserver
````
git clone https://github.com/GioBonvi/Spyfall
````
and make sure the web server has write access to the db folder.

That's it! you're done! I tried to make this the most portable I could ;)

## How does it work?

The server side (pages 'setUpdate.php' and 'getUpdate.php') are PHP scripts which connect to SQLite databases (one for each game). I chose PHP because it's the only language supported by my free server and SQLite over MySQL for portability

The server-side pages answer the requests with simple JSON messages, which are elaborated by Javascript in the client-side ('play.php').

Authentication is based on simple PHP session management (via cookies).

## What did you change?

With my version I changed some behaviours and part of the structure of the game, mainly little changes without great impact, but I like them:

 - I think my version has less connection problem (ghost players, match not starting), but it could also be due to a lessere load of the server
 
 - I built the game around the idea that each match has a **host** who is the only one who has some _powers_ over other users: he starts and ends the matches, he can kick players. This solved some problems I experienced in the other version and prevents users from randomly kicking other players
 
 - thanks to PHP session management people can easily rejoin a game if they closed the page: refreshing the page has no influence on the game

 - I added three spy-themed music tracks at the end of the page, which can be activated only if wanted (save Internet data)
 
 - I added a banner alerting about cookie use
 
 - language can be changed more easily and during the game as well
 
 - error messages are actively shown explaining what went wrong

## What's up next?

These are the features I am thinking of implementing:

 - new roles and locations (obvioulsy)
 
 - translations (you can already contribute via GitHub, but I'd like to add an easier way)
 
 - the host should be able to kick players
 
 - easy way to change your name in-game

 - another way to share the link: QR code

 - add a logging system (no IP, only country of origin and statistics for the game)

 - a voting system (requested by those who don't play vis-a-vis, but via Skype or similar applications)
