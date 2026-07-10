<?php

namespace AIOS\AUTOPOPULATE\Services;

class PopulateService
{
    public static function getActiveThemeSlug(): string
    {
        $template   = get_option('template');
        $stylesheet = get_option('stylesheet');

        return $template === 'aios-starter-theme' ? $stylesheet : $template;
    }

    public static function getThemeUri(): string
    {
        $template = get_option('template');

        return $template === 'aios-starter-theme'
            ? get_stylesheet_directory_uri()
            : get_template_directory_uri();
    }

    public static function getConfig(): ?object
    {
        return \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');
    }

    public static function getContents(): ?object
    {
        return \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('contents.json');
    }

    public static function clearCache(): void
    {
        \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
    }

    public static function respond(bool $ok, string $message, string $date = '', array $extra = []): \WP_REST_Response
    {
        return rest_ensure_response(array_merge([
            'ok'      => $ok,
            'success' => $ok,
            'status'  => $ok ? 'success' : 'error',
            'message' => $message,
            'date'    => $date,
        ], $extra));
    }
}
