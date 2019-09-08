<?php
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app->get('/admin/categories',function(){
	User::verifyLogin();

	$categories = category::listAll();

	$page = new PageAdmin();
	$page->setTpl("categories",array(
		"categories" => $categories
	));



});

$app->get('/admin/categories/create',function(){
	User::verifyLogin();


	$page = new PageAdmin();
	$page->setTpl("categories-create");
});

$app->post('/admin/categories/create',function(){
	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);
	$category->save();

	header("Location: /hcode_ecommerce/admin/categories");
	exit;

});

$app->get('/admin/categories/:idcategory/delete', function($idcategory){
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);
	$category->delete();

	header("Location: /hcode_ecommerce/admin/categories");
	exit;


});

$app->get('/admin/categories/:idcategory',function($idcategory){
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);

	$page = new PageAdmin();
	$page->setTpl("categories-update",array(
		"category" => $category->getValues()
	));
});

$app->post('/admin/categories/:idcategory', function($idcategory){
	User::verifyLogin();

	$category = new Category();
	$category->get((int)$idcategory);
	$category->setData($_POST);
	$category->update();

	header("Location: /hcode_ecommerce/admin/categories");
	exit;


});