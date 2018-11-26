<?php
  include("google-api-php-client/vendor/autoload.php");
  include("func.php");
  session_start();
  $home = true;
  if (isset($_GET['code'])) {
    try {
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      $client->setAccessToken($token);
      $_SESSION["token"] = $token;
      $oauth = new Google_Service_Oauth2($client);
      $dump = $oauth->userinfo->get();
      $user_id = $db->real_escape_string($dump->id);
      $email = $db->real_escape_string($dump->email);
      $name = $db->real_escape_string($dump->name);
      $user_check = $db->query("SELECT * FROM users WHERE prime_key='$user_id'");
      $time_now = strval(time());
      $ip_hash = hash("sha256", $_SERVER['REMOTE_ADDR']); // I don't even want to know your IP.
      if (mysqli_num_rows($user_check) == 0) {
        $db->query("INSERT INTO users (prime_key, name, email, permission, joined, updated, join_ip, last_ip, alias) VALUES ('$user_id', '$name', '$email', 0, $time_now, $time_now, '$ip_hash', '$ip_hash', 'Anonymous')");
      } else {
        $db->query("UPDATE users SET name='$name', email='$email', updated=$time_now, last_ip='$ip_hash' WHERE prime_key='$user_id'");
      }
    } catch (Exception $e) { }
    if ($debug_mode) {
      var_dump($token);
      $home = false;
    }
  } else if (isset($_GET['login'])) {
    $auth_url = $client->createAuthUrl();
    $home = false;
    header("Location: ".filter_var($auth_url, FILTER_SANITIZE_URL));
  } else if (isset($_GET['logout'])) {
    session_destroy();
  }
  if ($home) {
    header("Location: ".$host_name);
  }
?>
