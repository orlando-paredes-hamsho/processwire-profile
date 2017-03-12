<?php include("./inc/header.php"); ?>
<h2><?=$page->get("headline|title"); ?></h2>
<?php 
foreach($page->children as $article){
	echo "<h3>".$article->get("headline|title")."</h3>";
	if($article->summary){
		echo "<p>".$article->summary."</p>";
	}else{
		echo "<p>".strip_tags(substr($article->body, 0,140))."</p>";
	} 
} 
?>
<?php include("./inc/footer.php"); ?>