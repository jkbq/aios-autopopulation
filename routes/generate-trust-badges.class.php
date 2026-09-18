<?php

namespace AIOS\AUTOPOPULATE\Routes;

class TrustBadges
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/trust-badges', [
            'methods'             => 'POST',
            'callback'            => [$this, 'aios_populate_trust_badges'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin_or_install_token'],
        ]);
    }

    public function aios_populate_trust_badges($data)
    {
        $is_repopulate = ! empty($data['repopulate']);
        $date          = $data['date'] ?? '';
        $dateComplete  = get_option('aios_auto_population_trust_badges_date', $date);

        if ($is_repopulate) {
            \AIOS\AUTOPOPULATE\Services\PopulateService::clearCache();
            delete_option('aios_auto_population_trust_badges');
            delete_option('aios_auto_population_trust_badges_date');
        }

        $already_generated = get_option('aios_auto_population_trust_badges', false);

        $result = \AIOS\AUTOPOPULATE\Services\TrustBadgesPopulator::ensureFromConfig();

        $saved_date = $is_repopulate ? ($date ?: $dateComplete) : ($dateComplete ?: $date);
        update_option('aios_auto_population_trust_badges', true);
        update_option('aios_auto_population_trust_badges_date', $saved_date);

        if (empty($result['created']) && empty($result['ids'])) {
            $message = 'No Trust Badges shortcode placeholders found';
        } elseif (! empty($result['created'])) {
            $message = 'Trust Badges generated successfully';
        } else {
            $message = 'Trust Badges sections already exist';
        }

        return rest_ensure_response([
            'status'  => 'success',
            'message' => $message,
            'date'    => $saved_date,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]);
    }
}
new TrustBadges();
