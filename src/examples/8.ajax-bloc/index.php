<?php

include('../header.inc.php');

$tpln = new \Tpln\Engine();

$tpln->open('template.html');
$output = $tpln->render();


if(!isset($_GET['ajax']))
{
	die($output);
}
else
{
	$bloc = $tpln->getAjaxBloc("content", $output);
	die($bloc);
}