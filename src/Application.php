<?php

namespace Dlt;

class Application
{

  const NAME = "DevShop Local Tools";
  const REPO = "opendevshop/dlt";
  const CONFIG_PREFIX = "DLT";

  /**
   * Return the path to the config file.
   * @return string
   */
  static function getConfigFilePath() {
    return getenv(self::CONFIG_PREFIX . '_CONFIG') ?: getenv('HOME') . '/.dlt/dlt.yml';;
  }

}
