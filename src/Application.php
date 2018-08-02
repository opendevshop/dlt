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
  static function getConfigFile() {
    return  getenv(self::CONFIG_PREFIX . '_CONFIG_FILE') ?: self::getConfigPath() . '/dlt.yml';;
  }

  /**
   * Return the path to the config directory.
   * @return string
   */
  static function getConfigPath() {
    return getenv(self::CONFIG_PREFIX . '_CONFIG_PATH') ?: getenv('HOME') . '/.dlt/';
  }
}
