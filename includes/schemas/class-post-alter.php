<?php
namespace Pro_Links_Maintainer\Schemas;

//RENAME TO UPGRADED OR SMTH
class Pro_Links_Maintainer_Wp_Post_Alter {

  private $table_name = '';

  function __construct() {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'posts';
  }

  public function addColumns() {
    global $wpdb;
    $name = $this->table_name;
  	$sql = "ALTER TABLE $name ADD COLUMN last_scan_date datetime";
  	$wpdb->query($sql);
  }

  public function dropColumns() {
    global $wpdb;
    $name = $this->table_name;
  	$sql = "ALTER TABLE $name DROP COLUMN last_scan_date";
    $wpdb->query($sql);
  }

  public function set_last_scan_date($post_id) {
    global $wpdb;
    $name = $this->table_name;
    $time = current_time( 'mysql', $gmt = 0 );
    $sql = "UPDATE $name SET last_scan_date = '$time' WHERE ID='$post_id'";
    $wpdb->query($sql);
  }

  public function set_last_scan_date_multi($ids) {
    if (sizeof($ids) > 0) {
      global $wpdb;
      $name = $this->table_name;
      $time = current_time( 'mysql', $gmt = 0 );

      $ids_str = "";
      $ids = array_unique($ids);
      foreach ($ids as $id) {
        $ids_str .= $id.', ';
      }
      $ids_str = substr($ids_str, 0, -2);

      $sql = "UPDATE $name SET last_scan_date = '$time' WHERE ID IN ($ids_str)";
      $wpdb->query($sql);
    }
  }

}

?>
