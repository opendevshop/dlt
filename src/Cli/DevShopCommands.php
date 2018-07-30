<?php

namespace Dlt\Cli;

class DevShopCommands extends \Robo\Tasks
{

  protected $dir;

  /**
   * Launch a devshop using docker-compose
   *
   * @command up
   */
  public function up(
    $dir = NULL
  )
  {

    // Ask what directory to install the docker-compose.yml file in.
    if (empty($dir)) {
      $dir = $this->io()->ask('What directory would you like to setup DevShop in?', getenv('HOME') . '/DevShop');
    }

    $this->dir = $dir;

    $this->io()->text("Setting up DevShop in $dir...");

    // Create the directory if it doesn't exist.
    $this->taskFilesystemStack()
      ->mkdir($dir)
      ->run();

    // Write the docker-compose.yml file.
    $yml = <<<YML
version: '2'

volumes:
  aegir:
  mysql:

services:
  devshop:
    image: devshop/server
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
YML;
      file_put_contents($dir . DIRECTORY_SEPARATOR . 'docker-compose.yml', $yml);

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
    $this->_exec('docker-compose up -d; docker-compose logs -f');
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


}
