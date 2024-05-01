<?php

// Hook into the rest_api_init action to register our custom REST route.
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/post/', array(
        'methods' => 'POST',
        'callback' => 'echo_spread_process',
        'permission_callback' => '__return_true',  // Включи проверку прав для продакшен-версии!
    ));
});

// Функция, которая обрабатывает запрос и создает пост
function echo_spread_process(WP_REST_Request $request) {
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    try {
        // Получаем токен из заголовка Authorization
        $headers = getallheaders();
        $received_token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

        log_api_request($request->get_body());

        // Сравниваем полученный токен с сохраненным в опции 'echo_spread_token'
        $options = get_option('echo_spread_options', []);
        $saved_token = $options['echo_spread_token'] ?: 'qwer1234';

        if ($received_token !== $saved_token) {
            return new WP_REST_Response('Unauthorized', 401);
        }

        // Получаем тело запроса и декодируем его из JSON
        $parameters = json_decode($request->get_body(), true);
        $title = $parameters['title'] ?? '';
        $message = $parameters['text'] ?? '';

        // Проверяем, содержит ли сообщение ключевые слова категорий
        $all_categories = get_categories(['hide_empty' => 0]);
        foreach ($all_categories as $current_cat) {
            /** @var WP_Term $current_cat */
            if(isset($options['category_keys'][$current_cat->term_id])) {
                $black_list = explode(';', $options['category_keys_black_list'][$current_cat->term_id]) ?: [];
                $black_list = array_map(fn($el) => trim($el), $black_list);

                foreach ($black_list as $key) {
                    if(stripos($message, $key) !== false) {
                        break 2;
                    }
                }

                $keys = explode(';', $options['category_keys'][$current_cat->term_id]) ?: [];
                $keys = array_map(fn($el) => trim($el), $keys);
                foreach ($keys as $key) {
                    if(stripos($message, $key) !== false) {
                        $categories[] = $current_cat->term_id;
                    }
                }
            }
        }

        if(!$categories && $options['echo_spread_default_category']) {
            $categories = [$options['echo_spread_default_category']];
        }


        $content = '';
        $attachments = [];
        if(isset($parameters['media']['image'])) {
            foreach ($parameters['media']['image'] as $src) {
                $attachment_id = media_sideload_image($src, 0, '', 'id');
                if (is_int($attachment_id)) {
                    $attachments[] = $attachment_id;
                    $content .= '<img style="margin-bottom:20px" src="' . wp_get_attachment_url($attachment_id) . '" alt="img">';
                } else {
                    throw new Exception('Ошибка при добавлении изображения.');
                }
            }
        }
        if(isset($parameters['media']['video'])) {
            foreach ($parameters['media']['video'] as $src) {
                $content .= '<video style="margin-bottom:20px;width:100%;" controls><source src="'. $src . '"></video>';
                break;
            }
        }
        $content .= $message;

        // Параметры для создания поста
        $post_data = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => $options['echo_spread_user'] ?: 1,
            'post_category' => $categories ?? []
        );

        // Вставляем пост в базу данных
        $post_id = wp_insert_post($post_data);

        foreach ($attachments as $id) {
            wp_update_post([
                'ID' => $id,
                'post_parent' => $post_id
            ]);
        }

    } catch (Exception $exception) {
        return new WP_REST_Response(['error' => $exception->getMessage()], 500);
    }

    if ($post_id !== 0) {
        return new WP_REST_Response(['message' => 'Post Created Successfully', 'post_id' => $post_id], 200);
    } else {
        return new WP_REST_Response(['error' => 'Failed to Create Post'], 500);
    }
}

function log_api_request($data) {
    $log_file = ABSPATH . 'wp-content/logs/api_requests.log';  // Путь к файлу лога
    $time_stamp = date("Y-m-d H:i:s");
    $log_entry = "{$time_stamp} - {$data}\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

?>
