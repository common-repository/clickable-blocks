<?php
/**
 * Plugin Name: Clickable Blocks
 * Description: Transform blocks into clickable links effortlessly with 'Clickable Blocks' – enhance user engagement instantly!
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: appiapp
 * Text Domain: appiapp_clickable_blocks
 * Domain Path: /languages
 * License: GPLv2 or later
 * 
 * @package           appiapp_clickable_blocks
 */

defined( 'ABSPATH' ) || exit;

function appicb_enqueue_block_editor_assets() {
	$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	$plugin_url  = untrailingslashit( plugin_dir_url( __FILE__ ) );
	$asset_file  = include untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/build/index.asset.php';

	wp_enqueue_script(
		'appiapp-clickable-blocks-editor-scripts',
		$plugin_url . '/build/index.js',
		$asset_file['dependencies'],
		$asset_file['version']
	);

    wp_set_script_translations(
        'appiapp-clickable-blocks-editor-scripts',
        'appiapp_clickable_blocks',
        $plugin_path . '/languages'
    );
}
add_action( 'enqueue_block_editor_assets', 'appicb_enqueue_block_editor_assets' );


function appicb_render_block_group( $block_content, $block ) {
    $hasUrl = isset( $block['attrs']['blockLink'] ) && isset( $block['attrs']['blockLink']['url'] );
    
    if ( ! $hasUrl ) {
		return $block_content;
	}

    $url = $block['attrs']['blockLink']['url'];
    $usejs = isset($block['attrs']['blockLink']['usejs']) && $block['attrs']['blockLink']['usejs'] == true ? true : false;
    $opensInNewTab = isset($block['attrs']['blockLink']['opensInNewTab']) && $block['attrs']['blockLink']['opensInNewTab'] == true ? true : false;
    $nofollow = isset($block['attrs']['blockLink']['nofollow']) &&  $block['attrs']['blockLink']['nofollow'] == true;

    if($usejs){
        $p = new WP_HTML_Tag_Processor( $block_content );
        if ( $p->next_tag() ) {
            $p->set_attribute( 'data-clickable-blocks', "true" );

            if($opensInNewTab){
                $p->set_attribute( 'onclick', 'window.open("'.$url.'", "_blank")' );
            }
            else{
                $p->set_attribute( 'onclick', 'window.location.href = "'.$url.'"' );
            }
        }

        $block_content = $p->get_updated_html();
        return $block_content;
        
    }else{
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$block_content);
        $body = $doc->documentElement;

        if($body->firstChild->hasChildNodes()){
            $current_container = $body->firstChild->childNodes->item(0);
            $cloned_container = $current_container->cloneNode(false);

            $tag_a = $doc->createElement('a');
            $tag_a->setAttribute('href',  $url);
            if($opensInNewTab){
                $tag_a->setAttribute('target',  '_blank');
            }

            if($nofollow){
                $tag_a->setAttribute('rel',  'nofollow');
            }
            
            $tag_a->setAttribute('class',  'link-anithing');
            $cloned_container->appendChild($tag_a);

            if($current_container->hasChildNodes()){
                while ($current_container->childNodes->length > 0) {
                    $tag_a->appendChild($current_container->childNodes->item(0));
                }
            }
            $doc->appendChild($cloned_container);
            $current_container->parentNode->removeChild($current_container);
            
            return $doc->saveHTML($cloned_container);
            //return $doc->saveHTML($cloned_container, LIBXML_NOEMPTYTAG | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD, 'UTF-8');

        }else{
            return $block_content;
        }
    }

    
}
add_filter( 'render_block_core/group', 'appicb_render_block_group', 10, 2 );
add_filter( 'render_block_core/columns', 'appicb_render_block_group', 10, 2 );
add_filter( 'render_block_core/column', 'appicb_render_block_group', 10, 2 );