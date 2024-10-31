<?php

namespace Pro_Links_Maintainer;

class Pro_Links_Maintainer_Scanner_YouTube
{
    private  $apiCaller ;
    private  $system_logger ;
    public function __construct( Pro_Links_Maintainer_Url_Checker $apiCaller, Pro_Links_Maintainer_System_Logger $system_logger )
    {
        $this->apiCaller = $apiCaller;
        $this->system_logger = $system_logger;
    }
    
    function filter_yt_ids( $post_id_with_yt_id )
    {
        $id = $post_id_with_yt_id['yt_id'];
        return $id;
    }
    
    public function scan_invalid_yt_urls_oembed( $posts_ids_with_yt_ids )
    {
        $invalid_urls = array();
        foreach ( $posts_ids_with_yt_ids as $post_id_with_yt_id ) {
            $yt_url = $post_id_with_yt_id['url'];
            preg_match( '%(?:youtube(?:-nocookie)?\\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\\.be/)([^"&?/ ]{11})%i', $yt_url, $match );
            $id = $match[1];
            $headers = get_headers( 'http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=' . $id );
            
            if ( !strpos( $headers[0], '200' ) ) {
                $post_id_with_yt_id['error'] = $headers[0];
                array_push( $invalid_urls, $post_id_with_yt_id );
            }
        
        }
        return $invalid_urls;
    }
    
    function scan_invalid_yt_using_google_api(
        $posts_ids_with_yt_ids,
        $ytKey,
        $url,
        $baseFilter = 'id'
    )
    {
        return $this->scan_invalid_yt_urls_oembed( $posts_ids_with_yt_ids );
    }
    
    public function scan_invalid_yt_urls_using_google_api( $posts_ids_with_yt_ids, $ytKey )
    {
        return $this->scan_invalid_yt_using_google_api( $posts_ids_with_yt_ids, $ytKey, 'https://content.googleapis.com/youtube/v3/videos' );
    }
    
    public function scan_invalid_yt_channels_using_google_api( $posts_ids_with_yt_ids, $ytKey )
    {
        return $this->scan_invalid_yt_using_google_api( $posts_ids_with_yt_ids, $ytKey, 'https://content.googleapis.com/youtube/v3/channels' );
    }
    
    public function scan_invalid_yt_playlist_using_google_api( $posts_ids_with_yt_ids, $ytKey )
    {
        return $this->scan_invalid_yt_using_google_api( $posts_ids_with_yt_ids, $ytKey, 'https://content.googleapis.com/youtube/v3/playlists' );
    }
    
    public function scan_invalid_yt_channels_by_user_using_google_api( $posts_ids_with_yt_ids, $ytKey )
    {
        $api_caller = $this->apiCaller;
        $invalid_urls = array();
        $ids = array_map( array( $this, 'filter_yt_ids' ), $posts_ids_with_yt_ids );
        $ids_str = join( ",", $ids );
        
        if ( $ids_str ) {
            $valid_ids = array();
            //forUsername
            foreach ( $ids as $id_str ) {
                $yt_check_url = 'https://content.googleapis.com/youtube/v3/channels?part=id&key=' . $ytKey . '&forUsername=' . $id_str;
                $get_data = $api_caller->getWithResponse( $yt_check_url );
                $response = json_decode( $get_data, true );
                $items = $response['items'];
                if ( sizeof( $items ) === 1 ) {
                    array_push( $valid_ids, $id_str );
                }
            }
            $invalid_urls = array_diff( $ids, $valid_ids );
            $this->system_logger->debug( 'Scan YT Channel API invalid urls: ' . print_r( $invalid_urls, true ) );
            $scan_results = array_filter( $posts_ids_with_yt_ids, function ( $post_id_with_yt_id ) use( $invalid_urls ) {
                $yt_id = $post_id_with_yt_id['yt_id'];
                return in_array( $yt_id, $invalid_urls );
            } );
            return $scan_results;
        } else {
            return array();
        }
    
    }

}