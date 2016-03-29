<?php
/**
 * Created by PhpStorm.
 * User: iem2
 * Date: 02/11/15
 * Time: 11:41
 */

require 'vendor/autoload.php';

$app= new \Slim\Slim();

const DB_SERVER = "192.168.240.31";
const DB_USER = "root";
const DB_PASSWORD = "";
const DB = "book_auth";

$app->post('/auth', function()
{
    $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if(isset($_POST['username']))
    {
        $REQUEST_SELECT_PWD = "SELECT pwd
                                FROM USER
                                WHERE login = '".$_POST['username']."'";

        $sql = $mysqli->query($REQUEST_SELECT_PWD);

        while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
        {
            $result[] = $rlt;
        }

        // If success everythig is good send header as "OK" and return list of users in JSON format
        $return = json_encode($result);
        return $return;
    }
    else{
        echo "incorrect login";
    }
});

$app->post('/signup', function(){
        $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
        if ($mysqli->connect_errno) {
            echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }

        if(isset($_POST['email']) && isset($_POST['name']) && isset($_POST['firstname']) && isset($_POST['login']) && isset($_POST['pwd'])){
            $REQEST_INSERT_USER = "INSERT INTO USER (Mail, Name, Firstname, Login, pwd)
                                SELECT ('".$_POST['email']."','".$_POST['name']."','".$_POST['firstname']."','".$_POST['login']."','".$_POST['pwd']."')
                                FROM dual
                                WHERE NOT EXISTS(SELECT *
                                                  FROM user
                                                  WHERE login = '".$_POST['login']."')";
            $sql = $mysqli->query($REQEST_INSERT_USER);

            while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
            {
                $result[] = $rlt;
            }

            // If success everythig is good send header as "OK" and return list of users in JSON format
            $return = json_encode($result);
            return $return;
        }
});
?>