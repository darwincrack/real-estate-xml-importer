<?php 
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

      $response = wp_remote_get('http://xml.tmgrupoinmobiliario.com/xmlkyero.php', ['timeout' => 60]);
      
      print_r( $response);
      