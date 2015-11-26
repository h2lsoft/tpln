<?php

include('../header.inc.php');

$tpln = new Tpln_Engine();

$tpln->open('template.html');
$tpln->parse('var', "parsing from included");


echo $tpln->render();
