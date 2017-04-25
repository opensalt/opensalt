Download Docker
	Download the Docker application and install it. (For Macs, this just goes in your Applications/ directory)
	For some systems you will also need to install Docker Compose; the Mac and Windows Docker applications include Compose, so there's no need to do this for Mac and Windows.
	For Macs, open Docker, then go to Preferences > File Sharing and remove the three directories "/Users", "/Volumes", and "/private" (see the d4m-nfs documentation for a screen shot)
	
Download/check out and configure opensalt
	Check out the opensalt github directory, or download it. I put it in my Users/username/Sites/ directory on my Mac.
	From the opensalt directory, run:
		./local-dev/initial_dev_install.sh
	This script will take up to a few minutes; it includes the following steps, which you could alternatively try to run manually:
	
		2. Create env file
		  ```
		  cp docker/.env.dist to docker/.env
		  ```

		3. Install components with composer
		  ```
		  ./bin/composer install
		  ```
		  *When asked, leave everything as their defaults except for the secret keys*

		4. Run Gulp
		  ```
		  ./bin/gulp
		  ```

		5. Run database migrations
		  ```
		  ./bin/console-docker doctrine:migrations:migrate --no-interaction
		  ```

		6. Add a port to the nginx config in `docker/docker-compose.yml` change "80" to something like "3000:80" if you want use port :3000
	
	Near the end of the script, you may get a prompt that says "Organization name for the new user:"; if you get this, type "Unknown" here.
	The script will also create a super-user admin with username "admin" and password "secret". To create another user, use the followwing command, where "rolename" can be "editor", "admin", or "super-user":
		./bin/console salt:user:add [username] [--password="secret"] [--role="rolename"]
	You should now be able to connect to, e.g., http://127.0.0.1:3000. To run with Symfony debugging turned on, go to http://127.0.0.1:3000/app_dev.php/
	The install script will start the application running; to stop it run `docker-compose down -v` from inside the `opensalt/docker/` directory, and to restart it later, cd into `opensalt/docker/` again, and run `docker-compose up -d`
	To import the 
	`../bin/console import:asn D10003FC`
	
For Macs, download and configure d4m-nfs
	With the standard Docker installation outlined above, the SALT application runs very slowly on Macs; running d4m-nfs speeds page loads up from an almost unbearable 6 seconds or more to a bearable (albeit still somewhat sluggish) less than 1 second.
	Note that you should install and configure d4m-nfs *after* you have run the initial_dev_install.sh script.
	First exit out of the SALT app: `docker-compose down -v`
	Download d4m-nfs (I put it in my Sites/ directory, but I believe it can go anywhere)
	Copy d4m-nfs-mounts.txt from d4m-nfs-master/examples/ into d4m-nfs-master/etc
	Edit d4m-nfs-mounts.txt so that the bottom 3 lines are:
		/Users:/Users:0:0
		/Volumes:/Volumes
		/private:/private
	Open Docker, then go to Preferences > File Sharing and remove the three directories "/Users", "/Volumes", and "/private" (see the d4m-nfs documentation for a screen shot). Then quit Docker.
	Run the d4m-nfs.sh shell script. It will probably ask you for a sudo password, then it should perform its magic, which includes restarting Docker.
	You can now restart the SALT app: `docker-compose up -d`.
	When you later restart your machine, you'll have to launch Docker via d4m-nfs before launching the SALT app.  So if you installed d4m-nfs-master in your Sites directory like I did, you can go to the terminal, cd into the opensalt/docker/ directory, and run:
	```
	../../d4m-nfs-master/d4m-nfs.sh
	docker-compose up -d
	```

Notes
	The app compiles site-wide css into web/css/main.css, and site-wide js into web/js/site.js.  In theory, if you edit one of the component css or js files, main.css and/or site.js should be automatically re-compiled, and Symfony should use the new version.  If this doesn't work, you may need to clear Symfony's cache, and/or clear the "app/Resources/assets/rev-manifest.json" file, and/or re-run gulp. From within the opensalt/ directory, these are the three commands:
		```
		rm -rf var/cache/{dev,prod}
		sudo rm app/Resources/assets/rev-manifest.json
		bin/gulp
		```
