<?php
require 'vendor/autoload.php';

$app= new \Slim\Slim();

const DB_SERVER = "192.168.240.31";
const DB_USER = "root";
const DB_PASSWORD = "";
const DB = "monPetitBouquin";

// SQL request selecting all books with ISBN, title, Author's name and firstname, and average of all rates
const REQUEST_ALL_BOOK = "SELECT B.ISBN, B.Title, A.Name, A.Firstname, AVG(C.Rate)
                            FROM BOOK B
                                  LEFT JOIN BOOK_AUTHOR BA
                                      ON B.ISBN =BA.IdBook
                                  LEFT JOIN AUTHOR A
                                      ON A.Id = BA.IdAuthor
                                  LEFT JOIN CRITICISM C
                                      ON B.ISBN = C.IdBook
                            GROUP BY B.ISBN, B.Title, A.Name, A.Firstname";
                            
// request selecting all authors with name firstname date of birth, date of death and total of books wrote
const REQUEST_ALL_AUTHOR = "SELECT Name, Firstname, birthYear, deathYear, COUNT(IdBook)
                            FROM AUTHOR, BOOK_AUTHOR
                            WHERE Id = IdAuthor
                              GROUP BY Name, Firstname";


// Function selecting all the books in the database and returning them as a JSON array
$app->get('/book', function () {
	$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    $sql = $mysqli->query(REQUEST_ALL_BOOK);

    while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
    {
        $result[] = $rlt;
    }

    // If success everythig is good send header as "OK" and return list of users in JSON format
    $return = json_encode($result);
    echo $return;
    return $return;
});

// Function selecting all the books wrote by an author in the database and returning them as a JSON array
$app->get('/book/:authorId', function ($authorId) {
    $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    $REQUEST_AUTHOR_BOOK = "SELECT B.ISBN, B.Title,B.Editor
                          FROM AUTHOR A, BOOK B, BOOK_AUTHOR BA
                          WHERE A.ID = '".$authorId."'
                          GROUP BY B.ISBN, A.Name";

    $sql = $mysqli->query($REQUEST_AUTHOR_BOOK);

    while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
    {
        $result[] = $rlt;
    }

    // If success everythig is good send header as "OK" and return list of users in JSON format
    $return = json_encode($result);
    echo $return;
    return $return;
});

// Function getting all the authors in the database and returning them as a JSON array
$app->get('/author', function () {
	$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
    $sql = $mysqli->query(REQUEST_ALL_AUTHOR);

    while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
    {
        $result[] = $rlt;
    }

    // If success everythig is good send header as "OK" and return list of users in JSON format
    $return = json_encode($result);
    echo $return;
    return $return;
});

// Function getting all the criticisms for one book $bookId as ISBN and returning them as a JSON array
$app->get('/criticism/:bookId', function ($bookId) {
	$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);

    $REQUEST_CRITICISM_BY_BOOK = "SELECT IdBook, IdUser, Rate, Comment FROM CRITICISM WHERE IdBook LIKE '$bookId%'";

    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
    $sql = $mysqli->query($REQUEST_CRITICISM_BY_BOOK);

    while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
    {
        $result[] = $rlt;
    }

    // If success everythig is good send header as "OK" and return list of users in JSON format
    $return = json_encode($result);
    echo $return;
    return $return;
});

// Function searching a list of books by a part of their ISBNs or a part of their authors names or a part of their titles
// and returning them as a JSON array
$app->post('/search', function () use ($app){
    if(isset($_POST['research'])) {
        $research = $_POST['research'];
        $REQUEST_SEARCH = "SELECT ISBN, Title, Editor, Name, Firstname, birthYear, deathYear
                                        FROM BOOK, AUTHOR, BOOK_AUTHOR
                                        WHERE ISBN = IdBook
                                          AND IdAuthor = Id
                                          AND (CAST(ISBN AS CHAR) LIKE '%$research%'
                                            OR (Name LIKE '%$research%' OR Firstname LIKE '%$research%')
                                            OR (Title LIKE '%$research%'))";
    }

	$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);

    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if(isset($research))
    {
        $sql = $mysqli->query($REQUEST_SEARCH);
        while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
        {
            $result[] = $rlt;
        }
    }
    else
    {
        echo "You didn't entered a research";
    }

    // If success everythig is good send header as "OK" and return list of users in JSON format
    $return = json_encode($result);
    echo $return;
    return $return;
});

// Function creating a new book in the database
$app->post('/insertBook', function(){
    $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if(isset($_POST['isbn']) && isset($_POST['title']) && isset($_POST['editor']) && isset($_POST['authorName']) && isset($_POST['authorFirstname']) && isset($_POST['birth']) && isset($_POST['death'])){
        $REQUEST_INSERT_USER = "INSERT INTO USER (ISBN, Title, Editor)
                                SELECT ('".$_POST['isbn']."','".$_POST['title']."','".$_POST['editor']."')
                                FROM dual
                                WHERE NOT EXISTS(SELECT *
                                                  FROM user
                                                  WHERE ISBN = '".$_POST['isbn']."');
                                INSERT INTO AUTHOR (Name, Firstname, birthYear, deathYear)
                                SELECT('".$_POST['authorName']."','".$_POST['authorFirstname']."','".$_POST['birth']."','".$_POST['death']."')
                                FROM dual
                                WHERE NOT EXISTS(SELECT *
                                                  FROM AUTHOR
                                                  WHERE Name='".$_POST['authorName']."'AND Firstname='".$_POST['authorFirstname']."' AND birthYear='".$_POST['birth']."');
                                INSERT INTO BOOK_AUTHOR (IdBook,IdAuthor)
                                VALUES('".$_POST['isbn']."',(SELECT Id FROM AUTHOR WHERE Name='".$_POST['authorName']."'AND Firstname='".$_POST['authorFirstname']."' AND birthYear='".$_POST['birth']."'))

                                ";
        $sql = $mysqli->query($REQUEST_INSERT_USER);

        while($rlt = $sql->fetch_array(MYSQLI_ASSOC))
        {
            $result[] = $rlt;
        }

        // If success everythig is good send header as "OK" and return list of users in JSON format
        $return = json_encode($result);
        echo $return;
        return $return;
    }
});

// Function creating a new author in the database
$app->post('/insertAuthor', function(){
    $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if(isset($_POST['name']) && isset($_POST['firstname']) && isset($_POST['birthYear']) && isset($_POST['deathYear'])){
        $REQUEST_INSERT_USER = "INSERT INTO AUTHOR (Name, Firstname, birthyear, deathYear)
                                SELECT ('".$_POST['name']."','".$_POST['firstname']."','".$_POST['birthYear']."','".$_POST['deathYear']."')
                                FROM dual
                                WHERE NOT EXISTS(SELECT *
                                                  FROM AUTHOR
                                                  WHERE Name = '".$_POST['name']."'
                                                  AND Firstname = '".$_POST['firstname']."'
                                                  AND birthYear = '".$_POST['birthYear']."')
                                ";
        $sql = $mysqli->query($REQUEST_INSERT_USER);
    }
});

// Function creating a new criticism in the database
$app->post('/insertCriticism', function(){
    $mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB);
    if ($mysqli->connect_errno) {
        echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    if(isset($_POST['idBook']) && isset($_POST['idUser']) && isset($_POST['Rate']) && isset($_POST['Comment'])){
        $REQUEST_INSERT_USER = "INSERT INTO CRITICISM (idBook, idUser, Rate, Comment)
                                VALUES ('".$_POST['idBook']."','".$_POST['idUser']."','".$_POST['Rate']."','".$_POST['Comment']."')
                                ";
        $sql = $mysqli->query($REQUEST_INSERT_USER);
    }
});

$app->run();


?>
