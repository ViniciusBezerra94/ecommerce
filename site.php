<?php

use \Hcode\Page;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


$app->get('/', function() {
    
	$page = new Page();
	$page->setTpl("index");

});

$app->get('/categories/:idcategory',function($idcategory){
	User::verifyLogin();

	$category = new Category();

	$category->get((int ) $idcategory);

	$page = new Page();
	$page->setTpl("category", array(
		"category" => $category->getValues(),
		"products" => array()
	));

});
