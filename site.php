<?php

use \Hcode\Page;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


$app->get('/', function() {
	
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", array(
		"product" => Product::checkList($products)
	));

});

