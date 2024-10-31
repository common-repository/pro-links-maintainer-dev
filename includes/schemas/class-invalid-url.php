<?php
namespace Pro_Links_Maintainer\Schemas;

class Pro_Links_Maintainer_Invalid_Url {

  private $table_name = '';

  function __construct() {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'bs_invalid_url';
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
      url_type tinytext NOT NULL,
      entity_id mediumint(9) NOT NULL,
      entity_url tinytext NOT NULL,
      entity_url_hash tinytext NOT NULL,
      entity_edit_url tinytext NOT NULL,
      error text,
  		PRIMARY KEY (id),
      UNIQUE KEY `key` (`entity_id`,`entity_url_hash`(8))
  	) $charset_collate;";

  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  	dbDelta( $sql );
  }

  public function deleteTable() {
    $name = $this->table_name;
    dbDelta( "DROP TABLE IF EXISTS $name" );
  }

  function get_edit_post_link( $id ) {
     if ( ! $post = get_post( $id ) ) {
       return;
     }
     if ( 'revision' === $post->post_type ) {
       $action = '';
     } else {
       $action = '&action=edit';
     }
     $post_type_object = get_post_type_object( $post->post_type );
     if ( ! $post_type_object ) {
       return;
     }
     if ( $post_type_object->_edit_link ) {
       $link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
     } else {
       $link = '';
     }
     return $link;
   }

  public function insertRecord($invalid_url) {
    global $wpdb;
    $name = $this->table_name;
    $current_time = current_time( 'mysql' );
    $url = $invalid_url['url'];
    $url_type = $invalid_url['url_type'];
    $entity_id = $invalid_url['entity_id'];
    $entity_url = get_post_permalink($entity_id);
    $entity_edit_url = $this->get_edit_post_link($entity_id);
    $error = esc_sql($invalid_url['error']);
    $entity_url_hash = hash('crc32', $url);
    $sql = "INSERT INTO $name (time,url,url_type,entity_id,entity_url,entity_url_hash,entity_edit_url,error) VALUES ('$current_time','$url','$url_type','$entity_id','$entity_url','$entity_url_hash','$entity_edit_url', '$error') ON DUPLICATE KEY UPDATE time = '$current_time'";
    $result = $wpdb->query($sql);
  }

  public function deleteRecord($id) {
    global $wpdb;
    $name = $this->table_name;
    $query = "DELETE FROM ".$name." WHERE entity_id = ".$id;
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

  public function get_invalid_urls($offset, $limit = 100) {
    global $wpdb;
    $name = $this->table_name;
    $query = "SELECT entity_id, time, url, url_type, entity_url, entity_edit_url, error FROM ".$name." ORDER BY id DESC LIMIT ".$limit." OFFSET ".$offset;
    $last_scan_result = $wpdb->get_results( $query, OBJECT_K );
    $json_result = array('results' => array());
    foreach ($last_scan_result as $res) {
      array_push($json_result['results'], $res);
    }
    return $json_result;

  }

}

?>
