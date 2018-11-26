<?php
  include("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  try {
    session_start();
    $client->setAccessToken($_SESSION["token"]);
    $oauth = new Google_Service_Oauth2($client);
    $dump = $oauth->userinfo->get();
    $list_options = array();
    $return_val = array();
    $return_val["status"] = 1;
    $return_val["content"] = array();
    $return_val["content"]["count"] = 0;
    $return_val["content"]["files"] = array();
    if (isset($_GET["index"])) {
      if (ctype_digit($_GET["index"])) {
        $index = intval($_GET["index"]) - 1;
        if ($index < 0) {
          die("{\"status\":0,\"content\":\"Invalid index\"}");
        }
        $items_per_page = 10;
        $limit_lower = strval($index * $items_per_page);
        $rows = $db->query("SELECT * FROM files WHERE active=1 ORDER BY name LIMIT $limit_lower, ".strval($items_per_page));
        while ($row = $rows->fetch_assoc()) {
          $new_item = array();
          $new_item["name"] = htmlentities($row["name"]);
          $id = $row["prime_key"];
          $new_item["id"] = $id;
          $owner = $row["owner"];
          $new_item["mirrors"] = mysqli_num_rows($db->query("SELECT * FROM mirrors WHERE parent='$id' AND failures<$fail_max"));
          $owner_info = $db->query("SELECT * FROM users WHERE prime_key='$owner'")->fetch_assoc();
          $new_item["owner"] = htmlentities($owner_info["alias"]);
          array_push($return_val["content"]["files"], $new_item);
        }
        $return_val["content"]["count"] = count($return_val["content"]["files"]);
        die(json_encode($return_val));
      } else {
        die("{\"status\":0,\"content\":\"Invalid index\"}");
      }
    } else {
      die("{\"status\":0,\"content\":\"Invalid index\"}");
    }
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>