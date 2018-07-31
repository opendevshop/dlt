<?php

namespace Dlt\Cli;

use Dlt\Application;

class DevShopCommands extends \Robo\Tasks
{

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
  public function up()
  {
    $this->config = $this->getContainer()->get('config');
    $projects_path = $this->config->get('projects_path', getenv('HOME') . '/DevShopProjects');

    $this->io()->text("Setting up DevShop in {$this->dir}...");
    $this->io()->text("Using projects path {$projects_path}...");

    // Create the directory if it doesn't exist.
    $this->taskFilesystemStack()
      ->mkdir($this->dir)
      ->mkdir($projects_path)
      ->run();

    $user_uid = trim(shell_exec('id -u'));
    $user_gid = trim(shell_exec('id -g'));

    // Write the docker-compose.yml file.
    $yml = <<<YML
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
        PROVISION_USER_NAME: provision
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
      - $projects_path:/var/aegir/projects
YML;
      file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'docker-compose.yml', $yml);

      $dockerfile = <<<DOCKERFILE
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
   * Enter a bash shell in the devmaster container.
   */
  public function shell() {
    $process = new \Symfony\Component\Process\Process("docker-compose exec devshop bash");
    $process->setTty(TRUE);
    $process->run();
  }


  /**
   * Override _exec to always cd into the devshop directory.
   * @param \Robo\Contract\CommandInterface|string $cmd
   *
   * @return \Robo\Result
   */
  public function _exec($cmd) {
    $cmd = "cd $this->dir; $cmd";
    return parent::_exec($cmd);
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
