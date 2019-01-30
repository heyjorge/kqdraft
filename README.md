# kqdraft
Killer Queen Draft/Mixer App

# Backend instructions
* Alter src/settings.php and src/trueskill.php to change database settings to point to a postgresql server
* Run schema file against postgresql - it will create a database called kqdraft with the proper structure
* Run composer install 
* Serve the public folder via apache - or run it locally on port 8080 using the php webserver command that's also in composer.json

# Front end instructions
* Install gem, sass, compass, npm, bower
* Run npm install / bower install
* Change the API hostname/URL in the src/kqdraft.js file
* Run `grunt` to build to build/production folder and serve the files there in apache. Alternatively run `grunt dev:server` to run a local nodejs server that will serve the files
