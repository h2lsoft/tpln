<?php

include('../header.inc.php');

$tpln = new \Tpln\Engine();

$tpln->open('template.html');

// bloc 1
$index_1 = rand(1, 3);
for($i=0; $i < $index_1; $i++)
{
	$tpln->parse('level_1.text', ($i+1)." / $index_1");

	// bloc 2
	$index_2 = rand(1, 3);
	for($j=0; $j < $index_2; $j++)
	{
		$tpln->parse('level_1.level_2.text', ($j+1)." / $index_2");

		// bloc 3
		$index_3 = rand(1, 3);
		for($k=0; $k < $index_3; $k++)
		{
			$tpln->parse('level_1.level_2.level_3.text', ($k+1)." / $index_3");
			$tpln->loop('level_1.level_2.level_3');
		}

		$tpln->loop('level_1.level_2');
	}

	$tpln->loop('level_1');
}


echo $tpln->render();


