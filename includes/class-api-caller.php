<?php
namespace Pro_Links_Maintainer;

class Pro_Links_Maintainer_Url_Checker {

  public $bg_settings;
  private $system_logger;

  public function __construct(Pro_Links_Maintainer_Settings $settings, Pro_Links_Maintainer_System_Logger $system_logger) {
    $this->bg_settings = $settings->getBGSettings();
    $this->system_logger = $system_logger;
  }

  function get_timeout() {
    $timeout = $bg_settings ? $bg_settings['connection_timeout'] : 30;
    return $timeout ? $timeout : 30;
  }

  function get_redirects() {
    $max_redirects = $bg_settings ? $bg_settings['max_redirects'] : 10;
    return $max_redirects ? $max_redirects : 10;
  }

  function parse_response($response) {
    if ( is_wp_error( $response ) ) {
       $error_message = $response->get_error_message();
       error_log("Something went wrong: ".print_r($error_message, true));
       return $error_message;
    } else {
       error_log("Call response body: ".print_r($response['body'], true));
       return $response['body'];
    }
  }

  function send_mailgun_mail($to,$subject,$msg, $api_key, $domain) {
    $this->system_logger->debug('$to: '.$to);
    $this->system_logger->debug('$subject: '.$subject);
    $this->system_logger->debug('$msg: '.$msg);
    $this->system_logger->debug('$api_key: '.$api_key);
    $this->system_logger->debug('$domain: '.$domain);

		$url = 'https://api:' . $api_key . '@api.mailgun.net/v3/' . $domain . '/messages';
		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'headers' => array(),
			'body' => array(
                'from' => 'WP Mailing System <wp_system@'.$domain.'>',
                'to' => $to,
                'subject' => $subject,
                'text' => $msg
              ),
			'cookies' => array()
		  )
		);

    $this->parse_response($response);
  }

  function urlencodefix($url){
    $regex_str = '|[^a-z0-9\+\-\/\\#:.,;=?!&%@()$\|*~_]|i';
    return preg_replace_callback($regex_str,create_function('$str','return rawurlencode($str[0]);'), $url);
  }

  public function getWithResponse($url) {
    $url = $this->urlencodefix($url);
    $args = array(
    	'timeout' => $this->get_timeout(),
    	'redirection' => $this->get_redirects()
    );
    $this->system_logger->debug('Get response from url: '.$url);
    $response = wp_remote_get( $url, $args);
    return $this->parse_response($response);
  }

  public function checkIfError($url) {
    $url = $this->urlencodefix($url);
    $args = array(
    	'timeout' => $this->get_timeout(),
    	'redirection' => $this->get_redirects()
    );
    
    $response = wp_remote_get( $url, $args);
    $response_code = wp_remote_retrieve_response_code( $response );

    $this->system_logger->debug('Got response code from url: '.$url.' '.$response_code);
    $request_ok = $response_code == 200 || $response_code == 201 || $response_code == 204;

    if ($request_ok) {
      return "";
    } else if ( is_wp_error( $response )) {
       $error_message = $response->get_error_message();
       $this->system_logger->error('Error while checking url: '.$url.' '.print_r($error_message, true));
       return $error_message;
    } else {
      return "Error code: ".$response_code;
    }
  }

}
