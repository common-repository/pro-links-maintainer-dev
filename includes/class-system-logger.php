<?php
namespace Pro_Links_Maintainer;

final class Pro_Links_Maintainer_System_Logger {

  private $log_file;

  public function __construct() {
    $this->log_file = pathinfo(ini_get('error_log'),PATHINFO_DIRNAME).'/pro_links_maintainer_system.log';
  }

  function current_time() {
    return date("[Y-m-d H:i:s]");
  }

  public function get_logs() {
    return file_get_contents($this->log_file);
  }

  public function clear_log_file() {
    file_put_contents($this->log_file, "");
  }

  public function debug($line) {
    error_log('DEBUG - '.$this->current_time().' - '.$line."\n", 3, $this->log_file);
  }

  public function info($line) {
    error_log('INFO - '.$this->current_time().' - '.$line."\n", 3, $this->log_file);
  }

  public function error($line) {
    error_log('ERROR - '.$this->current_time().' - '.$line."\n", 3, $this->log_file);
  }

}
