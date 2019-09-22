<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
Use \Hcode\Model;
Use \Hcode\Mailer;


class User extends Model
{
    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const CIFRA = "AES-256-CBC";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSuccess";

    public static function getFromSession()
    {
        $user = new User();

        if( isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]["iduser"] > 0){
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    public static function checkLogin($inadmin = true){
        if(
            !isset($_SESSION[User::SESSION]) 
         || !$_SESSION[User::SESSION]
         || !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ){
            // nao esta logado
            return false;
        }else{
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true ){
                return true;
            }else if ($inadmin === false){
                return true;
            }else {
                return false;
            }
        }
    }

    public static function login($login,$password)
    {
        $sql = new Sql();
        //$results = $sql->select("SELECT * from tb_users where deslogin = :LOGIN", array(
        $results = $sql->select("SELECT * from tb_users a inner join tb_persons b using(idperson) where a.deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));
        if(count($results) === 0 )
        {
            throw new \Exception("Usuario inexistente ou senha inválida.");
        }
        $data = $results[0];

        if(password_verify($password,$data["despassword"]))
        {

            $user = new User();
            $data['desperson'] = utf8_encode($data['desperson']);
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;

        }else {
            throw new \Exception("Usuario inexistente ou senha inválida.");
        }

    }

    public static function verifyLogin($inadmin = true){
        if(!User::checkLogin($inadmin))
        {
            if($inadmin){
                header("Location: /hcode_ecommerce/admin/login");
            }else{
                header("Location: /hcode_ecommerce/login");
            }

            exit;
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * from tb_users as a inner join tb_persons b using(idperson) order by b.desperson desc");

    }

    public function save(){
        $sql = new Sql();
        $result = $sql->select("CALL sp_users_save(:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)",array(
            ":desperson" => utf8_decode($this->getdesperson()),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($result[0]);

    }

    public function get($iduser){
        $sql = new Sql();
        $results = $sql->select("SELECT * from tb_users a inner join tb_persons b using(idperson) where a.iduser = :iduser",
        array(
            ":iduser" => $iduser
        ));

        $data = $results[0];

        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setData($data);
    }

    public function update()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_usersupdate_save(:iduser,:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)",array(
            ":iduser" => $this->getiduser(),
            ":desperson" => utf8_decode($this->getdesperson()),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();
        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }

    public static function getForgot($email, $inadmin = true){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_persons a inner join tb_users b USING(idperson) where a.desemail = :email", array(
            ":email" => $email
        ));

        if(count($results) === 0){
            throw new \Exception("Não foi possivel recuperar a senha.");
        }else{

            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
            array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"]
            ));

            if(count($results2) === 0 ){
                throw new \Exception("Não foi possivel recuperar a senha");
            }

            $dataRecovery = $results2[0];

            $IV = substr(hash('sha256', USER::CIFRA), 0, 16);

            $code = base64_encode(openssl_encrypt($dataRecovery['idrecovery'], User::CIFRA,User::SECRET, 0, $IV));
            if($inadmin){
                $link = "http://localhost/hcode_ecommerce/admin/forgot/reset?code=$code";
            }else{
                $link = "http://localhost/hcode_ecommerce/forgot/reset?code=$code";
            }
            
            

            $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store","forgot",array(
                "name"=>$data["desperson"],
                "link"=>$link
            ));

            $mailer->send();

            return $data;
            

        }

    }

    public static function validForgotDecrypt($code)
    {
        base64_decode($code);
        $IV = substr(hash('sha256', USER::CIFRA), 0, 16); 


        $idrecovery = openssl_decrypt(base64_decode($code), User::CIFRA, USER::SECRET, 0 ,$IV);
        $sql = new Sql();
        $result = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a 
        INNER JOIN tb_users b USING(iduser)
        INNER JOIN tb_persons c USING(idperson)
        WHERE
            a.idrecovery = :idrecovery
            AND
            a.dtrecovery IS NULL 
            AND 
            DATE_ADD(a.dtregister,INTERVAL 1 HOUR) >= now();            
        ", array(
            ":idrecovery" => $idrecovery
        ));

        if(count($result) === 0){
            throw new \Exception("Não foi possivel recuperar a senha.");
        }
        else{
            return $result[0];
        }


    }

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = now() where
        idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));

    }

    public function setPassword($password){
        
        $sql = new Sql();
        $sql->query("UPDATE tb_users SET despassword = :password where iduser = :iduser",array(
            ":password" => $password,
            "iduser" => $this->getiduser()
        ));
    }

    public static function setError($msg){
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR] ? $_SESSION[User::ERROR] : "" );
        User::clearError();
        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function getErrorRegister()
    {
        $msg = ( isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]  ? $_SESSION[User::ERROR_REGISTER] : "");
        User::clearErrorRegister();
    }

    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    public static function getPasswordHash($password)
    {
        return password_hash($password,PASSWORD_DEFAULT, array(
            'cost' => 12
        ));
    }

    public static function checkLoginExist($login)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * from tb_users where deslogin = :deslogin", array(
            ':deslogin'=> $login
        ));

        return (count($results) > 0);

    }


    public static function setSuccess($msg){
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS] ? $_SESSION[User::SUCCESS] : "" );
        User::clearSuccess();
        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[User::SUCCESS] = NULL;
    }


}
