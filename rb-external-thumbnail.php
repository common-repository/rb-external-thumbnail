<?php
/*
 * Plugin Name: RB External Thumbnail
 * Plugin URI: https://github.com/rodrigo-brito/rb-external-thumbnail
 * Description: External images for post thumbnail.
 * Author: Rodrigo Brito
 * Version: 1.3
 * Author URI: http://www.rodrigobrito.net/
 * Text Domain: rb-external-thumbnail
 * Domain Path: /languages/
 */

require_once plugin_dir_path( __FILE__ ).'inc/class-metabox.php';

register_deactivation_hook(__FILE__, 'rb_thumbnail_uninstall');

/**
 * Load textdomain
 */
function rb_external_thumbnail_load_textdomain() {
	load_plugin_textdomain( 'rb-external-thumbnail', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'rb_external_thumbnail_load_textdomain' );

function rb_external_thumbnail_init_metabox() {
	$thumbnail_metabox = new RB_Thumbnail_Metabox(
	    'thumbnail_external',
	    __( 'Thumbnail External (URL)', 'rb-external-thumbnail' ),
	    'post',
	    'side',
	    'low'
	);

	$thumbnail_metabox->set_fields(
	    array(
	        array(
	            'id'          => 'thumbnail_external',
	            'label'       => __( 'Image URL', 'rb-external-thumbnail' ),
	            'type'        => 'text',
	            'attributes'  => array(
	                'placeholder' => __( 'Ex: http://www.externalsite.com/image.jpg', 'rb-external-thumbnail' )
	            )
	        )
	    )
	);
}

add_action( 'init', 'rb_external_thumbnail_init_metabox' );


function rb_thumbnail_uninstall() {
    delete_post_meta_by_key( 'thumbnail_external' );
}

add_action( 'admin_enqueue_scripts', 'rb_external_enqueue_scripts' );

function rb_external_enqueue_scripts() {
    wp_enqueue_script(
        'rb_external_thumbnail',
        plugins_url( '/js/script.js' , __FILE__ ),
        array( 'jquery' )
    );

    wp_enqueue_style(
        'twentyfifteen-style',
        plugins_url( '/css/style.css' , __FILE__ )
    );
}

add_filter( 'post_thumbnail_html', 'external_post_thumbnail_html', 99, 5 );

function external_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    global $post;
    $thumbnail = get_post_meta( $post->ID ,'thumbnail_external', true );
    $alt = get_the_title( $post->ID ); // gets the post thumbnail title
    $class = isset($attr['class']) ?  $attr['class'] : 'attachment-post-thumbnail wp-post-image';
    if ( !empty( $thumbnail ) )
        $html = '<img src="' . esc_url( $thumbnail ) . '"  alt="' . $alt . '" class="' . $class . '" />';
    return $html;
}

add_filter('get_post_metadata', 'rb_has_post_thumbnail', true, 4);

function rb_has_post_thumbnail($metadata, $object_id, $meta_key, $single){
    if( isset($meta_key) && '_thumbnail_id' === $meta_key ){
        global $post;
        $post_id = ( null === $object_id ) ? $post->ID : $object_id;
        $url_thumbnail_external = get_post_meta( $post_id ,'thumbnail_external', true );
        if( !empty($url_thumbnail_external) ){
            return true;
        }
    }
    return $metadata;
}


add_filter( 'get_post_metadata', 'rb_external_inject_thumbnail_id', 10, 4 );

function rb_external_inject_thumbnail_id ( $value, $post_id, $meta_key, $single ) {
    if ( $meta_key === '_thumbnail_id' ) {
        $url_thumbnail_external = get_post_meta( $post_id ,'thumbnail_external', true );
        if( !empty($url_thumbnail_external) ){
            return "ext-" . $post_id;
        }
    }
    return $value;
}

add_filter( 'wp_get_attachment_image_src', 'rb_external_attachment_image_src', 10, 4 );

function rb_external_attachment_image_src( $image, $attachment_id, $size, $icon ){
    if(isset($attachment_id) && !empty($attachment_id) && substr($attachment_id, 0, 3) === "ext"){
        $id = substr($attachment_id, 4);
        $thumbnail = get_post_meta( $id ,'thumbnail_external', true );
        return Array ( $thumbnail, 0, 0, false );
    }
    return $image;
}