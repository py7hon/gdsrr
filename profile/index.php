<?php
  include("../google-api-php-client/vendor/autoload.php");
  include("../func.php");
  session_start();
  $has_session = true;
  if (!(isset($_SESSION["token"]))) {
    $has_session = false;
  } else {
    try {
      $ticket = $client->verifyIdToken($_SESSION["token"]["id_token"]);
      if ($ticket) {
        $client->setAccessToken($_SESSION["token"]);
        $oauth = new Google_Service_Oauth2($client);
        $dump = $oauth->userinfo->get();
      } else {
        $has_session = false;
      }
    } catch (Exception $e) {
      try {
        if ($client->isAccessTokenExpired()) {
          $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
          $_SESSION["token"] = $client->getAccessToken();
        }
      } catch (Exception $f) {
        $has_session = false;
      }
    }
  }
  if (!($has_session)) {
    header("Location: ".$host_name);
  } else {
    $user_id = $dump->id;
    $result = $db->query("SELECT * FROM users WHERE prime_key='$user_id'");
    $row = $result->fetch_assoc();
    $user_info = array();
    $user_info["role"] = $permissions[intval($row["permission"])];
    $user_info["name"] = $row["name"];
    $user_info["id"] = $user_id;
    $user_info["email"] = $row["email"];
    $user_info["alias"] = $row["alias"];
    $user_info["private"] = "";
    if ($row["show_private"] == 1) {
      $user_info["private"] = " checked=\"checked\"";
    }
  }
?>
<html>
  <head>
    <title>Profile - <?php echo $site_title; ?></title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="../assets/css/materialize.css?<?php if ($reload_assets) { echo time(); } ?>"  media="screen,projection">
    <link type="text/css" rel="stylesheet" href="../assets/css/main.css?<?php if ($reload_assets) { echo time(); } ?>">
    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://use.fontawesome.com/4857764df8.js"></script>
    <script type="text/javascript" src="../assets/js/materialize.js?<?php if ($reload_assets) { echo time(); } ?>"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body class="grey darken-4">
    <script>
      $(function() {
        $('.button-collapse').sideNav();
        $("#alias").val("<?php echo $user_info["alias"]; ?>");
    Materialize.updateTextFields();
      });
      $(window).load(function() {
        setTimeout(function() {
          $('.preloader').fadeOut(1000, 'swing', function(){});
        }, 500);
      });
      $(document).ready(function() {
        Materialize.updateTextFields();
      });
      function update() {
        var show_private = $("#show-private").is(":checked") ? "1" : "0";
        $.ajax({
          type: "GET",
          url: "../api/update_profile.php",
          data: "alias=" + escape($("#alias").val()) + "&show_private=" + show_private,
          success: function(data) {
            var obj = JSON.parse(data);
            if (obj.status == 1) {
              Materialize.toast("Successfully updated profile!", 10000);
            } else {
              Materialize.toast(obj.content, 10000);
            }
          }
        });
      }
    </script>
    <div class="preloader"></div>
    <nav>
      <div class="nav-wrapper">
<?php
  $raw_sites = file_get_contents('../assets/json/navbar.json');
  $decoded_sites = json_decode($raw_sites);
  echo "        ".$decoded_sites->logo."\n";
  $normal_links = array();
  $mobile_links = array();
  foreach ($decoded_sites->navbar as $single_site) {
    if ($single_site->hide) {
      continue;
    }
    $url = $single_site->link;
    $fa = $single_site->fa;
    $name = $single_site->name;
    $normal_active = "";
    $mobile_active = "";
    if ($single_site->cwd == getcwd()) {
      $url = "#!";
      $normal_active = " active";
      $mobile_active = " class=\"active\"";
    }
    array_push($normal_links, "<li class=\"waves-effect waves-lighten$normal_active\"><a href=\"$url\"><i class=\"fa fa-fw fa-$fa\"></i>&nbsp; $name</a></li>");
    array_push($mobile_links, "<li$mobile_active><a href=\"$url\">$name</a></li>");
  }
?>
        <a href="#" data-activates="mobile-nav" class="button-collapse right"><i class="material-icons">menu</i></a>
        <ul class="right hide-on-med-and-down">
<?php
  if ($has_session) {
    $normal_padding = "          ";
    foreach ($normal_links as $normal_link) {
      echo $normal_padding.$normal_link."\n";
    }
?>
          <li class="waves-effect waves-lighten"><a href="<?php echo $host_name."session.php?logout=1" ?>"><i class="fa fa-fw fa-sign-out"></i>&nbsp; Logout (<?php echo $dump->name; ?>)</a></li>
<?php
  } else {
?>
          <li class="waves-effect waves-lighten"><a href="<?php echo $host_name."session.php?login=1" ?>"><i class="fa fa-fw fa-sign-in"></i>&nbsp; Login</a></li>
<?php
  }
?>
        </ul>
        <ul class="side-nav" id="mobile-nav">
<?php
  if ($has_session) {
    $mobile_padding = "          ";
    foreach ($mobile_links as $mobile_link) {
      echo $mobile_padding.$mobile_link."\n";
    }
?>
          <li><a href="<?php echo $host_name."session.php?logout=1" ?>">Logout (<?php echo $dump->name; ?>)</a></li>
<?php
  } else {
?>
          <li><a href="<?php echo $host_name."session.php?login=1" ?>">Login</a></li>
<?php
  }
?>
        </ul>
      </div>
    </nav>
    <main class="white-text">
      <div class="container">
        <h4>Profile</h4>
        <form class="col s12">
          <div class="row">
            <div class="input-field col s6">
              <input disabled placeholder="<?php echo $user_info["name"]; ?>" id="name" type="text">
              <label for="name" class="white-text">Full Name</label>
            </div>
            <div class="input-field col s6">
              <input disabled placeholder="<?php echo $user_info["role"]; ?>" id="role" type="text">
              <label for="role" class="white-text">Role</label>
            </div>
          </div>
          <div class="row">
            <div class="input-field col s12">
              <input disabled placeholder="<?php echo $user_info["email"]; ?>" id="email" type="text">
              <label for="email" class="white-text">Email</label>
            </div>
          </div>
          <div class="row">
            <div class="input-field col s12">
              <input placeholder="<?php echo $user_info["alias"]; ?>" id="alias" type="text">
              <label for="alias" class="white-text">Alias</label>
            </div>
            <div class="row">
              <div class="input-field col s6">
                <input type="checkbox" class="filled-in" id="show-private"<?php echo $user_info["private"]; ?>>
                <label for="show-private">Display Private Files</label>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="input-field col s12">
              <a class="light-blue darken-2 waves-effect waves-light btn-large" style="width: 100%;" onclick="update()">Update Profile</a>
            </div>
          </div>
        </form>
      </div>
    </main>
    <footer class="page-footer">
<?php
  $raw_footer = file_get_contents('../assets/json/footer.json');
  $decoded_footer = json_decode($raw_footer);
  $footer_links = array();
  $footer_title = $decoded_footer->title;
  $footer_desc = $decoded_footer->description;
  $footer_copyright = $decoded_footer->copyright;
  $footer_made = $decoded_footer->madewith;
  foreach ($decoded_footer->links as $single_link) {
    if ($single_link->hide) {
      continue;
    }
    $link_name = $single_link->name;
    $link_fa = $single_link->fa;
    $link_url = $single_link->link;
    array_push($footer_links, "<li><a class=\"grey-text text-lighten-3\" href=\"$link_url\"><i class=\"fa fa-fw fa-$link_fa\"></i>&nbsp; $link_name</a></li>");
  }
?>
      <div class="container">
        <div class="row">
          <div class="col l6 s12">
            <h5 class="white-text"><?php echo $footer_title; ?></h5>
            <p class="grey-text text-lighten-4"><?php echo $footer_desc; ?></p>
          </div>
        <div class="col l4 offset-l2 s12">
          <h5 class="white-text">Links</h5>
            <ul>
<?php
  $footer_padding = "              ";
  foreach ($footer_links as $single_footer_link) {
    echo $footer_padding.$single_footer_link."\n";
  }
?>
            </ul>
          </div>
        </div>
      </div>
      <div class="footer-copyright">
        <div class="container row">
          <div class="col l6 s12">
            <span>Copyright &copy; <?php echo date("Y"); ?> <?php echo $footer_copyright; ?></span>
          </div>
          <div class="col l4 offset-l2 s12">
            <span><?php echo $footer_made; ?></a></span>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>
