<?php
/**
 *
 * @package Reco, plugin for WordPress
 * @since     0.1
 */

if ( ! defined( 'DB_NAME' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}


if ( isset( $recos ) ) {
	
$after_content = <<<HTML1
	<div class="recos-generated">
		
		<h3 class="recos-title">Recos:</h3>
		
		<ul class="recos-list recos-rated-id-{$post_id}">
HTML1;

	foreach ( (array) $recos as $reco ) {
		
		$after_content .= '<li class="recos-item">';
		$after_content .= '<a class="recos-link post-id-' . $reco->ID . '" ';
		$after_content .= 'href="' . get_permalink( $reco->ID ) . '" alt="' . $reco->post_title . '">';
		$after_content .= ( $reco->post_title ) ? $reco->post_title : '(empty post title)';
		$after_content .= '</a></li>';
		
	}

$after_content .= <<<HTML3
		</ul>
		
	</div>
HTML3;

}

