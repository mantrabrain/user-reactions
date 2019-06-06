<?php

class User_Reactions
{

    /**
     * User_Reactions version.
     *
     * @var string
     */
    public $version = USER_REACTIONS_VERSION;

    /**
     * The single instance of the class.
     *
     * @var User_Reactions
     * @since 1.0.0
     */
    protected static $_instance = null;


    /**
     * Main User_Reactions Instance.
     *
     * Ensures only one instance of User_Reactions is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return User_Reactions - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private static $timeversion = 120004042016;

    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        do_action('user_reactions_loaded');
    }

    public function includes()
    {
        include_once USER_REACTIONS_ABSPATH . 'includes/class-user-reactions-install.php';

    }

    /**
     * Define User_Reactions Constants.
     */
    private function define_constants()
    {

        $this->define('USER_REACTIONS_ABSPATH', dirname(USER_REACTIONS_FILE) . '/');
        $this->define('USER_REACTIONS_BASENAME', plugin_basename(USER_REACTIONS_FILE));
    }

    /**
     * Define constant if not already set.
     *
     * @param string $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Class Construct
     */
    public function init_hooks()
    {
        // register shortcode
        add_shortcode('user_reactions', array($this, 'shortcode_reactions'));
        register_activation_hook(USER_REACTIONS_FILE, array('User_Reactions_Install', 'install'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
        add_action('wp_head', array($this, 'head'));
        add_action('admin_menu', array($this, 'settings_page'));
        add_action('admin_init', array($this, 'save'));

        add_action('plugins_loaded', array($this, 'load_translate'));

        // ajax action
        add_action('wp_ajax_user_reaction_save_action', array($this, 'ajax'));
        add_action('wp_ajax_nopriv_user_reaction_save_action', array($this, 'ajax'));
    }

    /**
     * Load translate
     */
    public function load_translate()
    {
        // Load translate text domain
        load_plugin_textdomain('user-reactions', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * WordPress header hook
     */
    public function head()
    {
        // add reactions to the content
        add_filter('the_content', array($this, 'replace_content'));
        // add reactions to the excerpt
        add_filter('get_the_excerpt', array($this, 'replace_content_excerpt'));
    }

    /**
     * Add reactions to post/page content
     *
     * @param string $content
     * @return string
     */
    public function replace_content($content)
    {
        if ($this->is_enable()) {
            if ($this->enable_in_single_post() || $this->enable_in_archive() || $this->enable_in_pages() || $this->enable_in_home()) {
                $fullcontent = '';
                if ($this->position('above')) {
                    $fullcontent .= $this->layout();
                }

                $fullcontent .= $content;

                if ($this->position('below')) {
                    $fullcontent .= $this->layout();
                }

                return $fullcontent;
            }
        }

        return $content;
    }

    /**
     * Add reactions to post/page excerpt
     *
     * @param string $content
     * @param string
     */
    public function replace_content_excerpt($content)
    {
        if (has_excerpt() && !is_single()) {
            $content = $this->replace_content($content);
        }

        return $content;
    }

    /**
     * Print Reactions layout
     *
     * @param int $post_id (default: false)
     */
    public function layout($post_id = false, $button = true, $count = true)
    {
        if ($post_id) {
            $post_id = get_the_ID();
        }

        $text = $this->get_reactions_text(get_current_user_id(), get_the_ID());
        $is_liked = $this->is_liked(get_current_user_id(), get_the_ID());
        $type = $is_liked ? 'unvote' : 'vote';
        ob_start();
        ?>
        <div class="user-reactions user-reactions-post-<?php the_ID() ?>" data-type="<?php echo esc_attr($type) ?>"
             data-nonce="<?php echo wp_create_nonce('_user_reaction_action') ?>" data-post="<?php the_ID() ?>">
            <?php if ($button) : ?>
                <?php if ((!$this->anonymous_can_vote() && !is_user_logged_in()) || is_user_logged_in()) : ?>
                    <div class="user-reactions-button">
                        <span class="user-reactions-main-button <?php echo esc_attr(strtolower($is_liked)) ?>"><?php echo esc_html($text) ?></span>
                        <div class="user-reactions-box">
                            <span class="user-reaction user-reaction-like"><strong><?php esc_attr_e('Like', 'user-reactions') ?></strong></span>
                            <span class="user-reaction user-reaction-love"><strong><?php esc_attr_e('Love', 'user-reactions') ?></strong></span>
                            <span class="user-reaction user-reaction-haha"><strong><?php esc_attr_e('Haha', 'user-reactions') ?></strong></span>
                            <span class="user-reaction user-reaction-wow"><strong><?php esc_attr_e('Wow', 'user-reactions') ?></strong></span>
                            <span class="user-reaction user-reaction-sad"><strong><?php esc_attr_e('Sad', 'user-reactions') ?></strong></span>
                            <span class="user-reaction user-reaction-angry"><strong><?php esc_attr_e('Angry', 'user-reactions') ?></strong></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (($this->enable_count() && $this->is_enable()) || (!$this->is_enable() && $count)) : ?>
                <div class="user-reactions-count">
                    <?php echo $this->count_like_layout($post_id); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

    /**
     * Print count vote reactions
     *
     * @param int $post_id
     */
    public function count_like_layout($post_id = false)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        $reactions = array('like', 'love', 'haha', 'wow', 'sad', 'angry');
        $total = get_post_meta($post_id, 'user_reaction_total_liked', true);
        $output = '';
        foreach ($reactions as $reaction) {
            $count = get_post_meta($post_id, 'user_reaction_' . $reaction);

            if (!empty($count)) {
                $output .= '<span class="user-reaction-count user-reaction-count-' . esc_attr($reaction) . '"><strong>' . esc_attr(count($count)) . '</strong></span>';
            }
        }

        return $output;
    }

    /**
     * Enqueue plugin's style/script
     */
    public function enqueue_script()
    {
        wp_enqueue_style('user-reaction-style', USER_REACTIONS_PLUGIN_URI . '/assets/css/style.css', array(), self::$timeversion);
        wp_enqueue_script('user-reaction-script', USER_REACTIONS_PLUGIN_URI . '/assets/js/script.js', array('jquery'), self::$timeversion);
        $localize = array(
            'ajax' => admin_url('admin-ajax.php'),
        );

        wp_localize_script('user-reaction-script', 'user_reaction', $localize);
    }

    /**
     * Ajax vote
     */
    public function ajax()
    {
        check_admin_referer('_user_reaction_action', 'nonce');

        $post_id = intval($_POST['post']);
        $type = sanitize_title($_POST['type']);
        $vote_type = isset($_POST['vote_type']) ? sanitize_title($_POST['vote_type']) : 'vote';
        $voted = isset($_POST['voted']) ? sanitize_title($_POST['voted']) : 'no';

        if (empty($post_id)) {
            wp_send_json_error(array('message' => __('Missing post.', 'user-reactions')));
        }

        if (empty($type)) {
            wp_send_json_error(array('message' => __('Missing type.', 'user-reactions')));
        }

        if ('yes' == $voted && !is_user_logged_in()) return;
        // delete old reactions
        $is_liked = $this->is_liked(get_current_user_id(), $post_id);
        if ($is_liked) {
            if (!is_user_logged_in()) return;
            delete_post_meta($post_id, $is_liked, get_current_user_id());
            if (isset($vote_type) && 'unvote' == $vote_type) {
                $total = get_post_meta($post_id, 'user_reaction_total_liked', true) ? get_post_meta($post_id, 'user_reaction_total_liked', true) : 0;
                if ($total >= 0) {
                    $total = (int)$total - 1;
                    update_post_meta($post_id, 'user_reaction_total_liked', $total);
                }

                $content = $this->count_like_layout($post_id);

                wp_send_json_success(array('html' => $content, 'type' => 'unvoted'));
                exit();
            }
        }

        if (!$is_liked) {
            $total = get_post_meta($post_id, 'user_reaction_total_liked', true) ? get_post_meta($post_id, 'user_reaction_total_liked', true) : 0;
            $total = (int)$total + 1;

            update_post_meta($post_id, 'user_reaction_total_liked', $total);
        }

        $count = get_post_meta($post_id, 'user_reaction_' . $type);

        $user_id = get_current_user_id();
        if (!is_user_logged_in() && !$this->anonymous_can_vote()) {
            $user_id = 'anonymous';
        }

        // update to database
        add_post_meta($post_id, 'user_reaction_' . $type, $user_id);

        $content = $this->count_like_layout($post_id);

        wp_send_json_success(array('html' => $content, 'type' => 'voted'));
        exit();
    }

    /**
     * Check user is liked
     *
     * @param int $user_id
     * @param int $post_id (default: false)
     */
    public function is_liked($user_id, $post_id = false)
    {
        global $wpdb;

        $query = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key IN ( 'user_reaction_love', 'user_reaction_like', 'user_reaction_haha', 'user_reaction_wow', 'user_reaction_sad', 'user_reaction_angry' ) AND meta_value = '{$user_id}'";

        if ($post_id) {
            $query .= " AND post_id = {$post_id}";
        }

        $result = $wpdb->get_var($query);

        return !empty($result) ? $result : false;
    }

    /**
     * Get reactions text
     *
     * @param int $user_id
     * @param int $post_id (default: false)
     */
    public function get_reactions_text($user_id, $post_id = false)
    {
        $is_liked = $this->is_liked($user_id, $post_id);
        $default = __('Like', 'user-reactions');
        if (!$is_liked) {
            return $default;
        } else {
            if (strpos($is_liked, 'like')) {
                return $default;
            } elseif (strpos($is_liked, 'haha')) {
                return __('Haha', 'user-reactions');
            } elseif (strpos($is_liked, 'love')) {
                return __('Love', 'user-reactions');
            } elseif (strpos($is_liked, 'wow')) {
                return __('Wow', 'user-reactions');
            } elseif (strpos($is_liked, 'angry')) {
                return __('Angry', 'user-reactions');
            } elseif (strpos($is_liked, 'sad')) {
                return __('Sad', 'user-reactions');
            }
        }
    }

    /**
     * Reactions short code
     *
     * @param array $atts
     */
    public function shortcode_reactions($atts = array())
    {
        extract(shortcode_atts(array(
            'id' => get_the_ID(),
            'button' => 'true',
            'count' => 'true',
        ), $atts, 'user_reactions'));

        $button = 'true' == $button ? true : false;
        $count = 'true' == $count ? true : false;

        echo $this->layout($id, $button, $count);
    }

    /**
     * Register settings page
     */
    public function settings_page()
    {
        add_submenu_page('options-general.php', __('Reactions Settings', 'user-reactions'), __('User Reactions', 'user-reactions'), 'manage_options', 'user_reaction_settings', array($this, 'setting_layout'));
    }

    /**
     * Print setting layout
     */
    public function setting_layout()
    {
        $options = get_option('user_reactions', array());
        $above = isset($options['position']['above']) ? $options['position']['above'] : false;
        $below = isset($options['position']['below']) ? $options['position']['below'] : false;
        $archive = isset($options['pages']['archive']) ? $options['pages']['archive'] : false;
        $posts = isset($options['pages']['posts']) ? $options['pages']['posts'] : false;
        $pages = isset($options['pages']['pages']) ? $options['pages']['pages'] : false;
        $home = isset($options['pages']['home']) ? $options['pages']['home'] : false;
        ?>
        <div class="wrap">
            <h2><?php echo get_admin_page_title(); ?></h2>
            <?php esc_attr_e('To display the reactions button on your blog posts, you can use one of two ways below:', 'user-reactions'); ?>
            <form method="post">
                <h3><?php esc_attr_e('1. Automatically display on the content of each post.', 'user-reactions') ?></h3>
                <table class="form-table">
                    <tr>
                        <td colspan="2">
                            <p><label>
                                    <input type="checkbox"
                                           name="user_reactions[enable]" <?php checked($this->is_enable(), true) ?>><span
                                            class="description"><?php esc_attr_e('Show reactions button.', 'user-reactions') ?></span>
                                </label></p>
                            <p><label><input type="checkbox"
                                             name="user_reactions[enable_count]" <?php checked($this->enable_count(), true) ?>><span
                                            class="description"><?php esc_attr_e('Show reactions count.', 'user-reactions') ?></span></label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox"
                                           name="user_reactions[anonymous_can_vote]" <?php checked($this->anonymous_can_vote(), true) ?>><span
                                            class="description"><?php esc_attr_e('Users must be registered and logged in to add reaction.', 'user-reactions') ?></span>
                                </label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_attr_e('Positions', 'user-reactions'); ?></th>
                        <td>
                            <p><label><input type="checkbox"
                                             name="user_reactions[position][above]" <?php checked(esc_attr($above), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show the reactions button above the post content.', 'user-reactions') ?></span></label>
                            </p>
                            <p><label><input type="checkbox"
                                             name="user_reactions[position][below]" <?php checked(esc_attr($below), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show the reactions button below the post content.', 'user-reactions') ?></span></label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_attr_e('Pages', 'user-reactions') ?></th>
                        <td>
                            <p><label><input type="checkbox"
                                             name="user_reactions[pages][home]"<?php checked(esc_attr($home), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show on Homepage', 'user-reactions') ?></span></label>
                            </p>
                            <p><label><input type="checkbox"
                                             name="user_reactions[pages][archive]" <?php checked(esc_attr($archive), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show on Archive pages', 'user-reactions') ?></span></label>
                            </p>
                            <p><label><input type="checkbox"
                                             name="user_reactions[pages][posts]"<?php checked(esc_attr($posts), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show on all Posts', 'user-reactions') ?></span></label>
                            </p>
                            <p><label><input type="checkbox"
                                             name="user_reactions[pages][pages]"<?php checked(esc_attr($pages), 'on') ?>><span
                                            class="description"><?php esc_attr_e('Show on all Pages', 'user-reactions') ?></span></label>
                            </p>
                        </td>
                    </tr>
                </table>
                <hr>
                <h3><?php esc_attr_e('2. Manually insert into your theme.', 'user-reactions') ?></h3>
                <p>
                <p><?php _e('If you DO NOT want the reactions to appear in every post/page, DO NOT use the code above. Just type in <code>[user-reactions]</code> into the selected post/page and it will embed reactions into that post/page only.', 'user-reactions'); ?></p>
                <p><?php _e('If you to use reactions button for specific post/page you can use this short code <code>[user-reactions id="1"]</code>, where 1 is the ID of the post/page.', 'user-reactions'); ?></p>
                <p><?php _e('If you want to show reactions button you can use <code>[user-reactions count=false button=true]</code>.', 'user-reactions') ?></p>
                <p><?php _e('If you want to show reactions count you can use <code>[user-reactions count=true button=false]</code>.', 'user-reactions') ?></p>
                </p>
                <button type="submit"
                        class="button button-primary"><?php esc_attr_e('Save changes', 'user-reactions') ?></button>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings action
     */
    public function save()
    {
        if (isset($_POST['user_reactions'])) {
            $valid_post_data = $this->get_valid_post($_POST['user_reactions']);
            if (count($valid_post_data) > 0) {
                update_option('user_reactions', $valid_post_data);
            }
        }
    }


    /*
     * post validation
     */
    public function get_valid_post($post_data)
    {
        $valid_post_data = array();

        if (isset($post_data['enable'])) {
            $valid_post_data['enable'] = sanitize_text_field($post_data['enable']);
        }

        if (isset($post_data['enable_count'])) {
            $valid_post_data['enable_count'] = sanitize_text_field($post_data['enable_count']);
        }

        if (isset($post_data['anonymous_can_vote'])) {
            $valid_post_data['anonymous_can_vote'] = sanitize_text_field($post_data['anonymous_can_vote']);
        }

        if (isset($post_data['position'])) {
            if (isset($post_data['position']['above'])) {
                $valid_post_data['position']['above'] = sanitize_text_field($post_data['position']['above']);

            }

            if (isset($post_data['position']['below'])) {
                $valid_post_data['position']['below'] = sanitize_text_field($post_data['position']['below']);

            }

        }

        if (isset($post_data['pages'])) {
            if (isset($post_data['pages']['home'])) {
                $valid_post_data['pages']['home'] = sanitize_text_field($post_data['pages']['home']);

            }

            if (isset($post_data['pages']['archive'])) {
                $valid_post_data['pages']['archive'] = sanitize_text_field($post_data['pages']['archive']);

            }

            if (isset($post_data['pages']['posts'])) {
                $valid_post_data['pages']['posts'] = sanitize_text_field($post_data['pages']['posts']);

            }
            if (isset($post_data['pages']['pages'])) {
                $valid_post_data['pages']['pages'] = sanitize_text_field($post_data['pages']['pages']);

            }

        }

        return $valid_post_data;
    }

    /**
     * Check is enable reactions buttons
     *
     * @return bool
     */
    public function is_enable()
    {
        $options = get_option('user_reactions', array());

        return isset($options['enable']) && 'on' == $options['enable'] ? true : false;
    }

    /**
     * Check is enable reactions count
     *
     * @return bool
     */
    public function enable_count()
    {
        $options = get_option('user_reactions', array());

        return isset($options['enable_count']) && 'on' == $options['enable_count'] ? true : false;
    }

    /**
     * Check anonymous can vote
     *
     * @return bool
     */
    public function anonymous_can_vote()
    {
        $options = get_option('user_reactions', array());

        return isset($options['anonymous_can_vote']) && 'on' == $options['anonymous_can_vote'] ? true : false;
    }

    /**
     * Check reactions enable in posts
     *
     * @return bool
     */
    public function enable_in_single_post()
    {
        $options = get_option('user_reactions', array());

        if ('posts' == $this->template_type() && isset($options['pages']['posts']) && 'on' == $options['pages']['posts']) {
            return true;
        }

        return false;
    }

    /**
     * Check reactions enable in pages
     *
     * @return bool
     */
    public function enable_in_pages()
    {
        $options = get_option('user_reactions', array());

        if ('pages' == $this->template_type() && isset($options['pages']['pages']) && 'on' == $options['pages']['pages']) {
            return true;
        }

        return false;
    }

    /**
     * Check reactions enable in archive pages
     *
     * @return bool
     */
    public function enable_in_archive()
    {
        $options = get_option('user_reactions', array());

        if ('archive' == $this->template_type() && isset($options['pages']['archive']) && 'on' == $options['pages']['archive']) {
            return true;
        }

        return false;
    }

    /**
     * Check reactions enable in home or front page
     *
     * @return bool
     */
    public function enable_in_home()
    {
        $options = get_option('user_reactions', array());

        if ('home' == $this->template_type() && isset($options['pages']['home']) && 'on' == $options['pages']['home']) {
            return true;
        }

        return false;
    }

    /**
     * Get current page template type
     *
     * @return string|bool
     */
    public function template_type()
    {
        global $post;

        if (is_home() || is_front_page()) {
            $type = 'home';
        } elseif (is_archive()) {
            $type = 'archive';
        } elseif (is_object($post) && is_page($post->ID)) {
            $type = 'pages';
        } elseif (is_single()) {
            $type = 'posts';
        } else {
            $type = false;
        }

        return $type;
    }

    /**
     * Get positions had enable
     *
     * @return bool
     */
    public function position($type)
    {
        $options = get_option('user_reactions', array());

        return isset($options['position'][$type]) && 'on' == $options['position'][$type] ? true : false;
    }
}

/**
 * Print reactions
 *
 * @param int $post_id (default: false)
 * @param bool $button (default: true)
 * @param bool $count (default: true)
 */
function user_reactions($post_id = false, $button = true, $count = true)
{
    $reactions = new User_Reactions();
    echo $reactions->layout($post_id, $button, $count);
}


