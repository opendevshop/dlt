<?php

namespace Dlt\Cli;

class DevShopCommands extends \Robo\Tasks
{
    /**
     * Launch a devshop using docker-compose
     *
     * @command up
     */
    public function up(
      $dir = ''
    )
    {

      if (empty($dir)) {
        $this->io()->ask('What directory would you like to setup DevShop in?', getcwd());
      }

      $this->io()->text("Setting up docker-compose.yml in $dir");


//        $model = new \Dlt\Example($a);
//        $result = $model->multiply($b);
//
//        $this->io()->text("$a times $b is $result");
    }
}
