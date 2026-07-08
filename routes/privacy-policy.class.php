<?php

namespace AIOS\AUTOPOPULATE\Routes;

class PrivacyPolicy
{
    private const PAGE_SLUG = 'privacy-policy';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/privacy-policy', [
            'methods'             => 'POST',
            'callback'            => [$this, 'populate'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function populate($data)
    {
        $repopulate = $this->get_request_param($data, 'repopulate');

        if ($repopulate) {
            delete_option('aios_auto_population_privacy_policy');
            delete_option('aios_auto_population_privacy_policy_date');
            \AIOS\AUTOPOPULATE\Helpers\Helpers::clear_theme_json_cache();
        }

        $dateComplete = get_option('aios_auto_population_privacy_policy_date', $this->get_request_param($data, 'date', ''));
        $already_done = get_option('aios_auto_population_privacy_policy', false);

        if ($already_done) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Privacy Policy already generated',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        if (!$this->ensure_privacy_renderer()) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Privacy Policy renderer is not available. Ensure aios-initial-setup includes the privacy-policy module.',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        $config = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');

        if (!$config) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Failed to load theme config.json',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        if (!isset($config->config[0]->aios_privacy_policy)) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'No privacy policy config in theme; skipped',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        $privacy  = (array) $config->config[0]->aios_privacy_policy;
        $settings = [
            'override_headings'  => $privacy['override_headings'] ?? '',
            'override_body_text' => $privacy['override_body_text'] ?? '',
            'client_name'        => $privacy['client_name'] ?? '',
            'legal_name'         => $privacy['legal_name'] ?? '',
            'address'            => $privacy['address'] ?? '',
            'email'              => $privacy['email'] ?? '',
            'phone'              => $privacy['phone'] ?? '',
            'policy_url'         => do_shortcode(
                str_replace('[blogurl]', home_url(), $privacy['policy_url'] ?? '')
            ),
            'opt_idx'            => !empty($privacy['opt_idx']),
            'opt_analytics'      => !empty($privacy['opt_analytics']),
            'opt_crm'            => !empty($privacy['opt_crm']),
            'opt_cookies'        => !empty($privacy['opt_cookies']),
            'opt_euuk'           => !empty($privacy['opt_euuk']),
            'opt_ccpa'           => !empty($privacy['opt_ccpa']),
        ];

        $html = \AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\RendererController::fromSettings(
            array_merge(
                $settings,
                [
                    'updated_date'   => date_i18n(get_option('date_format')),
                    'editable_field' => false,
                ]
            )
        )->render();

        update_option($this->option_key(), $settings);
        update_option($this->option_content_key(), wp_kses_post($html));

        $page_id = $this->with_publish_capability(function () {
            return $this->publish_privacy_page();
        });

        if ($page_id) {
            $settings['selected_page'] = (string) $page_id;
            update_option($this->option_key(), $settings);
        }

        update_option('aios_auto_population_privacy_policy', true);
        update_option('aios_auto_population_privacy_policy_date', $dateComplete);

        return rest_ensure_response([
            'success'     => true,
            'message'     => $page_id ? 'Privacy Policy generated successfully' : 'Privacy Policy settings saved but page could not be created',
            'date'        => $dateComplete,
            'page_id'     => $page_id ?: null,
            'post_status' => $page_id ? get_post_status($page_id) : null,
            'post_name'   => $page_id ? get_post_field('post_name', $page_id) : null,
            'skipped'     => false,
        ]);
    }

    private function get_request_param($data, string $key, $default = null)
    {
        if ($data instanceof \WP_REST_Request) {
            return $data->get_param($key) ?? $default;
        }

        if (is_array($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    private function option_key(): string
    {
        return defined('REPP_OPTION') ? REPP_OPTION : 'aios_privacy_policy';
    }

    private function option_content_key(): string
    {
        return defined('REPP_OPTION_CONTENT') ? REPP_OPTION_CONTENT : 'aios_privacy_policy_content';
    }

    private function ensure_privacy_renderer(): bool
    {
        if (class_exists(\AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\RendererController::class)) {
            return true;
        }

        if (!defined('AIOS_INITIAL_SETUP_DIR')) {
            return false;
        }

        $controllers_dir = AIOS_INITIAL_SETUP_DIR . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'privacy-policy' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR;
        $renderer_file   = $controllers_dir . 'RendererController.php';

        if (!file_exists($renderer_file)) {
            return false;
        }

        if (!defined('REPP_OPTION')) {
            define('REPP_API_NAMESPACE', 'aios/v1/privacy-policy');
            define('REPP_OPTION', 'aios_privacy_policy');
            define('REPP_OPTION_CONTENT', 'aios_privacy_policy_content');
        }

        require_once $renderer_file;

        $shortcode_file = $controllers_dir . 'ShortcodeController.php';
        if (file_exists($shortcode_file) && !shortcode_exists('aios_privacy_policy')) {
            require_once $shortcode_file;
            new \AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\ShortcodeController();
        }

        $page_file = $controllers_dir . 'PageController.php';
        if (file_exists($page_file) && !has_filter('the_content', [\AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\PageController::class, 'render_shortcode_in_selected_page'])) {
            require_once $page_file;
            new \AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\PageController();
        }

        return class_exists(\AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\RendererController::class);
    }

    private function with_publish_capability(callable $callback)
    {
        $previous_user = get_current_user_id();
        $admin_id      = $this->get_admin_user_id();

        if ($admin_id > 0) {
            wp_set_current_user($admin_id);
        }

        try {
            return $callback();
        } finally {
            wp_set_current_user($previous_user);
        }
    }

    private function get_admin_user_id(): int
    {
        $admins = get_users([
            'role'   => 'administrator',
            'number' => 1,
            'fields' => 'ID',
        ]);

        return !empty($admins) ? (int) $admins[0] : 1;
    }

    /**
     * Publish the WordPress default Privacy Policy page and return its ID.
     * PageController in aios-initial-setup injects policy content via selected_page.
     */
    private function publish_privacy_page(): int
    {
        $page_id = $this->find_existing_privacy_page_id();

        if ($page_id > 0) {
            wp_update_post([
                'ID'          => $page_id,
                'post_status' => 'publish',
                'post_name'   => self::PAGE_SLUG,
            ]);
        } else {
            $inserted = wp_insert_post([
                'post_type'    => 'page',
                'post_title'   => 'Privacy Policy',
                'post_name'    => self::PAGE_SLUG,
                'post_content' => '[aios_privacy_policy]',
                'post_status'  => 'publish',
                'post_author'  => $this->get_admin_user_id(),
            ]);

            if (is_wp_error($inserted) || !$inserted) {
                return 0;
            }

            $page_id = (int) $inserted;
        }

        $this->ensure_published_page($page_id);

        if (get_post_status($page_id) !== 'publish') {
            error_log('AIOS Autopopulate: Privacy Policy page ' . $page_id . ' could not be published; leaving wp_page_for_privacy_policy untouched.');
            return 0;
        }

        update_option('wp_page_for_privacy_policy', $page_id);
        clean_post_cache($page_id);

        return $page_id;
    }

    private function ensure_published_page(int $page_id): void
    {
        if (get_post_status($page_id) !== 'publish') {
            wp_publish_post($page_id);
        }

        if (get_post_field('post_name', $page_id) !== self::PAGE_SLUG) {
            wp_update_post([
                'ID'        => $page_id,
                'post_name' => self::PAGE_SLUG,
            ]);
        }
    }

    private function find_existing_privacy_page_id(): int
    {
        $wp_id = (int) get_option('wp_page_for_privacy_policy');
        if ($wp_id > 0 && get_post($wp_id)) {
            return $wp_id;
        }

        $by_slug = get_posts([
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'private', 'pending'],
            'name'           => self::PAGE_SLUG,
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ]);

        if (!empty($by_slug)) {
            return (int) $by_slug[0]->ID;
        }

        global $wpdb;

        $by_title = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_title = %s AND post_status IN ('publish', 'draft', 'private', 'pending') ORDER BY ID ASC LIMIT 1",
                'Privacy Policy'
            )
        );

        return $by_title > 0 ? $by_title : 0;
    }
}

new PrivacyPolicy();
