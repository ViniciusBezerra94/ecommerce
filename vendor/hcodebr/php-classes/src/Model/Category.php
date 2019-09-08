<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
Use \Hcode\Model;
Use \Hcode\Mailer;
use \Hcode\Model\Product;


class Category extends Model
{
    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * from tb_categories order by descategory");

    }

    public function save()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",array(
            ":idcategory" => $this->getidcategory(),
            ":descategory" => $this->getdescategory()
        ));

        $this->setData($results[0]);

        Category::updateFile();

    }

    public function update()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",array(
            ":idcategory" => $this->getidcategory(),
            ":descategory" => $this->getdescategory()
        ));

        $this->setData($results[0]);

        Category::updateFile();

    }

    public function get($idcategory){
        $sql = new Sql();
        $results = $sql->select("SELECT * from tb_categories where idcategory = :idcategory",
        array(
            ":idcategory" => $idcategory
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();
        $sql->query("DELETE FROM tb_categories where idcategory = :idcategory", array(
            ":idcategory" => $this->getidcategory()
        ));

        Category::updateFile();

    }

    public static function updateFile()
    {
        $categories = Category::listAll();

        $html = [];

        foreach ($categories as $row ) {
            array_push($html, '<li><a href="/hcode_ecommerce/categories/' . $row['idcategory'] . '">'. $row['descategory'] .'</a></li>');            
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] 
            . DIRECTORY_SEPARATOR . 'hcode_ecommerce' . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'categories-menu.html',implode("",$html) );

    }

    public function getProducts($related = true)
    {
        $sql = new Sql();
        $results = array();
        if($related)
        {
            $results = $sql->select("SELECT * FROM tb_products where idproduct IN(
            
                SELECT a.idproduct FROM tb_products a inner join tb_productscategories b on a.idproduct = b.idproduct
                where b.idcategory = :idcategory)", array(
                    ":idcategory" => $this->getidcategory()
                ));

        }
        else
        {
            $results = $sql->select(" SELECT * FROM tb_products where idproduct NOT IN(
            
            SELECT a.idproduct FROM tb_products a inner join tb_productscategories b on a.idproduct = b.idproduct
            where b.idcategory = :idcategory)",array(
                ":idcategory" => $this->getidcategory()
            ));

        }
        return $results;
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();
        $sql->query("INSERT INTO tb_productscategories (idcategory,idproduct) values (:idcategory,:idproduct)",
        array (
            ":idcategory"=> $this->getidcategory(),
            ":idproduct"=> $product->getidproduct()
        ));
    }

    public function removeProduct(Product $product)
    {
        $sql = new Sql();
        $sql->query("DELETE FROM tb_productscategories where idcategory = :idcategory and idproduct = :idproduct",
        array (
            ":idcategory"=> $this->getidcategory(),
            ":idproduct"=> $product->getidproduct()
        ));
    }    

}
