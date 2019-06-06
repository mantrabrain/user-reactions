<?php
/*
* Plugin Name: User Reactions
* Plugin URI: https://wordpress.org/plugins/user-reactions/
* Description: A simple plugin that helps you integrate reaction buttons into your WordPress site look like Facebook.
* Author: Mantrabrain
* Author URI: https://mantrabrain.com/
* Version: 1.0.1
* Text Domain: user-reactions
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define USER_REACTIONS_PLUGIN_FILE.
if (!defined('USER_REACTIONS_FILE')) {
    define('USER_REACTIONS_FILE', __FILE__);
}

// Define USER_REACTIONS_VERSION.
if (!defined('USER_REACTIONS_VERSION')) {
    define('USER_REACTIONS_VERSION', '1.0.1');
}

// Define USER_REACTIONS_PLUGIN_URI.
if (!defined('USER_REACTIONS_PLUGIN_URI')) {
    define('USER_REACTIONS_PLUGIN_URI', plugins_url('', USER_REACTIONS_FILE));
}

// Define USER_REACTIONS_PLUGIN_DIR.
if (!defined('USER_REACTIONS_PLUGIN_DIR')) {
    define('USER_REACTIONS_PLUGIN_DIR', plugin_dir_path(USER_REACTIONS_FILE));
}


// Include the main User_Reactions class.
if (!class_exists('User_Reactions')) {
    include_once dirname(__FILE__) . '/includes/class-user-reactions.php';
}


/**
 * Main instance of User_Reactions.
 *
 * Returns the main instance of User_Reactions to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return User_Reactions
 */
function user_reactions_instance()
{
    return User_Reactions::instance();
}

// Global for backwards compatibility.
$GLOBALS['user-reactions'] = user_reactions_instance();
