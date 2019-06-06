<?php
/**
 * User_Reactions install setup
 *
 * @package User_Reactions
 * @since   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Main User_Reactions_Install Class.
 *
 * @class User_Reactions
 */
final class User_Reactions_Install
{

    public static function install()
    {
        $user_reactions_version = get_option('user_reactions_plugin_version');

        if (empty($user_reactions_version)) {
            self::install_content_and_options();
        } else {
            update_option('user_reactions_plugin_version', USER_REACTIONS_VERSION);
        }

    }

    private static function install_content_and_options()
    {
        $pages = array();

        foreach ($pages as $page) {

            $page_id = wp_insert_post($page);


        }

        $data = array(
            'enable' => 'on',
            'enable_count' => 'on',
            'anonymous_can_vote' => 'on',
            'position' => array(
                'above' => 'off',
                'below' => 'on',
            ),
            'pages' => array(
                'home' => 'off',
                'archive' => 'off',
                'posts' => 'on',
                'pages' => 'off'
            )
        );

        update_option('user_reactions', $data);

        $options = array();

        foreach ($options as $option_key => $option_value) {

            update_option($option_key, $option_value);
        }

    }

    public static function init()
    {

    }


}

User_Reactions_Install::init();