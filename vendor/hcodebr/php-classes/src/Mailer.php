<?php

namespace Hcode;

use Rain\Tpl;

class Mailer 
{

    const USERNAME = "user@gmail.com";
    const PASSWORD = "<senha>";
    const NAME_FROM = "Hcode Store";
    private $mail;

    public function __construct($toAddress,$toName,$subject,$tplName, $data = array())
    {
        $config = array(
            "tpl_dir" => $_SERVER['DOCUMENT_ROOT'] . "/hcode_ecommerce/views/email/",
            "cache_dir" => $_SERVER['DOCUMENT_ROOT']. "/hcode_ecommerce/views-cache/",
            "debug" => false
        );

        Tpl::configure($config);

        $tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }
        $html = $tpl->draw($tplName,true);

        $this->mail = new \PHPMailer();
        $this->mail->isSMTP();
        $this->mail->Port = 587;
 
        $this->mail->SMTPDebug = 0;
        $this->mail->Debugoutput = 'html';
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPSecure = 'tls';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = Mailer::USERNAME;
        $this->mail->Password = Mailer::PASSWORD;

        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

        $this->mail->addAddress($toAddress, $toName);
        $this->mail->Subject = $subject;

        $this->mail->msgHTML($html);


    }


    public function send()
    {
        return $this->mail->send();
    }

}

?>

