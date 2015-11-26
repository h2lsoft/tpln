<?php

include('../header.inc.php');

$tpln = new Tpln_Engine();

$tpln->open('template.html');

$families = [

	[
		'lastname' => 'Sters',
		'childrens' => [
							['lastname' => "Sters", 'firstname' => 'Amanda'],
							['lastname' => "Mopius", 'firstname' => 'Billy'],
							['lastname' => "Sters", 'firstname' => 'Luc jr']
						]
	],

	[
		'lastname' => 'Berguson',
		'childrens' => []
	],

	[
		'lastname' => 'Alfonso',
		'childrens' => [
							['lastname' => "Vitali", 'firstname' => 'Antonio'],
							['lastname' => "Bougloni", 'firstname' => 'Marcelo']
						]
	]

];

foreach($families as $family)
{
	$tpln->parse('family.lastname', $family['lastname'], '|strtoupper');
	$tpln->parse('family.children_count', count($family['childrens']));

	if(!count($family['childrens']))
	{
		$tpln->eraseBloc('family.children');
		$tpln->loop('family.children');
	}
	else
	{
		foreach($family['childrens'] as $children)
		{
			$tpln->parse('family.children.firstname', $children['firstname']);
			$tpln->parse('family.children.lastname', $children['lastname']);
			$tpln->loop('family.children');
		}
	}

	$tpln->loop('family');
}



echo $tpln->render();


