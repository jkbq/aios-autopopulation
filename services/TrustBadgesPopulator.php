<?php

namespace AIOS\AUTOPOPULATE\Services;

class TrustBadgesPopulator
{
    public const PLACEHOLDER_ID = 'GeneratedUniqueIDSave';

    /**
     * @var array<string, array{post_type: string, template: string, template_group: string, repeater: string, option_key: string, post_title: string, row_type: string}>
     */
    private const TYPES = [
        'aios_awards' => [
            'post_type'      => 'aios-tb-award',
            'template'       => 'marquee',
            'template_group' => 'awards',
            'repeater'       => 'awards',
            'option_key'     => 'aios_auto_population_tb_awards_unique_id',
            'post_title'     => 'Awards',
            'row_type'       => 'awards',
        ],
        'aios_affiliates' => [
            'post_type'      => 'aios-tb-affiliate',
            'template'       => 'animated',
            'template_group' => 'affiliates',
            'repeater'       => 'affiliates',
            'option_key'     => 'aios_auto_population_tb_affiliates_unique_id',
            'post_title'     => 'Affiliates',
            'row_type'       => 'affiliates',
        ],
        'aios_press_highlight' => [
            'post_type'      => 'aios-tbpresshl',
            'template'       => 'default',
            'template_group' => 'presshighlight',
            'repeater'       => 'press_highlights',
            'option_key'     => 'aios_auto_population_tb_press_highlight_unique_id',
            'post_title'     => 'Press Highlights',
            'row_type'       => 'press_highlight',
        ],
    ];

    /**
     * First 17 catalog keys from TrustBadgesData::getOptions(), excluding empty and others.
     *
     * @var string[]
     */
    private const CATALOG_KEYS = [
        'coldwell_banker_international_presidents_circle',
        'real_trends_verified',
        'boston_magazine_top_real_estate_producers_2025',
        'd_magazine_best_2025',
        'modern_luxury_power_players_dallas_2025',
        'rp_real_producers_top_500_dallas',
        'variety',
        'forbes_global_properties',
        'los_angeles_business_journal',
        'luxuryhomes_affiliate_member',
        'california_department_of_real_estate',
        'national_association_of_realtors',
        'SDAR',
        'aba_defending_liberty_pursuing_justice',
        'juwai',
        'san_diego_mls',
        'greater_los_angeles_realtors',
    ];

    /**
     * Scan theme config widgets, create missing sections, return unique IDs by shortcode.
     *
     * @return array{created: string[], skipped: string[], ids: array<string, string>}
     */
    public static function ensureFromConfig(?object $config = null): array
    {
        $config = $config ?? PopulateService::getConfig();
        $detected = self::detectPlaceholderTypes($config);

        $created = [];
        $skipped = [];
        $ids     = [];

        foreach ($detected as $shortcode) {
            $result = self::ensureSection($shortcode, $config);

            if ($result['unique_id'] !== '') {
                $ids[$shortcode] = $result['unique_id'];
            }

            if ($result['status'] === 'created') {
                $created[] = $shortcode;
            } else {
                $skipped[] = $shortcode;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'ids'     => $ids,
        ];
    }

    /**
     * @return string[] Shortcode names that contain the placeholder id.
     */
    public static function detectPlaceholderTypes(?object $config): array
    {
        if (! $config || empty($config->widgets)) {
            return [];
        }

        $haystack = self::collectWidgetStrings($config->widgets);
        $found    = [];

        foreach (array_keys(self::TYPES) as $shortcode) {
            if (self::contentHasPlaceholder($haystack, $shortcode)) {
                $found[] = $shortcode;
            }
        }

        return $found;
    }

    public static function replacePlaceholders(string $content): string
    {
        if ($content === '' || strpos($content, self::PLACEHOLDER_ID) === false) {
            return $content;
        }

        foreach (self::TYPES as $shortcode => $meta) {
            $unique_id = (string) get_option($meta['option_key'], '');
            if ($unique_id === '') {
                continue;
            }

            $content = preg_replace(
                '/(\[' . preg_quote($shortcode, '/') . '\s[^\]]*?\bid=["\'])' . preg_quote(self::PLACEHOLDER_ID, '/') . '(["\'])/i',
                '${1}' . $unique_id . '${2}',
                $content
            ) ?? $content;
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function replacePlaceholdersInArgs(array $args): array
    {
        foreach ($args as $key => $value) {
            if (is_string($value)) {
                $args[$key] = self::replacePlaceholders($value);
            }
        }

        return $args;
    }

    /**
     * Apply config.json plugins.{shortcode}.theme to existing Trust Badges sections.
     */
    public static function applyPluginSettings(?object $config = null): void
    {
        $config = $config ?? PopulateService::getConfig();
        if (! $config) {
            return;
        }

        foreach (self::TYPES as $shortcode => $type) {
            $unique_id = (string) get_option($type['option_key'], '');
            if ($unique_id === '') {
                continue;
            }

            $post_id = self::findSectionByUniqueId($type['post_type'], $unique_id);
            if (! $post_id) {
                continue;
            }

            self::applySectionSettings($post_id, $config, $shortcode);
        }
    }

    /**
     * @return array{status: string, unique_id: string, message: string}
     */
    private static function ensureSection(string $shortcode, ?object $config = null): array
    {
        $type = self::TYPES[$shortcode] ?? null;
        if (! $type) {
            return ['status' => 'skipped', 'unique_id' => '', 'message' => 'Unknown shortcode'];
        }

        if (! post_type_exists($type['post_type'])) {
            return ['status' => 'skipped', 'unique_id' => '', 'message' => 'Post type not registered'];
        }

        $unique_id = (string) get_option($type['option_key'], '');
        if ($unique_id === '') {
            $unique_id = self::generateUniqueId();
            update_option($type['option_key'], $unique_id);
        }

        $template      = self::resolveTemplate($config, $shortcode);
        $speed         = self::resolveMarqueeSpeed($config, $shortcode);
        $section_title = self::resolveSectionTitle($config, $shortcode);
        $existing_id   = self::findSectionByUniqueId($type['post_type'], $unique_id);

        if ($existing_id) {
            self::applySectionSettings($existing_id, $config, $shortcode);

            return ['status' => 'exists', 'unique_id' => $unique_id, 'message' => 'Section already exists'];
        }

        $post_id = wp_insert_post([
            'post_type'    => $type['post_type'],
            'post_title'   => $section_title !== '' ? $section_title : $type['post_title'],
            'post_status'  => 'publish',
            'post_author'  => 1,
            'meta_input'   => [
                'custom_unique_id'         => $unique_id,
                'section_visibility'       => '1',
                'template'                 => $template,
                'section_title'            => $section_title,
                'marquee_animation_speed'  => $speed,
            ],
        ]);

        if (! $post_id || is_wp_error($post_id)) {
            return ['status' => 'skipped', 'unique_id' => $unique_id, 'message' => 'Failed to create section'];
        }

        update_post_meta((int) $post_id, $type['repeater'], self::buildLogoRows($type['row_type'], $config, $shortcode));
        self::ensureFeaturedPhoto((int) $post_id, $config, $shortcode);

        return ['status' => 'created', 'unique_id' => $unique_id, 'message' => 'Section created'];
    }

    private static function applySectionSettings(int $post_id, ?object $config, string $shortcode): void
    {
        $type = self::TYPES[$shortcode] ?? null;
        if (! $type) {
            return;
        }

        $section_title = self::resolveSectionTitle($config, $shortcode);

        update_post_meta($post_id, 'template', self::resolveTemplate($config, $shortcode));
        update_post_meta($post_id, 'marquee_animation_speed', self::resolveMarqueeSpeed($config, $shortcode));
        update_post_meta($post_id, 'section_title', $section_title);

        if ($section_title !== '') {
            wp_update_post([
                'ID'         => $post_id,
                'post_title' => $section_title,
            ]);
        }

        if (self::logosFromConfig($config, $shortcode) !== []) {
            update_post_meta($post_id, $type['repeater'], self::buildLogoRows($type['row_type'], $config, $shortcode));
        }

        self::ensureFeaturedPhoto($post_id, $config, $shortcode);
    }

    private static function ensureFeaturedPhoto(int $post_id, ?object $config, string $shortcode): void
    {
        if ($shortcode !== 'aios_press_highlight') {
            return;
        }

        if ((string) get_post_meta($post_id, 'featured_photo', true) !== '') {
            return;
        }

        if (! $config) {
            return;
        }

        $settings = $config->config[0]->plugins->aios_press_highlight ?? null;
        $filename = is_object($settings) ? basename((string) ($settings->featured_photo ?? '')) : '';

        if ($filename === '') {
            return;
        }

        $extension = is_object($settings) ? trim((string) ($settings->extension ?? 'assets'), '/') : 'assets';
        if ($extension === '') {
            $extension = 'assets';
        }

        $url = rtrim(PopulateService::getThemeUri(), '/') . '/' . $extension . '/images/' . $filename;

        $attachment_id = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_attachment_by_source_url($url);

        if ($attachment_id <= 0) {
            if (! function_exists('media_sideload_image')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }

            $sideloaded = media_sideload_image($url, $post_id, '', 'id');
            if (is_wp_error($sideloaded) || ! $sideloaded) {
                return;
            }

            $attachment_id = (int) $sideloaded;
        }

        if ($attachment_id > 0) {
            update_post_meta($post_id, 'featured_photo', $attachment_id);
        }
    }

    private static function resolveSectionTitle(?object $config, string $shortcode): string
    {
        if (! $config || ! isset($config->config[0]->plugins->{$shortcode}->section_title)) {
            return '';
        }

        return sanitize_text_field((string) $config->config[0]->plugins->{$shortcode}->section_title);
    }

    private static function resolveMarqueeSpeed(?object $config, string $shortcode): string
    {
        $speed = '12';

        if ($config && isset($config->config[0]->plugins->{$shortcode}->marquee_animation_speed)) {
            $speed = (string) $config->config[0]->plugins->{$shortcode}->marquee_animation_speed;
        }

        $speed = trim($speed);

        return $speed !== '' ? sanitize_text_field($speed) : '12';
    }

    private static function resolveTemplate(?object $config, string $shortcode): string
    {
        $type    = self::TYPES[$shortcode];
        $default = $type['template'];
        $theme   = '';

        if ($config && isset($config->config[0]->plugins->{$shortcode}->theme)) {
            $theme = (string) $config->config[0]->plugins->{$shortcode}->theme;
        }

        if (class_exists('\\AIOS\\TrustBadges\\Helpers\\TemplateSupport')) {
            return \AIOS\TrustBadges\Helpers\TemplateSupport::resolveTemplateSlug(
                $type['template_group'],
                $theme,
                $default
            );
        }

        $allowed = [
            'awards'         => ['default', 'marquee', 'smallerlogo'],
            'affiliates'     => ['biggerLogoAnimated', 'gridType', 'animated'],
            'presshighlight' => ['default', '2column'],
        ];

        $group_allowed = $allowed[$type['template_group']] ?? [];

        return in_array($theme, $group_allowed, true) ? $theme : $default;
    }

    private static function generateUniqueId(): string
    {
        if (function_exists('aiosnexus_generate_unique_id')) {
            return aiosnexus_generate_unique_id('custom_unique_id');
        }

        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 12);
    }

    private static function findSectionByUniqueId(string $post_type, string $unique_id): int
    {
        $sections = get_posts([
            'post_type'              => $post_type,
            'posts_per_page'         => 1,
            'post_status'            => 'any',
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'     => 'custom_unique_id',
                    'value'   => $unique_id,
                    'compare' => '=',
                ],
            ],
        ]);

        return ! empty($sections) ? (int) $sections[0] : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildLogoRows(string $row_type, ?object $config, string $shortcode): array
    {
        $defined = self::logosFromConfig($config, $shortcode);

        if ($defined !== []) {
            $rows = [];

            foreach ($defined as $item) {
                $row = self::normalizeLogoRow($row_type, $item);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }

            if ($rows !== []) {
                return $rows;
            }
        }

        return self::buildDefaultCatalogRows($row_type);
    }

    /**
     * @return array<int, array<string, mixed>|object>
     */
    private static function logosFromConfig(?object $config, string $shortcode): array
    {
        if (! $config || empty($config->config[0]->plugins->{$shortcode}->logos)) {
            return [];
        }

        $logos = $config->config[0]->plugins->{$shortcode}->logos;

        if (is_array($logos)) {
            $items = [];

            foreach ($logos as $key => $item) {
                if (is_string($item)) {
                    $items[] = ['selection' => $item];
                    continue;
                }

                if (is_object($item) || is_array($item)) {
                    $row = (array) $item;
                    if (! isset($row['selection']) && is_string($key) && ! is_numeric($key)) {
                        $row['selection'] = $key;
                    }
                    $items[] = $row;
                }
            }

            return $items;
        }

        if (is_object($logos)) {
            $items = [];

            foreach ($logos as $key => $item) {
                if (is_string($item)) {
                    $items[] = [
                        'selection' => is_string($key) && ! is_numeric($key) ? $key : $item,
                        'title'     => is_string($key) && ! is_numeric($key) ? $item : '',
                    ];
                    continue;
                }

                $row = is_object($item) || is_array($item) ? (array) $item : [];
                if (! isset($row['selection']) && is_string($key) && ! is_numeric($key)) {
                    $row['selection'] = $key;
                }
                $items[] = $row;
            }

            return $items;
        }

        return [];
    }

    /**
     * @param array<string, mixed>|object $item
     * @return array<string, mixed>|null
     */
    private static function normalizeLogoRow(string $row_type, $item): ?array
    {
        $item      = (array) $item;
        $selection = sanitize_text_field((string) ($item['selection'] ?? ''));

        if ($selection === '' || $selection === 'select') {
            return null;
        }

        $has_data = class_exists('\\AIOS\\TrustBadges\\Helpers\\TrustBadgesData');
        $logo     = '';

        if ($selection === 'others') {
            $logo = sanitize_text_field((string) ($item['logo'] ?? ''));
        } elseif ($has_data) {
            $logo = \AIOS\TrustBadges\Helpers\TrustBadgesData::getDefaultLogo($selection);
        }

        $title = sanitize_text_field((string) ($item['title'] ?? $item['caption'] ?? ''));
        $link  = esc_url_raw((string) ($item['link'] ?? ''));
        $gray  = ! empty($item['logo_url_grayscale']) ? '1' : '';

        if ($row_type === 'affiliates') {
            return [
                'selection'          => $selection,
                'logo'               => $logo,
                'title'              => $title,
                'link'               => $link,
                'sub_fields'         => [],
                'logo_url_grayscale' => $gray,
            ];
        }

        if ($row_type === 'press_highlight') {
            return [
                'selection'          => $selection,
                'logo'               => $logo,
                'title'              => $title,
                'link'               => $link,
                'sub_fields'         => [],
                'logo_url_grayscale' => $gray,
            ];
        }

        return [
            'selection'          => $selection,
            'logo'               => $logo,
            'title'              => $title,
            'sub_fields'         => [],
            'logo_url_grayscale' => $gray,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildDefaultCatalogRows(string $row_type): array
    {
        $has_data = class_exists('\\AIOS\\TrustBadges\\Helpers\\TrustBadgesData');
        $rows     = [];

        foreach (self::CATALOG_KEYS as $selection) {
            $row = self::normalizeLogoRow($row_type, ['selection' => $selection]);
            if ($row !== null) {
                if (! $has_data) {
                    $row['logo'] = '';
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<int, object>|object $widgets
     */
    private static function collectWidgetStrings($widgets): string
    {
        $parts = [];

        foreach ($widgets as $widget) {
            if (! is_object($widget) || empty($widget->args)) {
                continue;
            }

            foreach ($widget->args as $value) {
                if (is_string($value) && $value !== '') {
                    $parts[] = $value;
                }
            }
        }

        return implode("\n", $parts);
    }

    private static function contentHasPlaceholder(string $haystack, string $shortcode): bool
    {
        if ($haystack === '') {
            return false;
        }

        $pattern = '/\[' . preg_quote($shortcode, '/') . '\s[^\]]*?\bid=["\']' . preg_quote(self::PLACEHOLDER_ID, '/') . '["\']/i';

        return (bool) preg_match($pattern, $haystack);
    }
}
