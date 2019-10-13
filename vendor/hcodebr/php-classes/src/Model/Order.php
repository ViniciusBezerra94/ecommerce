<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
Use \Hcode\Model;
use \Hcode\Model\Product;
use \Hcode\Model\User;
use \Hcode\Model\Cart;


class Order extends Model
{
    const ERROR = "Order-Erro";
    const SUCCESS = "Order-Success";

    public function save()
    {

        $sql = new Sql();
        $results = $sql->select("CALL sp_orders_save(:idorder,:idcart,:iduser,:idstatus,:idaddress,:vltotal)", array(
            ':idorder'=> $this->getidorder(),
            ':idcart'=> $this->getidcart(),
            ':iduser'=> $this->getiduser(),
            ':idstatus'=> $this->getidstatus(),
            ':idaddress'=> $this->getidaddress(),
            ':vltotal'=> $this->getvltotal()
        ));

        if(count($results) > 0 ){
            $this->setData($results[0]);
        } 


    }

    public function get($idorder)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * from tb_orders a 
                inner join tb_ordersstatus b USING(idstatus)
                inner join tb_carts c USING(idcart)
                inner join tb_users d on d.iduser = a.iduser
                inner join tb_addresses e using(idaddress)
                inner join tb_persons f ON f.idperson = d.idperson
                WHERE a.idorder = :idorder
        ", array(
            ':idorder' => $idorder
        ));

        if(count($results) > 0) $this->setData($results[0]);

    }

    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * from tb_orders a 
                inner join tb_ordersstatus b USING(idstatus)
                inner join tb_carts c USING(idcart)
                inner join tb_users d on d.iduser = a.iduser
                inner join tb_addresses e using(idaddress)
                inner join tb_persons f ON f.idperson = d.idperson 
                ORDER BY a.dtregister DESC
                ");

    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_orders where idorder = :idorder", array(
            ':idorder' => $this->getidorder()
        ));
    }

    public function getCart():Cart
    {
        $cart = new Cart();
        $cart->get((int) $this->getidcart());

        return $cart;
    }


    public static function setSuccess($msg){
        $_SESSION[Order::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS] ? $_SESSION[Order::SUCCESS] : "" );
        Order::clearSuccess();
        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[Order::SUCCESS] = NULL;
    }

    public static function setError($msg){
        $_SESSION[Order::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR] ? $_SESSION[Order::ERROR] : "" );
        Order::clearError();
        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[Order::ERROR] = NULL;
    }

}
