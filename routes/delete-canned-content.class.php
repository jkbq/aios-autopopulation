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
            $edited  = array_sum(array_column($results, 'edited'));

            $message = sprintf('Deleted %d unmodified canned content item(s).', $deleted);
            if ($edited > 0) {
                $message .= sprintf(' Preserved %d edited item(s).', $edited);
            }

            return rest_ensure_response([
                'status'  => 'success',
                'message' => $message,
                'deleted' => $deleted,
                'edited'  => $edited,
                'results' => $results,
            ]);
        }

        $result = \AIOS\AUTOPOPULATE\Helpers\Helpers::delete_canned_content_section($section);

        if ($result['unmodified'] === 0 && $result['deleted'] === 0) {
            return rest_ensure_response([
                'status'  => 'success',
                'message' => 'No unmodified canned content found for this section.',
                'deleted' => 0,
                'edited'  => (int) $result['edited'],
                'section' => $section,
            ]);
        }

        $message = sprintf('Deleted %d unmodified canned content item(s).', $result['deleted']);
        if ($result['edited'] > 0) {
            $message .= sprintf(' Preserved %d edited item(s).', $result['edited']);
        }

        return rest_ensure_response([
            'status'  => 'success',
            'message' => $message,
            'deleted' => $result['deleted'],
            'edited'  => (int) $result['edited'],
            'section' => $section,
        ]);
    }
}
new DeleteCannedContent();
