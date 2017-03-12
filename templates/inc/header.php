<!DOCTYPE html>
<html>
<?php include("go_functions.php"); ?>
<head>
	<!-- META -->
	<?=seo_go()->meta; ?>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="generator" content="ProcessWire">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
	<!--link rel="icon" type="image/png" href="<?=$config->urls->templates?>img/favico.png"-->

	<!-- CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" media="all" />
	<link rel="stylesheet" type="text/css" href="<?=$config->urls->templates?>css/kickstart.css" media="all" />
	<link rel="stylesheet" type="text/css" href="<?=$config->urls->templates?>style.css" media="all" /> 
	
	<!-- Javascript -->
	<script type="text/javascript" async src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?=$config->urls->templates?>js/kickstart.js"></script>
	
	<!-- Facebook -->
	<?=fb_go(); ?>
	<!-- Facebook -->
</head>
<body>