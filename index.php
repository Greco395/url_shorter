<?php


// Database configuration
$dbHost     = "db_host";
$dbUsername = "db_user";
$dbPassword = "db_pass";
$dbName     = "db_name";

// Create database connection
try{
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUsername, $dbPassword);
}catch(PDOException $e){
    echo "Connection failed: " . $e->getMessage();
}


class URL_SHORTER{
    public $domain = "https://shortflow.ga/";
    protected static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";
    protected static $table = "short_urls";
    protected static $checkUrlExists = false;
    
    public function validateUrl($url){
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }
    public function checkUrl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }
    function urlExistsInDB($url){
        global $db;
        $query = "SELECT short_code FROM ".self::$table." WHERE long_url = :long_url LIMIT 1";
        $stmt = $db->prepare($query);
        $params = array(
            "long_url" => $url
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["short_code"];
    }
    public function generateRandomString($length = 6){
        $sets = explode('|', self::$chars);
        $all = '';
        $randString = '';
        foreach($sets as $set){
            $randString .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++){
            $randString .= $all[array_rand($all)];
        }
        $randString = str_shuffle($randString);
        return strtoupper($randString);
    }
    public function insertUrlInDB($url, $code){
        global $db;
        $query = "INSERT INTO `short_urls` (`id`, `long_url`, `short_code`, `hits`, `created`) VALUES (null,	'".$url."',	'".$code."',	0,	".time().");";
        $stmnt = $db->exec($query);
        return $db->lastInsertId();
    }
    public function infoID($id){
        global $db;
        $stmt = $db->prepare("SELECT * FROM short_urls WHERE id=?");
        $stmt->execute([$id]); 
        return $stmt->fetch();
    }
    public function shortize($long_url){
        if(isset($long_url)){
            $isset_urlDB = self::urlExistsInDB($long_url);
            if($isset_urlDB != true){
                return $this->domain.self::infoID(self::insertUrlInDB($long_url, self::generateRandomString(4)))['short_code'];
            }else{
                return $this->domain.$isset_urlDB;
            }
        }
        
    }
}
$shorty = new URL_SHORTER;

var_dump($shorty->shortize("https://greco395.com"));
