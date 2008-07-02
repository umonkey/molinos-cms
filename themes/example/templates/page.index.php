<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
	<head>
		<title>Главная страница - </title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="description" content="" />
		<meta name="keywords" content="" />
		<meta name="robots" content="index,follow" />
	  <base href='<?php print $base; ?>' />
		<link rel="shortcut icon" href="themes/all/img/favicon.ico" type="image/x-icon" />
		<link rel="stylesheet" href="themes/example/styles/lib/refpoint.reset.css" type="text/css" />
		<link rel="stylesheet" href="themes/example/styles/lib/refpoint.typography-16.css" type="text/css" />
		<link rel="stylesheet" href="themes/example/styles/lib/refpoint.logo.css" type="text/css" />
		<link rel="stylesheet" href="themes/example/styles/page.index.css" type="text/css" />
		<!--
		<script type="text/javascript" src="themes/example/scripts/lib/jquery.js"></script>
		<script type="text/javascript" src="themes/example/scripts/pages.ordinary.js"></script>
		-->
	</head>
	<body>
		
		<div id="navigation">
			<?= $widgets['sections'] ?>
		</div>
		
		<div id="content">
			<h1 id="logo"><a href=""><span>Molinos.CMS</span></a></h1>
		
			<?= $widgets['doclist'] ?>
		
		</div>
		
	</body>
</html>
