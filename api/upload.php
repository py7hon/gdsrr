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
      try {
        $list_options = array();
        $list_options["fields"] = "id,md5Checksum,name,permissions(emailAddress,id,role),size,trashed,webContentLink,webViewLink";
        $found_file = $drive->files->get($_GET["id"], $list_options);
        $is_public = false;
        $change_publicity = false;
        foreach ($found_file->permissions as $permission) {
          if ($permission->id == "anyoneWithLink") {
            $is_public = true;
            break;
          }
        }
        if (!($is_public)) {
          try {
            $permission = new Google_Service_Drive_Permission();
            $permission->setRole("reader");
            $permission->setType("anyone");
            $drive->permissions->create($found_file->id, $permission);
          } catch (Exception $k) {
            die("{\"status\":0,\"content\":\"File is not public\"}");
          }
        }
        $old_id = $found_file->id;
        $file_key = substr(str_replace("+", "_", str_replace("/", "_", str_replace("=", "-", base64_encode(md5($old_id, true))))), 0, -2);
        $key_check = $db->query("SELECT * FROM files WHERE prime_key='$file_key'");
        $do_insert = true;
        if (mysqli_num_rows($key_check) > 0) {
          $do_insert = false;
        }
        $size = strval($found_file->size);
        $owner = $dump->id;
        $hash = $found_file->md5Checksum;
        $listed = "0";
        $name = $db->real_escape_string($found_file->name);
        if ($do_insert) {
          if ($size == "") {
            $size = "0";
          }
          if (is_null($hash)) {
            $hash = "";
          }
          $db->query("INSERT INTO files (prime_key, owner, hash, name, size, listed, active) VALUES ('$file_key', '$owner', '$hash', '$name', $size, $listed, 1)") or die("{\"status\":0,\"content\":\"Failed to add file to database\"}");
        }
        $db->query("DELETE FROM mirrors WHERE owner='$owner' AND parent='$file_key'");
        $db->query("INSERT INTO mirrors (owner, parent, id) VALUES ('$owner', '$file_key', '$old_id')") or die("{\"status\":0,\"content\":\"Failed to mirror file to database\"}");
        die("{\"status\":1,\"content\":\"$file_key\"}");
      } catch (Exception $f) {
        die("{\"status\":0,\"content\":\"Invalid file ID\"}");
      }
    } else {
      die("{\"status\":0,\"content\":\"Invalid file ID\"}");
    }
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
