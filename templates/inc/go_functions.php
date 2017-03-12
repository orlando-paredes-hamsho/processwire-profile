<?php
	
/*--------------------------------------------------------------------------
	SEO GO
----------------------------------------------------------------------------*/
class seo{
public $title = false;
public $summary = false;
public $thumbnail = false;
public $meta = "";
public $favicon = false;
}

function seo_go(){
	
	//Setting $page and $pages to allow regular pw sintax
	$page = wire('page');
	$pages = wire('pages');
	
	//Creating the $home variable
	$home = $pages->get("/");
	
	//Creating an instance of the SEO object
	$seo = new seo();
	
	//Populating the summary Property
	($page->summary) ? $seo->summary = $page->summary : $seo->summary = $home->summary;
	
	//Populating the title Property
	($page->get('headline|title') && $home->site_name) ? $seo->title = "{$page->get('headline|title')} | {$home->site_name}" 
													   : $seo->title = $page->get('headline|title');
	
	//Populating the Thumbnail Property
	($page->google_image) ? $seo->thumbnail = $page->google_image : $seo->thumbnail = $home->google_image;
	if($seo->thumbnail){ $seo->thumbnail = "http://".$_SERVER['HTTP_HOST'].$seo->thumbnail->url; }
	
	//Populating the meta property
	$seo->meta = seo_meta($seo);
	
	//Returning the seo object
	return $seo;
}

//SEO Meta returns a meta tag string using your SEO Go data
function seo_meta($seo){
	$meta = "";
	if($seo->title) $meta .= "<title>".$seo->title."</title>";
	if($seo->summary) $meta .= "<meta name='description' content='".$seo->summary."'>";
	if($seo->thumbnail) $meta .= "<meta name='thumbnail' content='".$seo->thumbnail."'>";
	
	return $meta;
}

/*--------------------------------------------------------------------------
	FaceBook Go
----------------------------------------------------------------------------*/

//Facebook Go fills in your OG metadata from the data you put in your seo tab
function fb_go(){
	//Setting $page and $pages to allow regular pw sintax
	$page = wire('page');
	$pages = wire('pages');
	
	//Creating the $home variable
	$home = $pages->get("/");
	
	if(!$page->summary&&!$home->summary&&!$home->site_name&&!$page->facebook_image&&!$home->facebook_image) return false;
	
	//Initializing our variables
	$facebook = "";
	$image = false;
	$summary = false;
	
	//Populating the $facebook variable with our OG data
	$facebook .= "<meta property='og:url' content='".$page->httpUrl."'/>";
	
	$facebook .= "<meta property='og:title' content='".$page->get('headline|title')."'/>";
	
	($page->summary) ? $summary = $page->summary : $summary = $home->summary;
	if($summary) $facebook .= "<meta property='og:description' content='".$summary."'/>";
	
	($page->id===1) ? $facebook .= "<meta property='og:type' content='website'/>"
					: $facebook .= "<meta property='og:type' content='article'/>";
					
	if($home->site_name) $facebook .= "<meta property='og:site_name' content='".$home->site_name."'/>";
	
	($page->facebook_image) ? $image = $page->facebook_image : $image = $home->facebook_image;
	if($image) $facebook .= "<meta property='og:image' content='http://".$_SERVER['HTTP_HOST'].$image->url."'/>".
						    "<meta property='og:image:width' content='".$image->width."'/>".
						    "<meta property='og:image:height' content='".$image->height."'/>";
	
	return $facebook;
}
?>