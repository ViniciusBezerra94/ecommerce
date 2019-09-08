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

$app->get('/categories/:idcategory',function($idcategory){
	$category = new Category();

	$page = ( isset($_GET['page']) ) ? (int)$_GET['page'] : 1;

	$category->get( (int) $idcategory );

	$pagination = $category->getProductsPage($page);
	
	$pages = [];
	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
		array_push($pages, array(
			'link' => '/hcode_ecommerce/categories/' . $category->getidcategory(). '?page=' . $i,
			'page' => $i
		));
	}
	
	$page = new Page();
	


	$page->setTpl("category", array(
		'category' => $category->getValues(),
		'products' => Product::checkList($pagination["data"]),
		'pages' => $pages
	));

});

$app->get("/products/:desurl",function($desurl){
	$product = new Product();
	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", array(
		'product' => $product->getValues(),
		'categories' => $product->getCategories()
	));

});

