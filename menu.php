<?php
function echo_spread_add_admin_menu() {
    add_menu_page('Echo spread API Settings', 'Echo spread API', 'manage_options', 'echo_spread', 'echo_spread_settings_page');
}

add_action('admin_menu', 'echo_spread_add_admin_menu');

function echo_spread_settings_page() {
    require_once 'config.php';
}

function echo_spread_settings_init() {
    register_setting('echo_spread_settings', 'echo_spread_options');

    add_settings_section(
        'echo_spread_main_section',
        'Настройки',
        'echo_spread_settings_section_callback',
        'echo_spread'
    );

    add_settings_section(
        'echo_spread_second_section',
        'Распределение по категориям',
        'echo_spread_settings_section2_callback',
        'echo_spread'
    );


    add_settings_field(
        'echo_spread_token',
        'API Token',
        'echo_spread_token_render',
        'echo_spread',
        'echo_spread_main_section'
    );

    add_settings_field(
        'echo_spread_user',
        'ID юзера для публикации постов',
        'echo_spread_user_render',
        'echo_spread',
        'echo_spread_main_section'
    );

    add_settings_field(
        'echo_spread_default_category',
        'Категория по умолчанию',
        'echo_spread_default_category_render',
        'echo_spread',
        'echo_spread_main_section'
    );

    add_settings_field(
        'echo_spread_category_keys',
        '',
        'echo_spread_category_keys_render',
        'echo_spread',
        'echo_spread_second_section'
    );
}

add_action('admin_init', 'echo_spread_settings_init');

function echo_spread_token_render() {
    $options = get_option('echo_spread_options');
    echo "<input type='text' name='echo_spread_options[echo_spread_token]' value='" . esc_attr($options['echo_spread_token'] ?? '') . "' style='width: 300px;'>";
}


function echo_spread_user_render() {
    $options = get_option('echo_spread_options');
    $echo_spread_user = $options['echo_spread_user'] ?? '';

    $users = get_users(); // Получаем всех пользователей

    echo '<select name="echo_spread_options[echo_spread_user]" id="echo_spread_echo_spread_user">';
    foreach ($users as $user) {
        $selected = ($user->ID == $echo_spread_user) ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>';
}

function echo_spread_default_category_render() {
    $options = get_option('echo_spread_options');
    $echo_spread_default_category = $options['echo_spread_default_category'] ?? '';

    $categories = get_categories(array('hide_empty' => 0));

    echo '<select name="echo_spread_options[echo_spread_default_category]" id="echo_spread_echo_spread_default_category">';
    echo '<option value="">Не указана</option>';
    foreach ($categories as $category) {
        $selected = ($category->term_id == $echo_spread_default_category) ? 'selected' : '';
        echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
    }
    echo '</select>';
}

function echo_spread_settings_section_callback() {
    echo 'Введите настройки для подключения к API';
}
function echo_spread_settings_section2_callback() {
    echo 'Перечислите через запятую слова напротив категорий, по этим слова будет осуществляться определения постав в категорию';
}

function echo_spread_category_keys_render() {
    $options = get_option('echo_spread_options');
    $categories = get_categories(array('hide_empty' => 0));

    echo '<table class="form-table"><tbody>';
    echo "<tr><th>Название категории</th><th>Ключевые фразы через точку с запятой</th><th>Запрещенне фразы через точку с запятой</th></tr>";

    foreach ($categories as $category) {
        $cat_id = $category->term_id;
        $cat_name = $category->name;
        $field_name = "echo_spread_options[category_keys][$cat_id]";
        $field_name_black_list = "echo_spread_options[category_keys_black_list][$cat_id]";
        $value = $options['category_keys'][$cat_id] ?? '';
        $value_black_list = $options['category_keys_black_list'][$cat_id] ?? '';

        echo "<tr>";
        echo "<td scope='row'><label for='$field_name'>$cat_name</label></td>";
        echo "<td><textarea name='$field_name' id='$field_name' style='width: 100%; max-width: 300px;'>".esc_attr($value)."</textarea></td>";
        echo "<td><textarea name='$field_name_black_list' id='$field_name_black_list' style='width: 100%; max-width: 300px;'>".esc_attr($value_black_list)."</textarea></td>";
        echo "</tr>";
    }

    echo '</tbody></table>';
}
