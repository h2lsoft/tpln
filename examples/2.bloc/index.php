<?php

include('../header.inc.php');

$tpln = new Tpln_Engine();

$tpln->open('template.html');

$users = [
			['firstname' => "John", 'lastname' => "Doe"],
			['firstname' => "Johnny", 'lastname' => "Depp"],
			['firstname' => "Sarah", 'lastname' => "Connor"],
			['firstname' => "Arnorld", 'lastname' => "Swcharzenegger"]
];

foreach($users as $user)
{
	$tpln->parse('users.firstname', $user['firstname']);
	$tpln->parse('users.lastname', $user['lastname']);
	$tpln->loop('users');
}

// or you can use @foreach syntax in template see example




echo $tpln->render();


