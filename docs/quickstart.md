Concerto Quickstart Guide
=========================

We don't endorse this method for use in 'production' environment (i.e. testing real people) as it is. Hosting a web 
application on the public server in efficient and secure manner requires specialist knowledge as there are many moving 
parts involved and considerations to make.

There is [Concerto Deployment Guide](deployment-guide.md) intended for IT professionals describing different 
deployment options for real-world scenarios.

Installation
------------

Please follow steps below to setup Concerto Platform:

1. Install **[docker](https://docs.docker.com/install/)** (v1.13 or newer)
1. Install **[docker-compose](https://docs.docker.com/compose/install/#install-compose)** (v1.21 or newer)
1. Download [docker-compose.yml](https://raw.githubusercontent.com/campsych/concerto-platform/master/deploy/docker-compose/docker-compose.yml) file and save to a path of your choice
1. Start it:

       docker-compose up -d

Running Administration Panel
----------------------------

When installation is successfully finished. You should now be able to access Concerto Platform administration panel by going to: [http://localhost/admin](http://localhost/admin)

Default login and password is:

Login: **admin**

Password: **admin**

**Please be aware you should immediately change your login and password when logging in for the first time!**

Troubleshooting
---------------

### 0.0.0.0:80: bind: address already in use

Port needed for Concerto's web server (80) is already taken by other thing running on your system. You could change ports Concerto is using in `docker-compose.yml` file. Alternatively find out what is using the port and stop it.

Example - changing Concerto web server port mapping to port 8080:

    ports:
      - 8080:80

After this change it will be available on [http://localhost:8080/](http://localhost:8080/)

### Not working on Raspberry PI

ARM architecture is not supported. Mostly by MySQL database. Could work with a different database that works on ARM, i.e. Postgres. You'd need to rebuild the container yourself using our Dockerfile as a base.

### Version in `./docker-compose.yml` is unsupported

You're not really using the recent version of Docker in your system. Please follow link above to Docker installation guide in order to get a recent one.

### The paths X are not shared from OS X and are not known to Docker. You can configure shared paths from Docker -> Preferences... -> File Sharing

On Mac OS, by default, Docker is not permitting mounts to anything outside of user's home directory. You need to do 
what it says to explicitly allow the directory or consider pulling Concerto files somewhere within your user's home 
(e.g. `~/concerto`).
