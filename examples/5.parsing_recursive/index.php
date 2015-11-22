<?php

include('../header.inc.php');

$tpln = new Tpln_Engine();

$families = [
	[
		'lastname' => 'Sters',
		'children' => [
							['lastname' => "Sters", 'firstname' => 'Amanda'],
							['lastname' => "Mopius", 'firstname' => 'Billy'],
							['lastname' => "Sters", 'firstname' => 'Luc jr']
						]
	],
	[
		'lastname' => 'Berguson',
		'children' => []
	],
	[
		'lastname' => 'Alfonso',
		'children' => [
							['lastname' => "Vitali", 'firstname' => 'Antonio'],
							['lastname' => "Bougloni", 'firstname' => 'Marcelo']
						]
	]
];


$tpln->open('template.html');

$tpln->parseArray('family', $families);


echo $tpln->render();
