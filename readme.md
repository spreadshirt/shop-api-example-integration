# Example Client for the new Shop-API

This is an example integration of the new Shop-API which is currently in beta testing. This is not production ready code but is supposed to give an overview over the capabilities.

## Requirements

- [docker](https://docs.docker.com/) (17.04.0+) and [docker-compose](https://docs.docker.com/compose/)

## Install dependencies

Go to the root directory of this project in your CLI and execute `docker run --rm --interactive --tty --volume $PWD/src:/app composer:1.8 install`.

If you are running the command inside the Windows command line, please replace `$PWD` with `%cd%`.

## Start

Start the example application by executing `docker-compose up` from the root directory of this project in your CLI.

The application will be running at [http://localhost:8008](http://localhost:8008/). If necessary you can change the port in the docker-compose.yml.
