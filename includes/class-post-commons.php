<?php
namespace Pro_Links_Maintainer;

class Pro_Links_Maintainer_Post_Commons {

  public static function get_edit_post_link( $id ) {
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

}
