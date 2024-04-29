<?php

function allow_source_tag($tags, $context) {
    if ($context === 'post') {
        // Добавляем тег <source> и его атрибуты
        $tags['source'] = array(
            'src' => true,
            'type' => true,
            'srcset' => true,
            'sizes' => true,
            'media' => true
        );
    }

    return $tags;
}

add_filter('wp_kses_allowed_html', 'allow_source_tag', 10, 2);