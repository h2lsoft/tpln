<?php

include('../header.inc.php');

$tpln = new \Tpln\Engine();


$tpln->open('template.html');

// form custom
$tpln->formSetLang('en');
$tpln->formSetName('my_form');
$tpln->formSetInputName('ZipCode', 'Zip code');
$tpln->formSetInputName('CreditCard', 'Credit card');

// input form init example (use record from database for example)
$record = array('Gender' => 'MALE', 'Name' => 'John', 'Colors' => array('Green', 'Blue'));
$tpln->formInit($record);

// form rules
$tpln->required('Gender')->inList('', array('MALE', 'FEMALE'));
$tpln->required('Name')->alpha()->min('', 5)->max('', 15);
$tpln->required('Birthdate')->date('', 'd/m/Y');
$tpln->required('Email')->email();
$tpln->required('Country');
$tpln->required('Fruits[]');
$tpln->required('Colors[]')->inList('', array('Red', 'Green', 'Blue')); # multiple
$tpln->alphaNumeric('Password', array('-','_'));
$tpln->digit('ZipCode')->charLength('', 5, 5);
$tpln->required('CreditCard')->mask('', '9999-99-99 AAA 9999');

$tpln->fileControl('File', false, '10 ko', 'txt', 'text/plain');

// image jpg <= 500 x 500 and 500 ko
$tpln->fileControl('Image', false, '500 ko', 'jpg')
     ->imageDimension('', '<=', 500, '<=', 500);

$tpln->required('Url')->url();
$tpln->max('Comment', 50);


if($tpln->formIsValid())
{
	// no error do want you want (database, mail, ...)
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
}

echo $tpln->render();