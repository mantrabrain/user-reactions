<?php
/*
* Plugin Name: User Reactions
* Plugin URI: https://wordpress.org/plugins/user-reactions/
* Description: A simple plugin that helps you integrate reaction buttons into your WordPress site look like Facebook.
* Author: Mantrabrain
* Author URI: https://mantrabrain.com/
* Version: 1.0.0
* Text Domain: reactions
*/

// exit if accessed directly
if (!defined('ABSPATH'))
    exit;

define('USER_REACTIONS_URL', plugins_url('', __FILE__));
define('USER_REACTIONS_PATH', plugin_dir_path(__FILE__));


include_once USER_REACTIONS_PATH . 'includes/class-user-reactions.php';
/**
 * Get instance of main class.
 *
 * @return object Instance
 */

new User_Reactions();