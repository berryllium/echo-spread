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
function echo_spread_process($request) {
    try {
        // Получаем токен из заголовка Authorization
        $headers = getallheaders();
        $received_token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

        log_api_request($request->get_body());

        // Сравниваем полученный токен с сохраненным в опции 'echo_spread_token'
        $saved_token = get_option('echo_spread_token', 'qwer1234');

        if ($received_token !== $saved_token) {
            return new WP_REST_Response('Unauthorized', 401);
        }

        // Получаем тело запроса и декодируем его из JSON
        $parameters = json_decode($request->get_body(), true);
        $title = $parameters['title'] ?? '';  // Убедись, что ключ 'message' существует
        $message = $parameters['text'] ?? '';  // Убедись, что ключ 'message' существует

        // Проверяем, содержит ли сообщение слово "привет"
        $category = strpos(strtolower($message), 'привет') !== false ? [5] : [];

        // Параметры для создания поста
        $post_data = array(
            'post_title' => wp_strip_all_tags($message),
            'post_content' => $message,
            'post_status' => 'publish',
            'post_author' => 1,  // ID автора, замените на актуальный ID пользователя
            'post_category' => $category
        );

        // Вставляем пост в базу данных
        $post_id = wp_insert_post($post_data);
    } catch (Exception $exception) {
        return new WP_REST_Response(json_encode(['error' => $exception->getMessage()], 500));
    }

    if ($post_id !== 0) {
        return new WP_REST_Response(json_encode(['message' => 'Post Created Successfully', 'post_id' => $post_id]), 200);
    } else {
        return new WP_REST_Response(json_encode(['error' => 'Failed to Create Post'], 500));
    }
}

function log_api_request($data) {
    $log_file = ABSPATH . 'wp-content/logs/api_requests.log';  // Путь к файлу лога
    $time_stamp = date("Y-m-d H:i:s");
    $log_entry = "{$time_stamp} - {$data}\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

?>
