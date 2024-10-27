<?php
defined('ABSPATH') or die('Direct script access disallowed.');

function aiccfb_enqueue_scripts() {
    wp_enqueue_script('ai-content-facebook-sdk', 'https://connect.facebook.net/en_US/sdk.js');
}

add_action('admin_enqueue_scripts', 'aiccfb_enqueue_scripts');

function aiccfb_initialize_sdk() {
    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        window.fbAsyncInit = function() {
          FB.init({
            appId      : '3727646757555408', // Your Facebook App ID
            cookie     : true,
            xfbml      : true,
            version    : 'v19.0' // Use the latest version or the version you are working with
          });

          FB.AppEvents.logPageView();

          // Check login status
          FB.getLoginStatus(function(response) {
              aiccfb_status_change_callback(response);
          });
        };

        (function(d, s, id){
           var js, fjs = d.getElementsByTagName(s)[0];
           if (d.getElementById(id)) {return;}
           js = d.createElement(s); js.id = id;
           js.src = "https://connect.facebook.net/en_US/sdk.js";
           fjs.parentNode.insertBefore(js, fjs);
         }(document, 'script', 'facebook-jssdk'));
      });

      // Callback function to handle login status
      function aiccfb_status_change_callback(response) {
        console.log('Facebook login status: ', response);
        // Handle the login status (connected, not authorized, unknown) here
      }
    </script>
    <?php
}

add_action('admin_footer', 'aiccfb_initialize_sdk');
