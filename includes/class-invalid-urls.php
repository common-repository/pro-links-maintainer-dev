<?php
namespace Pro_Links_Maintainer;

final class Pro_Links_Maintainer_Invalid_Urls {

  public $repo;

  public function __construct() {
    require_once PRO_LINKS_MAINTAINER_INCLUDES . '/schemas/class-invalid-url.php';
    $this->repo = new Schemas\Pro_Links_Maintainer_Invalid_Url();
  }

  public function deleteRecord($id) {
    return $this->repo->deleteRecord($id);
  }

  public function deleteAllRecords() {
    return $this->repo->deleteAllRecords();
  }

  public function get_invalid_urls($offset, $limit = 100) {
    $invalid_urls = $this->repo->get_invalid_urls($offset, $limit);
    $json_result = array('results' => array());

    foreach ($invalid_urls['results'] as $url) {
      if ( pro_links_maintainer_fs()->is_not_paying() ) {
        $url->entity_edit_url = '';
      }
      array_push($json_result['results'], $url);
    }

    return $json_result;
  }

}
