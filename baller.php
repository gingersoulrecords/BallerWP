<?php
/*
Plugin Name: BallerWP
*/

add_action('admin_menu', 'ballerwp_menu');

function ballerwp_menu()
{
    add_options_page('BallerWP Settings', 'BallerWP', 'manage_options', 'ballerwp', 'ballerwp_options');
}

function ballerwp_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $scripts = $_POST['scripts'];
        $dependency = $_POST['dependency'];
        $defer = isset($_POST['defer']) ? '1' : '0';
        update_option('ballerwp_scripts', $scripts);
        update_option('ballerwp_dependency', $dependency);
        update_option('ballerwp_defer', $defer);
        $urls = explode("\n", str_replace("\r", "", $scripts));
        $ball = '';
        foreach ($urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                $content = file_get_contents($url);
                if ($content !== false) {
                    $ball .= $content;
                }
            }
        }

        // Save the 'balled' JavaScript to a file
        $ball_file = plugin_dir_path(__FILE__) . 'ball.js';
        file_put_contents($ball_file, $ball);
    } else {
        $scripts = get_option('ballerwp_scripts', '');
        $dependency = get_option('ballerwp_dependency', '');
        $defer = get_option('ballerwp_defer', '0');
    }

    echo '<div class="wrap">';
    echo '<form method="post">';
    echo '<textarea name="scripts" rows="10" cols="50">' . esc_textarea($scripts) . '</textarea>';
    echo '<input type="text" name="dependency" value="' . esc_attr($dependency) . '" placeholder="Dependency handle" />';
    echo '<label><input type="checkbox" name="defer" value="1"' . checked('1', $defer, false) . ' /> Defer script</label>';
    echo '<input type="submit" value="Submit" />';
    echo '</form>';
    echo '</div>';
}

function ballerwp_enqueue_scripts()
{
    $ball_file = plugin_dir_url(__FILE__) . 'ball.js';
    $dependency = get_option('ballerwp_dependency', '');
    wp_enqueue_script('ballerwp_ball', $ball_file, array($dependency), false, true);
}
add_action('wp_enqueue_scripts', 'ballerwp_enqueue_scripts');

function ballerwp_add_defer_attribute($tag, $handle)
{
    // If the handle isn't 'ballerwp_ball', return the tag unmodified
    if ('ballerwp_ball' !== $handle) {
        return $tag;
    }

    // If the 'defer' option is not set, return the tag unmodified
    if (get_option('ballerwp_defer', '0') !== '1') {
        return $tag;
    }

    // If the 'defer' attribute is not present, add it
    if (false === strpos($tag, ' defer ')) {
        $tag = str_replace(' src', ' defer src', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'ballerwp_add_defer_attribute', 10, 2);
