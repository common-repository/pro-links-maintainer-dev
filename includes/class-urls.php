<?php
namespace Pro_Links_Maintainer;

final class Pro_Links_Maintainer_Urls {

	private $scanner;
	private $entity_url_schema;
	private $system_logger;

	public function __construct(
			Pro_Links_Maintainer_Scanner $scanner,
			Schemas\Pro_Links_Maintainer_Entity_Url $entity_url_schema,
			Pro_Links_Maintainer_System_Logger $system_logger
		) {
		$this->scanner = $scanner;
		$this->entity_url_schema = $entity_url_schema;
		$this->system_logger = $system_logger;
	}

	public function get_parsing_status() {
		$current_status = get_option(PARSER_STATUS);
		if ( !$current_status ) {
			$current_status = array('parsed' => 0, 'finished' => false);
		}
		return $current_status;
	}

  public function fill_urls() {
		$current_status = get_option(PARSER_STATUS);
		$empty_status = array('parsed' => 0, 'finished' => false);
		if ( $current_status ) {
			update_option(PARSER_STATUS, $empty_status);
		} else {
			add_option(PARSER_STATUS, $empty_status);
		}

    $urlsTable = $this->entity_url_schema;
    $urlsTable->deleteAllRecords();
    $has_more_posts = true;
    $limit = 100;
    $offset = 0;
    while($has_more_posts) {
       $scanned_urls = $this->scanner->parse_urls_from_posts($offset, $limit);
       foreach($scanned_urls['urls'] as $url) {
         $entity_url = array(
           'url' => $url['url'],
           'entity_id' => $url['post_id'],
           'entity_type' => 'POST'
         );
				 $this->system_logger->debug('Parsed and saving url: '.$url['url'].' from post id: '.$url['post_id']);
         $urlsTable->insertRecord($entity_url);
       }
       $offset += $limit;
       $has_more_posts = $scanned_urls['has_more_posts'];
			 update_option(PARSER_STATUS, array('parsed' => $offset, 'finished' => false));
    }
    delete_option($this->scanner->bg_status_option_name());
		update_option(PARSER_STATUS, array('parsed' => $offset, 'finished' => true));
  }

	public function get_urls_for_single_post($post_id) {
		return $this->entity_url_schema->get_urls_for_single_post($post_id);
	}

	public function get_urls($offset, $limit, $last_scan_date_difference_hours) {
		return $this->entity_url_schema->get_urls($offset, $limit, $last_scan_date_difference_hours);
	}

}
