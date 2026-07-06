<?php

namespace AIOS\AUTOPOPULATE\Routes;

class PrivacyPolicy
{
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
        if (!empty($data['repopulate'])) {
            delete_option('aios_auto_population_privacy_policy');
            delete_option('aios_auto_population_privacy_policy_date');
        }

        $dateComplete = get_option('aios_auto_population_privacy_policy_date', $data['date'] ?? '');
        $already_done = get_option('aios_auto_population_privacy_policy', false);

        if ($already_done) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Privacy Policy already generated',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        if (!class_exists(\AiosInitialSetup\App\Modules\PrivacyPolicy\Controllers\RendererController::class)) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Privacy Policy renderer is not available',
                'date'    => $dateComplete,
                'skipped' => true,
            ]);
        }

        $config = \AIOS\AUTOPOPULATE\Helpers\Helpers::get_theme_json('config.json');

        if (!$config || !isset($config->config[0]->aios_privacy_policy)) {
            update_option('aios_auto_population_privacy_policy', true);
            update_option('aios_auto_population_privacy_policy_date', $dateComplete);

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

        $wp_privacy_page_id = (int) get_option('wp_page_for_privacy_policy');
        if ($wp_privacy_page_id) {
            wp_delete_post($wp_privacy_page_id, true);
            delete_option('wp_page_for_privacy_policy');
        }

        $page_id = $this->upsert_privacy_page();
        if ($page_id) {
            $settings['selected_page'] = (string) $page_id;
            update_option($this->option_key(), $settings);
        }

        update_option('aios_auto_population_privacy_policy', true);
        update_option('aios_auto_population_privacy_policy_date', $dateComplete);

        return rest_ensure_response([
            'success' => true,
            'message' => 'Privacy Policy generated successfully',
            'date'    => $dateComplete,
            'page_id' => $page_id ?: null,
            'skipped' => false,
        ]);
    }

    private function option_key(): string
    {
        return defined('REPP_OPTION') ? REPP_OPTION : 'aios_privacy_policy';
    }

    private function option_content_key(): string
    {
        return defined('REPP_OPTION_CONTENT') ? REPP_OPTION_CONTENT : 'aios_privacy_policy_content';
    }

    private function upsert_privacy_page(): int
    {
        $shortcode = '[aios_privacy_policy]';
        $page      = get_page_by_path('privacy-policy');

        if ($page) {
            wp_update_post([
                'ID'           => $page->ID,
                'post_content' => $shortcode,
                'post_status'  => 'publish',
            ]);

            return (int) $page->ID;
        }

        $draft_pages = get_posts([
            'post_type'   => 'page',
            'post_status' => 'draft',
            'numberposts' => 1,
            'name'        => 'privacy-policy',
        ]);

        if (!empty($draft_pages)) {
            wp_update_post([
                'ID'           => $draft_pages[0]->ID,
                'post_content' => $shortcode,
                'post_status'  => 'publish',
            ]);

            return (int) $draft_pages[0]->ID;
        }

        $page_id = wp_insert_post([
            'post_type'    => 'page',
            'post_title'   => 'Privacy Policy',
            'post_name'    => 'privacy-policy',
            'post_content' => $shortcode,
            'post_status'  => 'publish',
            'post_author'  => 1,
        ]);

        return is_wp_error($page_id) ? 0 : (int) $page_id;
    }
}

new PrivacyPolicy();
