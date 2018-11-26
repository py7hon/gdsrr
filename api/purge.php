<?php
  include("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  try {
    session_start();
    $client->setAccessToken($_SESSION["token"]);
    $oauth = new Google_Service_Oauth2($client);
    $drive = new Google_Service_Drive($client);
    $dump = $oauth->userinfo->get();
    $user_id = $dump->id;
    $user_check = $db->query("SELECT * FROM users WHERE prime_key='$user_id'");
    $user_row = $user_check->fetch_assoc();
    $list_options["fields"] = "id,md5Checksum,name,permissions/id,size,trashed,webContentLink,webViewLink";
    if ($user_row["permission"] < 90) {
      die("{\"status\":0,\"content\":\"Insufficient permissions\"}");
    } else {
      if (isset($_GET["mirror"])) {
        $mirror_check = $db->query("SELECT * FROM mirrors");
        while ($mirror_row = $mirror_check->fetch_assoc()) {
          $increment_row = false;
          try {
            $found_file = $drive->files->get($mirror_row["id"], $list_options);
            if ($found_file->trashed) {
              $increment_row = true;
            } else {
              $is_public = true; // Was going to check if it was public or not
              if (!($is_public)) {
                $increment_row = true;
              }
            }
          } catch (Exception $f) {
            $increment_row = true;
          }
          if ($increment_row) {
            $prime_key = strval($mirror_row["prime_key"]);
            $fail_count = strval($mirror_row["failures"] + 1);
            $db->query("UPDATE mirrors SET failures=$fail_count WHERE prime_key='$prime_key'");
          }
        }
      }
      $db->query("DELETE FROM mirrors WHERE failures>=$fail_max");
      $check_all = $db->query("SELECT * FROM files");
      while ($row = $check_all->fetch_assoc()) {
        $search_key = $row["prime_key"];
        $mirror_count = $db->query("SELECT * FROM mirrors WHERE parent='$search_key'");
        if (mysqli_num_rows($mirror_count) == 0) {
          $db->query("DELETE FROM files WHERE prime_key='$search_key'");
        }
      }
      die("{\"status\":1,\"content\":\"Deleted failed mirrors\"}");
    }
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
