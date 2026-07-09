<?php

namespace AIOS\AUTOPOPULATE\Services;

class AboutContactPopulator
{
    /**
     * Lightweight theme-switch refresh (from regeneratePage about/contact block).
     */
    public static function runRefresh(object $config, string $sPath): array
    {
        if (empty($config->about_contact[0])) {
            return ['ok' => false, 'message' => 'No about/contact config found'];
        }

        $productType = $config->config[0]->product_type ?? '';
        $aboutBlock  = $config->about_contact[0];
        $about       = $aboutBlock->about ?? null;
        $contact     = $aboutBlock->contact ?? null;
        $generated   = [];

        if ($about && ! isset($about->disabled)) {
            $about_options = get_option('about_options', []);
            $aios_client_info = get_option('aiis_ci', []);

            if (! empty($about_options['agent_team_photo'])) {
                $aios_client_info['photo'] = wp_get_attachment_image_url($about_options['agent_team_photo'], 'full');
                update_option('aiis_ci', $aios_client_info);
            }

            $about_options['theme'] = $productType . '-' . $about->theme;
            update_option('about-theme', $productType . '-' . $about->theme);

            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages('about', $about->theme, true);
            }

            update_option('about_options', $about_options);
            $generated[] = 'About';
        }

        if ($contact && ! isset($contact->disabled)) {
            $image             = $aboutBlock->image ?? null;
            $extension         = ! empty($image->extension) ? $image->extension . '/' : '';
            $background_image_url = $sPath . '/' . $extension . 'images/' . ($image->background ?? '');
            $contact_options   = get_option('contact_options', []);

            if (($contact_options['theme'] ?? '') !== 'agent-pro-element') {
                if ($contact->theme === 'element' && $background_image_url) {
                    $backgroundImage = media_sideload_image($background_image_url, $contact_options['page_id'] ?? 0, '', 'id');
                    if (! is_wp_error($backgroundImage)) {
                        $contact_options['agent_team_photo'] = $backgroundImage;
                    }
                }
            }

            $contact_options['theme'] = $productType . '-' . $contact->theme;
            if (isset($contact->address_display)) {
                $contact_options['address-display'] = $contact->address_display;
            }

            update_option('contact-theme', $productType . '-' . $contact->theme);

            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages('contact', $contact->theme, true);
            }

            update_option('contact_options', $contact_options);
            $generated[] = 'Contact';
        }

        $about_options   = get_option('about_options', []);
        $contact_options = get_option('contact_options', []);

        if (! empty($aboutBlock->page_template_about) && ! empty($about_options['page_id'])) {
            update_post_meta($about_options['page_id'], '_wp_page_template', $aboutBlock->page_template_about);
        }

        if (! empty($aboutBlock->page_template_contact) && ! empty($contact_options['page_id'])) {
            update_post_meta($contact_options['page_id'], '_wp_page_template', $aboutBlock->page_template_contact);
        }

        return [
            'ok'      => true,
            'message' => empty($generated) ? 'No about/contact templates updated' : implode(' and ', $generated) . ' templates updated',
        ];
    }

    /**
     * Full first-run population (install flow).
     */
    public static function runInitial(object $config, string $sPath): array
    {
        if (empty($config->about_contact)) {
            return ['ok' => false, 'message' => 'No about/contact config found'];
        }

        $productType       = $config->config[0]->product_type ?? '';
        $image             = $config->about_contact[0]->image;
        $extension         = ! empty($image->extension) ? $image->extension . '/' : '';
        $image_path        = $sPath . '/' . $extension . 'images/';
        $background_image_url = $sPath . '/' . $extension . 'images/' . $image->background;
        $aios_client_info  = get_option('aiis_ci', []);
        $generatedResponse = '';
        $agentPhoto        = null;

        $about = $config->about_contact[0]->about ?? null;

        if ($about && ! isset($about->disabled)) {
            $about_options = get_option('about_options', []);
            $agentPhoto = media_sideload_image($image_path . $image->image_name, $about_options['page_id'], '', 'id');

            if (! is_wp_error($agentPhoto)) {
                $about_options['agent_team_photo'] = $agentPhoto;
                $aios_client_info['photo'] = wp_get_attachment_image_url($agentPhoto, 'full');
                update_option('aiis_ci', $aios_client_info);
            }

            foreach ($about as $key => $content) {
                if ($key === 'theme') {
                    $about_options[$key] = $productType . '-' . $content;
                } elseif ($key === 'about_overlay_photo') {
                    $about_options[$key] = media_sideload_image($image_path . $about->about_overlay_photo, $about_options['page_id'], '', 'id');
                } else {
                    $about_options[$key] = $content;
                }
            }

            update_option('about-theme', $productType . '-' . $about->theme);

            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages('about', $about->theme, true);
            }

            update_option('about_options', $about_options);
            $generatedResponse = 'About';
        }

        $contact = $config->about_contact[0]->contact ?? null;

        if ($contact && ! isset($contact->disabled)) {
            $contact_options = get_option('contact_options', []);

            if (isset($contact->agent_team_photo)) {
                $contactFormPhotoSrc = $sPath . '/' . $image->extension . '/images/' . $contact->agent_team_photo;
                $contactFormPhoto = media_sideload_image($contactFormPhotoSrc, $contact_options['page_id'], '', 'id');
                $contact_options['agent_team_photo'] = $contactFormPhoto;
            } elseif ($contact->theme !== 'element' && $agentPhoto) {
                $contact_options['agent_team_photo'] = $agentPhoto;
            } else {
                $backgroundImage = media_sideload_image($background_image_url, $contact_options['page_id'], '', 'id');
                $contact_options['agent_team_photo'] = $backgroundImage;
            }

            foreach ($contact as $key => $content) {
                switch ($key) {
                    case 'theme':
                        $contact_options[$key] = $productType . '-' . $content;
                        break;
                    case 'image_accent':
                        $contact_options['contact_image_accent'] = media_sideload_image(
                            $image_path . $contact->image_accent,
                            $contact_options['page_id'],
                            '',
                            'id'
                        );
                        break;
                    case 'agent_team_photo':
                        break;
                    case 'contact_form':
                        $form = get_page_by_path($content, OBJECT, 'wpcf7_contact_form');
                        if ($form) {
                            $contact_options['contact-theme-form'] = $form->ID;
                            update_option('contact-theme-form', $form->ID);
                        }
                        break;
                    case 'form_title':
                        $contact_options['contact-theme-form-title'] = sanitize_text_field($content);
                        update_option('contact-theme-form-title', sanitize_text_field($content));
                        break;
                    default:
                        $finalKey = $key === 'address_display' ? 'address-display' : $key;
                        $contact_options[$finalKey] = $content;
                        break;
                }
            }

            update_option('contact-theme', $productType . '-' . $contact->theme);

            if (function_exists('autoPopulateCustomPages')) {
                autoPopulateCustomPages('contact', $contact->theme, true);
            }

            update_option('contact_options', $contact_options);
            $generatedResponse = $generatedResponse . (! empty($generatedResponse) ? ' and' : '') . ' Contact';
        }

        return [
            'ok'      => true,
            'message' => empty($generatedResponse) ? 'No pages are generated' : $generatedResponse . ' successfully generated',
        ];
    }
}
