<?php
  include("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  try {
    session_start();
    $client->setAccessToken($_SESSION["token"]);
    $oauth = new Google_Service_Oauth2($client);
    $drive = new Google_Service_Drive($client);
    $dump = $oauth->userinfo->get();
    $list_options = array();
    $list_options["fields"] = "files(id,md5Checksum,name,permissions(emailAddress,id,role),size,trashed,webContentLink,webViewLink)";
    $files_list = $drive->files->listFiles($list_options)->getFiles();
    $return_val = array();
    $return_val["status"] = 1;
    $return_val["content"] = array();
    $return_val["content"]["available"] = array();
    $return_val["content"]["available"]["count"] = 0;
    $return_val["content"]["available"]["files"] = array();
    $return_val["content"]["uploaded"] = array();
    $return_val["content"]["uploaded"]["count"] = 0;
    $return_val["content"]["uploaded"]["files"] = array();
    $user_id = $dump->id;
    $user_check = $db->query("SELECT * FROM users WHERE prime_key='$user_id'");
    $user_row = $user_check->fetch_assoc();
    $show_private = false;
    if ($user_row["show_private"] == 1) {
      $show_private = true;
    }
    // echo $show_private;
    foreach ($files_list as $file) {
      if ($file->trashed) {
        continue;
      }
      $hash = $file->md5Checksum;
      if (is_null($hash) || strlen($hash) < 1) {
        continue;
      }
      if (substr($file->name, 0, strlen($file_prefix)) === $file_prefix) {
        continue;
      }
      $file_key = substr(str_replace("+", "_", str_replace("/", "_", str_replace("=", "-", base64_encode(md5($file->id, true))))), 0, -2);
      $key_check = $db->query("SELECT * FROM files WHERE prime_key='$file_key'");
      if (mysqli_num_rows($key_check) > 0) {
        continue;
      }
      $is_owned = false;
      $is_public = false;
      foreach ($file->permissions as $permission) {
        if ($permission->id == "anyoneWithLink") {
          $is_public = true;
          continue;
        }
        if ($permission->role == "owner") {
          // echo $permission->email;
          // echo $dump->email;
          if ($permission->emailAddress == $dump->email) {
            $is_owned = true;
          }
          // if ($is_owned) {
          //   echo "owned";
          // } else {
          //   echo "no";
          // }
        }
      }
      if ($is_public || $is_owned) {
        $single_file = array();
        $single_file["id"] = $file->id;
        $single_file["name"] = $file->name;
        $single_file["size"] = $file->size;
        // $single_file["public"] = $is_public;
        if ($is_public || ($show_private && $is_owned)) {
          array_push($return_val["content"]["available"]["files"], $single_file);
        }
      }
    }
    $result = $db->query("SELECT * FROM files WHERE owner='$user_id'");
    while ($row = $result->fetch_assoc()) {
      $row_info = array();
      $row_info["name"] = $row["name"];
      $row_info["id"] = $row["prime_key"];
      array_push($return_val["content"]["uploaded"]["files"], $row_info);
    }
    $return_val["content"]["available"]["count"] = count($return_val["content"]["available"]["files"]);
    $return_val["content"]["uploaded"]["count"] = count($return_val["content"]["uploaded"]["files"]);
    die(json_encode($return_val));
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
