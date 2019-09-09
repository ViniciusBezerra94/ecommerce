<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
Use \Hcode\Model;
use \Hcode\Model\Product;
use \Hcode\Model\User;


class Cart extends Model
{
    const SESSION = "Cart";

    public static function getFromSession()
    {
        $cart = new Cart();

        if( isset( $_SESSION[Cart::SESSION] ) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0 ){
            $cart->get((int) $_SESSION[Cart::SESSION]['idcart']);

        }
        else
        {
            $cart->getFromSessionID();

            if(!(int)$cart->getidcart() > 0)
            {
                $data = array(
                    'dessessionid' => session_id() 
                );

                if(User::checkLogin(false) === true){
                    $user = User::getFromSession();
                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);
                $cart->save();
                $cart->setToSession();            

            }
        }
        return $cart;
    }

    public function setToSession(){
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID()
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts where dessessionid = :dessessionid ", array(
            ':dessessionid'=>session_id()
        ));

        if(count($results) > 0){
            $this->setData($results[0]);
        }
    }

    public function get(int $idcart)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts where idcart = :idcart ", array(
            ':idcart'=>$idcart
        ));

        if(count($results) > 0){
            $this->setData($results[0]);
        }

        
    }

    public function save()
    {
        $sql = new Sql();
        $results = $sql->select ("Call sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",array(
            ":idcart" => $this->getidcart(),
            ":dessessionid" => $this->getdessessionid(),
            ":iduser" => $this->getiduser(),
            ":deszipcode" => $this->getdeszipcode(),
            ":vlfreight" => $this->getvlfreight(),
            ":nrdays" => $this->getnrdays()
        ));

        $this->setData($results[0]);
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();
        $sql->query("Insert into tb_cartsproducts (idcart, idproduct) values (:idcart, :idproduct)", array(
            ":idcart" => $this->getidcart(),
            ':idproduct' => $product->getidproduct()
        ));
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();

        if($all)
        {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = now() where idcart = :idcart
            and idproduct = :idproduct and dtremoved is null", array(
                ':idcart' => $this->getidcart(),
                ':idproduct' => $product->getidproduct()
            ));
        }
        else
        {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = now() where idcart = :idcart
            and idproduct = :idproduct and dtremoved IS NULL limit 1", array(
                ':idcart' => $this->getidcart(),
                ':idproduct' => $product->getidproduct()
            ));
        }
    }

    public function getProducts()
    {
        $sql = new Sql();
        return Product::checkList($sql->select("SELECT b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight,b.desurl,count(*) as nrqtd, SUM(b.vlprice) as vltotal
        from tb_cartsproducts a 
        inner join tb_products b on a.idproduct = b.idproduct 
        where a.idcart = :idcart 
        and a.dtremoved is null
        group by b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight, b.desurl
        order by b.desproduct", array(
            ':idcart'=>$this->getidcart()
        )));
    }
}
