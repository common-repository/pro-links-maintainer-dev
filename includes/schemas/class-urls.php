<?php
namespace Pro_Links_Maintainer\Schemas;

class Pro_Links_Maintainer_Entity_Url {

  private $table_name = '';

  function __construct() {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'bs_urls';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  }

  public function createSchema() {
    global $wpdb;
  	$charset_collate = $wpdb->get_charset_collate();
    $name = $this->table_name;
  	$sql = "CREATE TABLE $name (
  		id mediumint(9) NOT NULL AUTO_INCREMENT,
  		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  		url tinytext NOT NULL,
      entity_id mediumint(9) NOT NULL,
      entity_type tinytext NOT NULL,
      last_scan_date datetime NULL,
      url_hash tinytext NOT NULL,
  		PRIMARY KEY (id),
      UNIQUE KEY `key` (`entity_id`,`url_hash`(32))
  	) $charset_collate;";

  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  	dbDelta( $sql );
  }

  public function deleteTable() {
    $name = $this->table_name;
    dbDelta( "DROP TABLE IF EXISTS $name" );
  }

  public function insertRecord($entity_url) {
    global $wpdb;
    $name = $this->table_name;
    $current_time = current_time( 'mysql' );

    $url = $entity_url['url'];
    $entity_id = $entity_url['entity_id'];
    $entity_type = $entity_url['entity_type'];
    $url_hash = hash('crc32', $url);

    $sql = "INSERT INTO $name (time,url,entity_id,entity_type,url_hash) VALUES ('$current_time','$url','$entity_id','$entity_type', '$url_hash') ON DUPLICATE KEY UPDATE time = '$current_time'";
    $wpdb->query($sql);
  }

  public function deletePostRecord($post_id) {
    global $wpdb;
    $name = $this->table_name;
    $query = "DELETE FROM ".$name." WHERE entity_id = ".$post_id." AND entity_type = 'POST'";
    $delete_result = $wpdb->query($query);
    return $delete_result;
  }

  public function deleteAllRecords() {
    global $wpdb;
    $name = $this->table_name;
    $query = "DELETE FROM ".$name;
    $delete_result = $wpdb->query($query);
    return $delete_result;
  }

  function get_where_query_based_on_filters($last_scan_date_difference_hours, $filter_out, $filter) {
    $where_query = '';

    //$filter_out = 'openload, youtube, dailymotion';
    $filters_outs = explode(", ", $filter_out);
    $query_filter_outs = "url NOT LIKE '%".implode("%' OR url NOT LIKE '%",$filters_outs)."%'";

    $filters = explode(", ", $filter);
    $query_filter = "url LIKE '%".implode("%' OR url LIKE '%",$filters)."%'";

    if ($last_scan_date_difference_hours > 0) {
      $where_query = 'WHERE ((last_scan_date + INTERVAL '.$last_scan_date_difference_hours.' HOUR < NOW()) OR last_scan_date is null) ';
    }

    if ($last_scan_date_difference_hours > 0 && strlen($filter_out) > 0) {
      $where_query .= 'AND '.$query_filter_outs;
    } elseif (strlen($filter_out) > 0) {
      $where_query = 'WHERE '.$query_filter_outs;
    }

    if (strlen($filter) > 0 && ($last_scan_date_difference_hours > 0 || strlen($filter_out) > 0)) {
      $where_query .= ' AND '.$query_filter;
    } elseif (strlen($filter) > 0) {
      $where_query .= 'WHERE '.$query_filter;
    }

    return $where_query;
  }

  public function get_urls($offset, $limit, $last_scan_date_difference_hours=null, $filter_out='', $filter='') {
    global $wpdb;
    $name = $this->table_name;

    $where_query = $this->get_where_query_based_on_filters($last_scan_date_difference_hours, $filter_out, $filter);
    $query = "SELECT id, time, url, entity_id, entity_type, last_scan_date, url_hash FROM ".$name." ".$where_query." ORDER BY url DESC LIMIT ".$limit." OFFSET ".$offset;
    $results = $wpdb->get_results( $query, OBJECT_K );
    $results_arr = array();
    foreach($results as $res) {
      $arr_obj = (array)$res;
      array_push($results_arr, $arr_obj);
    }
    return $results_arr;
  }

  public function set_last_scan_date($hashes) {
    if (sizeof($hashes) > 0) {
      global $wpdb;

      $hash_str = "";
      foreach ($hashes as $hash) {
        $hash_str .= '\''.$hash.'\', ';
      }
      $hash_str = substr($hash_str, 0, -2);

      $name = $this->table_name;
      $time = current_time( 'mysql', $gmt = 0 );
      $sql = "UPDATE $name SET last_scan_date = '$time' WHERE url_hash IN ($hash_str)";
      $wpdb->query($sql);
    }
  }

  public function get_urls_count($last_scan_date_difference_hours, $filter_out, $filter) {
    global $wpdb;
    $name = $this->table_name;
    $where_query = $this->get_where_query_based_on_filters($last_scan_date_difference_hours, $filter_out, $filter);

    $query = "SELECT count(id) FROM ".$name." ".$where_query;
    $count = $wpdb->get_var( $query );
    return $count;
  }

  public function get_urls_for_single_post($post_id) {
    global $wpdb;
    $name = $this->table_name;
    $where_query = " WHERE entity_id = ".$post_id." AND entity_type = 'POST'";
    $query = "SELECT id, time, url, entity_id, entity_type, last_scan_date, url_hash FROM ".$name." ".$where_query;
    $results = $wpdb->get_results( $query, OBJECT_K );
    $results_arr = array();
    foreach($results as $res) {
      $arr_obj = (array)$res;
      array_push($results_arr, $arr_obj);
    }
    return $results_arr;
  }

}

?>
