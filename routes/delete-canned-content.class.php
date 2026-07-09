<?php

namespace AIOS\AUTOPOPULATE\Routes;

class DeleteCannedContent
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints()
    {
        register_rest_route('aios-populate/v1', '/delete-canned-content', [
            'methods'             => 'POST',
            'callback'            => [$this, 'delete_canned_content'],
            'permission_callback' => [\AIOS\AUTOPOPULATE\Helpers\RestAuth::class, 'require_admin'],
        ]);
    }

    public function delete_canned_content($request)
    {
        $section = sanitize_key($request['section'] ?? 'all');

        if ($section === 'all') {
            $results = \AIOS\AUTOPOPULATE\Helpers\Helpers::delete_all_canned_content();
            $deleted = array_sum(array_column($results, 'deleted'));

            return rest_ensure_response([
                'status'  => 'success',
                'message' => sprintf('Deleted %d canned content item(s).', $deleted),
                'deleted' => $deleted,
                'results' => $results,
            ]);
        }

        $result = \AIOS\AUTOPOPULATE\Helpers\Helpers::delete_canned_content_section($section);

        if ($result['count'] === 0 && $result['deleted'] === 0) {
            return rest_ensure_response([
                'status'  => 'success',
                'message' => 'No tracked canned content found for this section.',
                'deleted' => 0,
                'section' => $section,
            ]);
        }

        return rest_ensure_response([
            'status'  => 'success',
            'message' => sprintf('Deleted %d canned content item(s).', $result['deleted']),
            'deleted' => $result['deleted'],
            'section' => $section,
        ]);
    }
}
new DeleteCannedContent();
