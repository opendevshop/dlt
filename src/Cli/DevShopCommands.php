<?php

namespace Dlt\Cli;

use Dlt\Application;

class DevShopCommands extends \Robo\Tasks {

    protected $dir;

    /**
     * @var \Robo\Config
     */
    protected $config;

    function __construct() {
        $this->dir = Application::getConfigPath();
    }

    /**
     * Launch a devshop using docker-compose
     *
     * @command up
     */
    public function up() {


        // Run the welcome committee.
        if (!file_exists($this->dir)) {
            if ($this->confirm("Hi there. You don't have the DLT config folder. Should I create it? ($this->dir) ")) {
                $this->taskFilesystemStack()
                    ->mkdir($this->dir)
                    ->run()
                ;
            }
            else {
                throw new \Exception('You must have a DLT config folder to continue.');
            }
        }

        $this->config = $this->getContainer()->get('config');
        $this->projects_path = $this->config->get('projects_path', getenv('HOME') . '/DevShopProjects');

        // Run the welcome committee.
        if (!file_exists($this->projects_path)) {
            if ($this->confirm("Hi there. You don't have a Projects folder. Should I create it? ($this->dir) ")) {
                $this->taskFilesystemStack()
                    ->mkdir($this->projects_path)
                    ->run()
                ;
            }
            else {
                throw new \Exception('You must have a Projects folder to continue.');
            }
        }

        $this->io()->text("Using projects path {$this->projects_path}...");

        $user_uid = trim(shell_exec('id -u'));
        $user_gid = trim(shell_exec('id -g'));
        $this->io()->text("Detected your UID as $user_uid and your GID $user_gid.");
        $this->io()->block('DLT will now attempt to alter the devshop/server container user to match your UID and GID...');

        // Write the docker-compose.yml file.
        $yml = <<<YML
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!

# This file is written by DevShop Local Tools every time
# the up command is run.

# If you wish to alter the docker stack, edit the file 
# docker-compose.override.yml

version: '2'

volumes:
  aegir:
  mysql:

services:
  devshop:
    image: devshop/server:local
    build:
      context: .
      dockerfile: Dockerfile.local
      args:
        PROVISION_USER_UID: $user_uid
        PROVISION_WEB_UID: $user_gid
    ports:
      - "2222:22"
      - 80:80
      - 443:443
    hostname: devshop.local.computer
    environment:
      AEGIR_CLIENT_EMAIL: jon@thinkdrop.net
    volumes:
      - aegir:/var/aegir
      - mysql:/var/lib/mysql
      - $this->projects_path:/var/aegir/projects
      
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!

YML;
        file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'docker-compose.yml', $yml);

        $dockerfile = <<<DOCKERFILE
        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!        
# DO NOT EDIT!

# This file is written by DevShop Local Tools every time
# the up command is run.

# If you wish to alter the docker stack, edit the file 
# docker-compose.override.yml

FROM devshop/server:latest
USER root
ARG PROVISION_USER_UID=12345
ENV PROVISION_USER_UID \${PROVISION_USER_UID:-$user_uid}
ARG PROVISION_WEB_UID=54321
ENV PROVISION_WEB_UID \${PROVISION_WEB_UID:-$user_gid}
ENV PROVISION_USER_NAME aegir
RUN echo "𝙋𝙍𝙊𝙑𝙄𝙎𝙄𝙊𝙉 Dockerfile.user ║ Running /usr/local/bin/set-user-ids \$PROVISION_USER_NAME \$PROVISION_USER_UID \$PROVISION_WEB_UID"
RUN /usr/local/bin/set-user-ids \$PROVISION_USER_NAME \$PROVISION_USER_UID \$PROVISION_WEB_UID
USER \$PROVISION_USER_NAME

DOCKERFILE;
        file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'Dockerfile.local', $dockerfile);

        $yml_override = <<<YML

# docker-compose.override.yml
# Use this file to add more services or customize the dynamic one.

version: '2'

services:
  node:
    image: node:8

YML;

        # Write the docker-compose.override.yml file, just once.
        if (!file_exists($this->dir . DIRECTORY_SEPARATOR . 'docker-compose.override.yml')) {
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'docker-compose.override.yml', $yml_override);
        }

        /**
         * @TODO:
         * - Detect occupied ports and offer to attempt to take them.
         * - Detect user's UID and build a local devshop/server to match.
         * - Ask where they would like to store Projects code.
         * - Write to the dlt.yml file so we don't have to ask what directory every time!
         */

        $this->dockerComposeUp();
    }

    /**
     * Run docker-compose up -d; docker-compose logs -f
     *
     * @command docker-compose:up
     */
    public function dockerComposeUp() {
        $this->_exec('docker-compose build --no-cache && docker-compose up -d && docker-compose logs -f');
    }

    /**
     * Override _exec to always cd into the devshop directory.
     *
     * @param \Robo\Contract\CommandInterface|string $cmd
     *
     * @return \Robo\Result
     */
    public function _exec($cmd) {
        $cmd = "cd $this->dir; $cmd";
        return parent::_exec($cmd);
    }

    /**
     * Enter a bash shell in the devmaster container.
     */
    public function shell() {
        $process = new \Symfony\Component\Process\Process("docker-compose exec devshop bash");
        $process->setTty(TRUE);
        $process->run();
    }

    /**
     * Run docker-compose stop
     */
    public function down() {
        $this->_exec('docker-compose stop');
    }

    /**
     * Run docker-compose stop
     */
    public function destroy() {
        $this->_exec('docker-compose kill; docker-compose rm -fv');
    }


}
