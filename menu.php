<?php
function echo_spread_add_admin_menu() {
    add_menu_page('Place API Settings', 'Place API', 'manage_options', 'echo_spread', 'echo_spread_settings_page');
}

add_action('admin_menu', 'echo_spread_add_admin_menu');

function echo_spread_settings_page() {
    require_once 'config.php';
}

function echo_spread_settings_init() {
    register_setting('echo_spread_settings', 'echo_spread_options');

    add_settings_section(
        'echo_spread_main_section',
        'API Settings',
        'echo_spread_settings_section_callback',
        'echo_spread'
    );

    add_settings_field(
        'echo_spread_token',
        'API Token',
        'echo_spread_token_render',
        'echo_spread',
        'echo_spread_main_section'
    );
}

add_action('admin_init', 'echo_spread_settings_init');

function echo_spread_token_render() {
    $options = get_option('echo_spread_options');
    ?>
    <input type='text' name='echo_spread_options[echo_spread_token]' value='<?php echo esc_attr($options['echo_spread_token'] ?? ''); ?>' style="width: 300px;">
    <?php
}


function echo_spread_settings_section_callback() {
    echo 'Введите настройки для подключения к API';
}

