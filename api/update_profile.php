<?php
  iinclude("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  try {
    session_start();
    $client->setAccessToken($_SESSION["token"]);
    $oauth = new Google_Service_Oauth2($client);
    $dump = $oauth->userinfo->get();
    $show_private = "0";
    if (!(isset($_GET["show_private"]))) {
      die("{\"status\":0,\"content\":\"Please fill in all parameters!\"}");
    } else {
      if ($_GET["show_private"] == "1") {
        $show_private = "1";
      }
    }
    if (!(isset($_GET["alias"])) || (strlen($_GET["alias"]) < 1)) {
      die("{\"status\":0,\"content\":\"Please fill in all parameters!\"}");
    }
    $alias = urldecode($_GET["alias"]);
    $alias = $db->real_escape_string($alias);
    // echo $alias;
    $user_id = $dump->id;
    $db->query("UPDATE users SET alias='$alias', show_private=$show_private WHERE prime_key='$user_id'") or die("{\"status\":0,\"content\":\"Failed to update profile\"}");
    die("{\"status\":1,\"content\":\"Profile updated\"}");
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
