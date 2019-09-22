<?php

use \Hcode\Page;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;


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

$app->get('/cart',function(){
	$cart = Cart::getFromSession();
	$page = new Page();
	$page->setTpl("cart", array(
		'cart'=> $cart->getValues(),
		'products'=> $cart->getProducts(),
		'error' => Cart::getMsgError()
	));
});

$app->get('/cart/:idproduct/add',function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int) $_GET['qtd'] : 1 ; 

	for ($i=0; $i < $qtd ; $i++) { 
		$cart->addProduct($product);
	}

	header("Location: /hcode_ecommerce/cart");
	exit;

});

$app->get('/cart/:idproduct/minus',function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /hcode_ecommerce/cart");
	exit;
});

$app->get('/cart/:idproduct/remove',function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product,true);

	header("Location: /hcode_ecommerce/cart");
	exit;
});

$app->post('/cart/freight',function(){

	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);

	header("Location: /hcode_ecommerce/cart");
	exit;
});

$app->get("/checkout",function(){

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address(); 

	$page = new Page();
	$page->setTpl("checkout", array(
		'cart' => $cart->getValues(),
		'address' => $address->getValues()
	));
});

$app->get("/login",function(){

	$cart = Cart::getFromSession();

	$address = new Address(); 

	$page = new Page();
	$page->setTpl("login", array(
		"error" => User::getError(),
		'errorRegister' => User::getErrorRegister(),
		'registerValues'=> (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : array(
			"name" => '',
			"email" => '',
			"phone" => ''	
		)
	));
});

$app->post("/login",function(){


	try{	
		User::login($_POST['login'],$_POST['password']);
	}catch(Exception $e){
		User::setError($e->getMessage());
	}
	header("Location: /hcode_ecommerce/checkout");
	exit;

});

$app->get("/logout",function(){

	User::logout();

	header("Location: /hcode_ecommerce/login");
	exit;

});

$app->post("/register",function(){
	$user = new User();

	$_SESSION['registerValues'] = $_POST;


	if( !isset($_POST['name']) ||  $_POST['name'] == '' )
	{
		User::setErrorRegister("Preencha o seu nome. ");
		header("Location: /hcode_ecommerce/login");
		exit;
	}

	if( !isset($_POST['email']) ||  $_POST['email'] == '' )
	{
		User::setErrorRegister("Preencha o seu email. ");
		header("Location: /hcode_ecommerce/login");
		exit;
	}

	if( !isset($_POST['password']) ||  $_POST['password'] == '' )
	{
		User::setErrorRegister("Preencha o sua senha. ");
		header("Location: /hcode_ecommerce/login");
		exit;
	}

	if(User::checkLoginExist($_POST['email'])){

		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário. ");
		header("Location: /hcode_ecommerce/login");
		exit;

	}


	$user->setData([
		'inadmin' => 0,
		'deslogin' => $_POST['email'],
		'desperson' => $_POST['name'],
		'desemail' => $_POST['email'],
		'despassword' => $_POST['password'],
		'nrphone'=> $_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'],$_POST['password']);

	header('LOcation: /hcode_ecommerce/checkout');
	exit;

});

$app->get('/forgot',function(){
	$page = new Page();
	$page->setTpl("forgot");
});

$app->post('/forgot',function(){
	
	$user = User::getForgot($_POST['email'],false);

	header("Location: /hcode_ecommerce/forgot/sent");
	exit;

});

$app->get('/forgot/sent',function(){
	$page = new Page();
	$page->setTpl("forgot-sent");
});


$app->get('/forgot/reset',function(){

	$user = User::validForgotDecrypt($_GET['code']);

	$page = new Page();
	$page->setTpl("forgot-reset", array(
		"name" => $user['desperson'],
		"code" => $_GET['code']
	));
});

$app->post('/forgot/reset',function(){
	$forgot = User::validForgotDecrypt($_POST['code']);

	User::setForgotUsed($forgot['idrecovery']);

	$user = new User();

	$password = password_hash($_POST['password'],PASSWORD_DEFAULT,array(
		"cost" => 12
	));

	$user->get((int)$forgot["iduser"]);
	$user->setPassword($password);

	$page = new Page();
	$page->setTpl("forgot-reset-success");

});

$app->get("/profile",function(){

	User::verifyLogin(false);
	$page = new Page();

	$user = User::getFromSession();


	$page->setTpl("profile", array(
		'user'=>$user->getValues(),
		'profileMsg'=> User::getSuccess(),
		'profileError'=> User::getError()
	));

});

$app->post("/profile",function(){

	User::verifyLogin(false);

	if( !isset($_POST['desperson']) || $_POST['desperson'] === '' )
	{
		User::setError("Preencha o seu nome. ");
		header("Location: /hcode_ecommerce/profile");
		exit;
	}

	if( !isset($_POST['desemail']) || $_POST['desemail'] === '' )
	{
		User::setError("Preencha o seu e-mail. ");
		header("Location: /hcode_ecommerce/profile");
		exit;
	}

	$user = User::getFromSession();

	if($_POST['desemail'] !== $user->getdesemail()){
		if(User::checkLoginExists($_POST['desemail']))
		{
			User::setError("Este endereço de e-mail já está cadastrado. ");
			header("Location: /hcode_ecommerce/profile");
			exit;
		}

	}




	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getpassword();
	$_POST['deslogin'] = $_POST['desemail'];
	$user->setData($_POST);

	$user->update();
	User::setSuccess("Dados alterados com sucesso!");

	header("Location: /hcode_ecommerce/profile");
	exit;

});
