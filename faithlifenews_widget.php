<?php
/*
Plugin Name: Faithlife News Widget
Plugin URI: https://news.faithlife.com
Description: Add the latest breaking Christian news to your Wordpress site
Version: 1.0.1
Author: Michael Jordan
Author URI: http://michaeljordanmedia.com
Network: true

Copyright 2015  Faithlife Corporation  (email : michael.jordan@faithlife.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/


// Block direct requests
if ( !defined('ABSPATH') )
die('-1');


add_action( 'widgets_init', 'register_faithlifenews_widget');

function register_faithlifenews_widget() {
	register_widget( 'Faithlife_News_Widget' );
	faithlife_news_admin_init();
}

function faithlife_news_admin_styles() {
    wp_enqueue_style( 'FaithlifeNewsWidgetAdminStylesheet' );
}

function faithlife_news_frontend_styles() {
	wp_enqueue_style( 'FaithlifeNewsWidgetFrontendStylesheet' );
}

function faithlife_news_admin_init() {
   wp_register_style( 'FaithlifeNewsWidgetAdminStylesheet', plugins_url('/css/admin_style.css', __FILE__) );
   wp_register_style( 'FaithlifeNewsWidgetFrontendStylesheet', plugins_url('/css/style.css', __FILE__) );
}

function faithlife_news_addreftagger_script() {
	wp_enqueue_script( 'reftagger-light', plugins_url('/js/reftagger-light.js', __FILE__), array(), '1.0.0', true );
}
function faithlife_news_addreftagger_script_dark() {
	wp_enqueue_script( 'reftagger-dark', plugins_url('/js/reftagger-dark.js', __FILE__), array(), '1.0.0', true );
}


/**
* Adds Faithlife_News_Widget widget.
*/
class Faithlife_News_Widget extends WP_Widget {
	
	/**
	* Register widget with WordPress.
	*/
	function __construct() {
		parent::__construct(
			'Faithlife_News_Widget', // Base ID
			__('Faithlife News', 'text_domain'), // Name
			array( 'description' => __( 'Add the latest breaking Christian news to your Wordpress site', 'text_domain' ), )
		);
		
		$this->fln_config = require('config.php');
		
		$source_json = file_get_contents($this->fln_config['api']['sources']); 
		$source_data = json_decode($source_json);
		$this->fln_sources = $source_data->sources;
	}

	/**
	* Front-end of widget.
	*/
	function widget($args, $instance) {
		extract( $args );
		
		//$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		echo '<div id="faithlife_news_widget" class="widget-text wp_widget_plugin_box faithlife_news_widget faithlife_news_widget_'.$instance[ 'colorscheme' ].'">';
		

		echo '<a class="faithlife_news_logo" href="'. $this->fln_config['app']['url'] .'"></a>';
		
		//set args to query the API
		$article_args['api_key'] = $this->fln_config['api']['key'];
		$article_args['articlecount'] = (isset($instance['articlecount'])) ? $instance['articlecount'] : 5;
		$article_args['sources'] = [];
		foreach($instance['sources'] as $n) {
			if ($instance['sources'][$n->ID]->Checked == 1) {
				array_push($article_args['sources'], $n->ID);
			}
		}
		
		//prepare query
		$getdata = http_build_query($article_args);
		$opts = array('http' =>
			array(
				'method'  => 'GET',
				'content' => $getdata
			)
		);
		$context  = stream_context_create($opts);
		
		//execute query
		$result = file_get_contents( $this->fln_config['api']['articles'].'?'.$getdata, false, $context);
		$data = json_decode($result, true);
		
		//
		if ($data['success']) {
			switch($instance[ 'format' ]) {
				case 1:
					echo '<ul class="faithlifenews_article_list">';
					foreach($data['articles'] as $a) {
						$a_image = ($a['HasS3Image']) ? $this->fln_config['image_path']['s3'] . $a['ID'] . ".jpg" : $this->fln_config['image_path']['placeholder'] . $a['SourceKey'] . '.png';
						echo '<li>';
						echo '<a class="faithlifenews_article faithlifenews_article_listitem" href="'. $this->fln_config['app']['article_path'] . $a['ID'] .'/?' . $this->fln_config['app']['tracking'] . '&utm_content=' . urlencode($a['SourceName']) .'">';
						
						echo '<span class="faithlifenews_article_listitem_image" style="background-image: url('.$a_image.')"></span>';
						echo '<span class="faithlifenews_article_title faithlifenews_article_listitem_title">' . $a['Title'] . '</span>';
						echo '</a>';
					}
					break;
				
				case 2:
					foreach($data['articles'] as $a) {
						$a_image = ($a['HasS3Image']) ? $this->fln_config['image_path']['s3'] . $a['ID'] . ".jpg" : $this->fln_config['image_path']['placeholder'] . $a['SourceKey'] . '.png';
						echo '<a class="faithlifenews_article faithlifenews_article_card" href="'. $this->fln_config['app']['article_path'] . $a['ID'] .'/?' . $this->fln_config['app']['tracking'] . '&utm_content=' . urlencode($a['SourceName']) .'"><img src="'.$a_image.'" width="100%"/>';
						echo '<span class="faithlifenews_article_title faithlifenews_article_card_title">' . $a['Title'] . '</span>';
						echo '</a>';
					}
					break;
				
				case 3:
					foreach($data['articles'] as $a) {
						
						$timestamp = strtotime($a['DateStamp']);
						$date = date("M d, Y", $timestamp);
						
						echo '<a class="faithlifenews_article faithlifenews_article_titleonly" href="' . $this->fln_config['app']['article_path'] . $a['ID'] .'/?' . $this->fln_config['app']['tracking'] . '&utm_content=' . urlencode($a['SourceName']) .'">';
						//echo '<h5>' . $a['Title'] . '<br><small>' . $date . '</small></h5>';
						echo '<span class="faithlifenews_article_title faithlifenews_article_titleonly_title">' . $a['Title'] . '</span>';
						echo '</a>';
					}
					break;
				
			}
			
			
			
		} else {
			echo 'The latest breaking Christian news. <a href="' . $this->fln_config['app']['url'] . '">'.$this->fln_config['app']['url'].'</a>';
		}

		echo '</div>';
		
		//load widget styles
		faithlife_news_frontend_styles();
		
		
		//Add reftagger script
		if ($instance[ 'addreftagger' ] == 1) {
			if ($instance[ 'colorscheme' ] == 'dark') {
				faithlife_news_addreftagger_script_dark();
			} else {
				faithlife_news_addreftagger_script();
			}
		}
		
		
		echo $after_widget;
	}
	
	
	/**
	* Back-end widget form.
	*/
	public function form( $instance ) {
		
		//if instance sets color scheme get it, otherwise default to light
		if ( isset( $instance[ 'colorscheme' ] ) ) {
			$colorscheme = $instance[ 'colorscheme' ];
		}
		else {
			$instance[ 'colorscheme' ] = 'light';
			$colorscheme = 'light';
		}
		
		//Color scheme selector
		echo '<h4>Color Scheme</h4> '; 
		echo '<p><select name="'.$this->get_field_name('colorscheme').'" id="'.$this->get_field_id('colorscheme').'" class="widefat">';
		echo '<option value="light" id="colorscheme-light"', $instance['colorscheme'] == 'light' ? ' selected="selected"' : '', '>Light</option>';
		echo '<option value="dark" id="colorscheme-dark"', $instance['colorscheme'] == 'dark' ? ' selected="selected"' : '', '>Dark</option>';
		echo "</select></p>";
		
		
		//Set article format
		if ( isset( $instance[ 'format' ] ) ) {
			$format = $instance[ 'format' ];
		}
		else {
			$instance[ 'format' ] = 1;
			$format = 1;
		}
		
		//Select article format
		echo '<h4>Display format</h4> '; 
		
		for($i=1;$i<=3;$i++){
		    switch($i) {
			    case 1:
			    	$format_title = "List";
			    	$format_image = plugins_url( 'img/list-icon.png', __FILE__ );
			    	break;
			    case 2:
			    	$format_title = "Card";
			    	$format_image = plugins_url( 'img/card-icon.png', __FILE__ );
			    	break;
			    case 3:
			    	$format_title = "Title only";
			    	$format_image = plugins_url( 'img/title-icon.png', __FILE__ );
			    	break;
		    }
		    
		    echo '<label class="fln_format_container"><img ', $instance['format'] == $i ? ' style="background: #ddd;" ' : '', ' src="'.$format_image.'" /><input type="radio" name="'.$this->get_field_name('format').'" value="' . $i . '" id="'.$this->get_field_id('format').'"', $instance['format'] == $i ? ' checked="checked" ' : '', '>', $format_title, '</label>';    
		    
		}
		
		
		//if instance sets article count get it, otherwise default to 5
		if ( isset( $instance[ 'articlecount' ] ) ) {
			$articlecount = $instance[ 'articlecount' ];
		}
		else {
			$instance[ 'articlecount' ] = 5;
			$articlecount = 5;
		}
		
		//Select number of articles to display
		echo '<h4>Number of articles to show</h4> '; 
		echo '<p><select name="'.$this->get_field_name('articlecount').'" id="'.$this->get_field_id('articlecount').'" class="widefat">';
		
		for($i=1;$i<=10;$i++){
		    echo '<option value="' . $i . '" id="show-' . $i . '"', $instance['articlecount'] == $i ? ' selected="selected"' : '', '>', $i, '</option>';
		}
		echo "</select></p>";
		
		
		//if instance sets sources get them, otherwise default to all
		if ( isset( $instance[ 'sources' ] ) ) {
			$selected_sources = $instance[ 'sources' ];
		} else {
			$selected_sources = array();
			foreach($this->fln_sources as $s) {
				$selected_sources[$s->ID]->Checked = 1;
			}
			$instance[ 'sources' ] = $selected_sources;
		}
		
		//Sources checkbox list
	    echo '<h4>Choose your channels<br><small>Only articles from your channel selections will be shown</small></h4> ';
	    echo '<div class="fln_channel_container">';
	    
		foreach($this->fln_sources as $s){
		    $tempCheckFlag[$s->ID] = ($selected_sources[$s->ID]->Checked)  ? 'checked="checked"' : '';   
		    $_fieldname = $this->get_field_name('sources');
		    
		    echo '<p><label class="fln_noselect"><input class="checkbox" type="checkbox" value="1" name="'.$_fieldname.'['.$s->ID.']" id="'.$this->get_field_id($s->ID) .'" '.$tempCheckFlag[$s->ID].'>'.$s->Name.'</label> <a rel="nofollow" target="_blank" href="'. $s->Website .'"><img class="fln_external_link" src="'.plugins_url( 'img/external_link.png', __FILE__ ).'" width="15" /></a></p>';
		}
		echo '</div>';
		
		
		//if instance sets addreftagger, otherwise default to 1
		if ( isset( $instance[ 'addreftagger' ] ) ) {
			$addreftagger = $instance[ 'addreftagger' ];
		}
		else {
			$instance[ 'addreftagger' ] = 1;
			$addreftagger = 1;
		}
		
		$is_checked = ($addreftagger) ? 'checked="checked"' : '';
		
		echo '<p><label class="fln_noselect"><input class="checkbox" type="checkbox" value="1" name="'.$this->get_field_name('addreftagger').'" id="'.$this->get_field_id('addreftagger') .'" '. $is_checked .'>Include <a href="http://reftagger.com/">Reftagger</a> with this widget</label></p>';
		
		
		//load widget styles
		faithlife_news_admin_styles();
				
	}
	
	/**
	* Update Widget
	*/
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['articlecount'] = ( ! empty( $new_instance['articlecount'] ) ) ? strip_tags( $new_instance['articlecount'] ) : '';
		$instance['format'] = ( ! empty( $new_instance['format'] ) ) ? strip_tags( $new_instance['format'] ) : 0;
		$instance['colorscheme'] = ( ! empty( $new_instance['colorscheme'] ) ) ? strip_tags( $new_instance['colorscheme'] ) : 'light';	
		$instance['addreftagger'] = ( ! empty( $new_instance['addreftagger'] ) ) ? strip_tags( $new_instance['addreftagger'] ) : 1;		

	    foreach($this->fln_sources as $n){
	        $instance['sources'][$n->ID]->Checked = ((isset($new_instance['sources'][$n->ID])) && ($new_instance['sources'][$n->ID] == 1)) ? 1 : 0;
	        $instance['sources'][$n->ID]->Name = strip_tags($n->Name);
	        $instance['sources'][$n->ID]->ID = strip_tags($n->ID);   
	    }
		
		return $instance;
	}
} // class Faithlife_News_Widget