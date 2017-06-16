<?php

$ltple = LTPLE_Embedded::instance();

// --------------- head content ---------------------

ob_start();

wp_head();

$head = ob_get_clean();
	
$head = preg_replace( array(

		'/<title(.*?)<\/title>/is',
		//'/<script(.*?)<\/script>/is',
		//'/<style(.*?)<\/style>/is',
		//'/<meta(.*?)>/is',
		//'/<link(.*?)>/is',
	), 
	'', $head
);

// --------------- footer content -------------------

/*
ob_start();

get_footer();

$footer = ob_get_clean();
var_dump($footer);exit;

*/

// --------------- add wp elements ----------------

$ltple->layer = str_replace( '</head>', $head . '</head>', $ltple->layer );

//$ltple->layer = str_replace( '</body>' . PHP_EOL . '</html>', $footer, $ltple->layer );

// ----------------- output layer ------------------

echo $ltple->layer;