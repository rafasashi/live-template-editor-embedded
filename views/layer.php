<?php

$ltple = LTPLE_Embedded::instance();

echo '<!DOCTYPE>';
echo '<html>';

	echo '<head>';
	
		echo '<!-- Le HTML5 shim, for IE6-8 support of HTML elements -->';
		echo '<!--[if lt IE 9]>';
		echo '<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>';
		echo '<![endif]-->';		

		echo '<meta charset="UTF-8">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		
		echo '<link rel="profile" href="http://gmpg.org/xfn/11">';
		
		echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
		echo '<link rel="dns-prefetch" href="//s.w.org">';

		echo '<title>Live Editor</title>';
		
		/*
        echo '<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/ui-lightness/jquery-ui.min.css"/>';
        echo '<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"/>';
		echo '<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>';		
			
		*/
		
	echo '</head>';

	echo '<body style="margin:0px;padding:0px;overflow:hidden;">';
		
		// embedded editor iframe
		
		echo' <iframe id="editorIframe" src="' . $ltple->embedded_url . '" frameborder="0" style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:100%;width:100%;position:absolute;top:0px;left:0px;right:0px;bottom:0px" height="100%" width="100%"></iframe>'.PHP_EOL;

		//echo' <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>'.PHP_EOL;
	
	echo '</body>';
echo '</html>';