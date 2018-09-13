<?php
require 'vendor/autoload.php';

function cleanHTML($html){
	$html = str_replace("&nbsp;", " ", $html);
	return preg_replace("/&#?[a-z0-9]+;/i","",$html);
}

function error($txt, $code){
	http_response_code($code);
	echo $txt;
	die();
}

function search($q) {
	$q = mb_substr($q, 0, 1);
	$url = "https://emojipedia.org/search/?q=".$q;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$a = curl_exec($ch);
	if(preg_match('#Location: (.*)#', $a, $r))
	$l = "https://emojipedia.org".trim($r[1]);
	return $l;
}

if(!isset($_GET['q'])) error("Invalid Parameters", 400); 
$out = [];
$url = search($_GET['q']);
if(!$url) {
	error("Nothing found", 400);
	return;
} 

$res = @file_get_contents($url);
if(!$res) error('Invalid parameters', 400);

$dom = new IvoPetkov\HTML5DOMDocument();
$dom->loadHTML($res);

$article = $dom->querySelector('article');
$aliases = $dom->querySelector('.aliases');
$unicode = $dom->querySelector('.unicodename');
$vendors = $dom->querySelector('.vendor-list');
$description = $article->querySelector('.description');

$out['title'] = $article->querySelector('h1')->textContent;
//$p = $description->querySelectorAll('p');
//$out['description'] = cleanHTML($description->textContent);
//$out['description'] = cleanHTML($p[0]->innerHTML);
//$out['approved'] = str_replace( "\n", ' ', trim($p[1]->textContent));


$desc = "";
$ps = $description->querySelectorAll('p');
foreach($ps as $p){
	$desc .= cleanHTML($p->innerHTML) . "<br>";	
}
$out['description'] = $desc;

if($unicode){
	$p = $unicode->querySelector('p');
	$out['unicode_name'] = $p->textContent;
}

if($aliases){
	$tmp = [];
	$lis = $aliases->querySelectorAll('li');
	foreach($lis as $li){
		$tmp[] = $li->textContent;	
	}
	$out['aliases'] = $tmp;
}

if($vendors){
	$tmp = [];
	$lis = $vendors->querySelectorAll('li');
	foreach($lis as $li){
		$v = [];
		$h2 = $li->querySelector('h2');
		if(!$h2) continue;
		$v['name'] = $h2->textContent; 
		$img = $li->querySelectorAll('img')[1]->getAttribute('src');
		$v['img'] = $img;
		$tmp[] = $v;
	}
	$out['vendors'] = $tmp;
}

header('Content-type: application/json');
echo json_encode($out);
