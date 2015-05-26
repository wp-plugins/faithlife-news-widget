<?php
	
return array(
	'image_path' => array(
		's3' => 'http://fln.s3.amazonaws.com/img/',
		'placeholder' => 'https://news.faithlife.com/images/source-placeholders/'
	),
	'api' => array(
		'key' => '1234',
		'sources' => 'https://news.faithlife.com/api/plugin_getsources.php',
		'articles' => 'https://news.faithlife.com/api/plugin_getarticles.php'
	),
	'app' => array(
		'url' => 'https://news.faithlife.com',
		'article_path' => 'https://news.faithlife.com/article/',
		'tracking' => 'utm_source=' . urlencode(CleanUrl(home_url())) . '&utm_medium=blog&utm_campaign=fln-widget',
	)
);

function CleanUrl($url) {
	$url = str_replace('http://', '', $url);
	$url = str_replace('https://', '', $url);
	
	return $url;
}