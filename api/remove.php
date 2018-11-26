<?php
  include("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  try {
    session_start();
    $client->setAccessToken($_SESSION["token"]);
    $oauth = new Google_Service_Oauth2($client);
    $drive = new Google_Service_Drive($client);
    $dump = $oauth->userinfo->get();
    if (isset($_GET["id"])) {
      $search_key = $db->real_escape_string($_GET["id"]);
      $check = $db->query("SELECT * FROM files WHERE prime_key='$search_key'");
      if (mysqli_num_rows($check) == 0) {
        die("{\"status\":0,\"content\":\"Invalid file ID\"}");
      } else {
        $result = $db->query("DELETE FROM files WHERE prime_key='$search_key'");
        die("{\"status\":1,\"content\":\"File removed\"}");
      }
    } else {
      die("{\"status\":0,\"content\":\"Invalid file ID\"}");
    }
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
