<?php

include('../header.inc.php');

$tpln = new \Tpln\Engine();

$models = array('Fiat 500', 'Mercedes class A', '');
$model = $models[rand(0,2)];

$tpln->open('template.html');
echo $tpln->render();