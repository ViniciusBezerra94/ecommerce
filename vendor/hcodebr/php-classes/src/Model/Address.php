<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
Use \Hcode\Model;
use \Hcode\Model\Product;
use \Hcode\Model\User;


class Address extends Model
{
    const SESSION_ERROR = "address_error";

    public static function setMsgError($msg)
    {
        $_SESSION[Address::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = ( isset( $_SESSION[Address::SESSION_ERROR] ) ) ? $_SESSION[Address::SESSION_ERROR] : "";
        Address::clearMSgError();
        return $msg;

    }

    public static function clearMSgError()
    {
        $_SESSION[Address::SESSION_ERROR] = null;
    }


    public static function getCEP($nrcep){
        $nrcep = str_replace("-","",$nrcep);


        $ch = curl_init();
        //https://viacep.com.br/ws/09855370/json/

        curl_setopt($ch,CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

        $data = json_decode(curl_exec($ch),true);

        curl_close($ch);

        return $data;



    }


    public  function loadFromCEP($nrcep)
    {
        $data = Address::getCEP($nrcep); 

        if( isset($data['logradouro']) && $data['logradouro'] )
        {
            $this->setdesaddress($data['logradouro']);
            $this->setdescomplement($data['complemento']);
            $this->setdesdistrict($data['bairro']);
            $this->setdescity($data['localidade']);
            $this->setdesstate($data['uf']);
            $this->setdescountry("Brasil");
            $this->setdeszipcode($nrcep);
        }

    }

    public function save()
    {

        $sql = new Sql();
        $results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, 
        :descountry, :deszipcode, :deszipcode)", array(
            ':idaddress'=> $this->getidaddress(),
            ':idperson'=> $this->getidperson(),
            ':desaddress'=> utf8_decode($this->getdesaddress()),
            ':desnumber'=> $this->getdesnumber(),
            ':descomplement'=> utf8_decode($this->getdescomplement()),
            ':descity'=> utf8_decode($this->getdescity()),
            ':desstate'=> utf8_decode($this->getdesstate()),
            ':descountry'=> utf8_decode($this->getdescountry()),
            ':deszipcode'=> $this->getdeszipcode(),
            ':desdistrict'=> $this->getdesdistrict()
        ));

        if(count($results) > 0 ){
            $this->setData($results[0]);
        } 


    }

}
