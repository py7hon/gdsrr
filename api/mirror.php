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
      $search_id = $db->real_escape_string($_GET["id"]);
      $file_check = $db->query("SELECT * FROM files WHERE prime_key='$search_id'");
      if (mysqli_num_rows($file_check) == 0) {
        die("{\"status\":0,\"content\":\"Invalid file ID\"}");
      } else {
        $mirror_check = $db->query("SELECT * FROM mirrors WHERE parent='$search_id' AND failures<$fail_max ORDER BY prime_key DESC");
        if (mysqli_num_rows($mirror_check) == 0) {
          die("{\"status\":0,\"content\":\"No mirrors available\"}");
        } else {
          $list_options = array();
          $list_options["fields"] = "id,md5Checksum,name,permissions(emailAddress,id,role),size,trashed,webContentLink,webViewLink";
          $mirrored = false;
          $found_id = "";
          while ($row = $mirror_check->fetch_assoc()) {
            $drop_row = false;
            try {
              $found_file = $drive->files->get($row["id"], $list_options);
              if ($found_file->trashed) {
                $drop_row = true;
              } else {
                $is_public = true; // Was going to check if it was public or not
                if (!($is_public)) {
                  $drop_row = true;
                } else {
                  $hash = $found_file->md5Checksum;
                  $parent_row = $file_check->fetch_assoc();
                  if ($hash != $parent_row["hash"]) {
                    $drop_row = true;
                  } else {
                    $name = $parent_row["name"];
                    $copied = new Google_Service_Drive_DriveFile();
                    $copied->setName($file_prefix.$name);
                    $found_id = $found_file->id;
                    $new_file = $drive->files->copy($found_file->id, $copied);
                    $permission = new Google_Service_Drive_Permission();
                    $permission->setRole("reader");
                    $permission->setType("anyone");
                    $drive->permissions->create($new_file->id, $permission);
                    $new_id = $new_file->id;
                    $owner = $dump->id;
                    $db->query("DELETE FROM mirrors WHERE owner='$owner' AND parent='$search_id'");
                    $db->query("INSERT INTO mirrors (owner, parent, id, failures) VALUES ('$owner', '$search_id', '$new_id', 0)");
                    $mirrored = true;
                  }
                }
              }
            } catch (Exception $f) {
              $drop_row = true;
            }
            if ($drop_row) {
              $prime_key = strval($row["prime_key"]);
              $fail_count = strval($row["failures"] + 1);
              $db->query("UPDATE mirrors SET failures=$fail_count WHERE prime_key='$prime_key'");
            }
            if ($mirrored) {
              break;
            }
          }
          if ($mirrored) {
            die("{\"status\":1,\"content\":\"https://drive.google.com/open?id=$found_id\"}");
          } else {
            die("{\"status\":0,\"content\":\"Mirror failed\"}");
          }
        }
      }
    } else {
      die("{\"status\":0,\"content\":\"Invalid file ID\"}");
    }
  } catch (Exception $e) {
    die("{\"status\":0,\"content\":\"Failed to start session\"}");
  }
?>
