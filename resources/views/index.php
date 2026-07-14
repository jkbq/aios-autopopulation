<?php
use AIOS\AUTOPOPULATE\Helpers\Helpers;

$helpers = new Helpers();
$themes = $helpers->agentpro_themes();
$apiStatus = $helpers->api_status();
$cannedContentRows = Helpers::canned_content_rows();
$cannedByName = [];
foreach ($cannedContentRows as $row) {
    $cannedByName[$row['name']] = $row;
}
$cannedUnmodifiedTotal = array_sum(array_column($cannedContentRows, 'unmodified'));
$cannedEditedTotal = array_sum(array_column($cannedContentRows, 'edited'));
$cannedHasUnmodified = $cannedUnmodifiedTotal > 0;

?>
<div id="wpui-container-minimalist">
   <!-- BEGIN: Container -->
   <div class="wpui-container">
      <h4>AIOS Population</h4>
      <!-- BEGIN: Tabs -->
      <div class="wpui-tabs">
         <!-- BEGIN: Header -->
         <div class="wpui-tabs-header">
            <ul>
               <li><a data-id="settings" href="#" class="active-panel">Settings</a></li>
               <li><a data-id="logs" href="#" class="active-panel">Logs</a></li>
            </ul>
         </div>
         <!-- END: Header -->
         <!-- BEGIN: Body -->
         <div class="wpui-tabs-body">
            <!-- Loader -->

            <!-- Settings Tab -->
            <div data-id="settings" class="wpui-tabs-content settings" style="display: block;">
               <div class="wpui-tabs-title">Settings</div>
               <div class="wpui-tabs-container">

                  <div class="aios-settings-section">
                     <h5 class="aios-settings-section__title">Theme</h5>
                     <?php
                        $beforeTheme        = get_option('aios_autopopulation_theme');
                        $template           = get_option('template');
                        $active_child_theme = get_option('stylesheet');
                        $active_theme       = $template === 'aios-starter-theme' ? $active_child_theme : $template;
                        $wp_theme           = wp_get_theme($active_child_theme);
                        $theme_display_name = $wp_theme->get('Name') ?: $active_theme;
                        $theme_screenshot   = $wp_theme->get_screenshot();
                        $is_ready           = $active_theme !== $beforeTheme;

                        $currentThemeName = '';
                        foreach ($themes as $theme) {
                            if (sanitize_title($theme) === $active_theme) {
                                $currentThemeName = $theme;
                                break;
                            }
                        }
                        if ($currentThemeName === '') {
                            $currentThemeName = $theme_display_name;
                        }

                        $populatedThemeName = '';
                        if ($beforeTheme) {
                            foreach ($themes as $theme) {
                                if (sanitize_title($theme) === $beforeTheme) {
                                    $populatedThemeName = $theme;
                                    break;
                                }
                            }
                            if ($populatedThemeName === '') {
                                $populatedThemeName = $beforeTheme;
                            }
                        }

                        $badge_class = $is_ready ? 'is-ready' : 'is-current';
                        $badge_label = $is_ready ? 'Ready to generate' : 'Population up to date';
                     ?>
                     <div class="aios-theme-card">
                        <div class="aios-theme-card__screenshot">
                           <?php if ($theme_screenshot) : ?>
                           <img src="<?= esc_url($theme_screenshot) ?>"
                                alt="<?= esc_attr($theme_display_name) ?>">
                           <?php else : ?>
                           <div class="aios-theme-card__placeholder" aria-hidden="true"></div>
                           <?php endif; ?>
                        </div>
                        <div class="aios-theme-card__body">
                           <span class="aios-theme-card__badge <?= esc_attr($badge_class) ?>">
                              <?= esc_html($badge_label) ?>
                           </span>
                           <p class="aios-theme-card__name"><?= esc_html($currentThemeName) ?></p>
                           <p class="aios-theme-card__meta"><?= esc_html($active_theme) ?></p>
                           <?php if ($beforeTheme) : ?>
                           <p class="aios-theme-card__populated">
                              Last populated: <?= esc_html($populatedThemeName) ?>
                           </p>
                           <?php endif; ?>
                        </div>
                        <div class="aios-theme-card__actions">
                           <?php if ($is_ready) : ?>
                           <button type="button"
                                   class="aios-theme-generate-btn aios-repopulate-widgets">
                              Generate Theme Setup
                           </button>
                           <?php else : ?>
                           <p class="aios-theme-card__hint">
                              Theme setup is current. Switch to a different theme to run setup again.
                           </p>
                           <a href="<?= esc_url(admin_url('themes.php')) ?>"
                              class="aios-theme-link-btn">
                              Go to Themes
                           </a>
                           <?php endif; ?>
                        </div>
                     </div>
                  </div>

                  <div class="aios-settings-section">
                     <h5 class="aios-settings-section__title">Manage Content</h5>

                     <div class="aios-repopulate-toolbar">
                        <div class="aios-repopulate-toolbar__info">
                           <strong data-canned-count="all"><?= (int) $cannedUnmodifiedTotal ?></strong> unmodified canned items
                           <span class="aios-repopulate-toolbar__edited"
                                 data-canned-edited-total><?php if ( $cannedEditedTotal > 0 ) : ?> · <?= (int) $cannedEditedTotal ?> edited<?php endif; ?></span>
                        </div>
                        <button type="button"
                                class="aios-delete-canned-btn aios-delete-all-btn"
                                data-section="all"
                                data-name="canned content"
                                <?= ! $cannedHasUnmodified ? 'disabled' : '' ?>>
                           Delete unmodified canned content
                        </button>
                     </div>

                     <div class="aios-manage-table">
                     <div class="wpui-row wpui-row-box list-of-logs-heading aios-manage-table__row">
                        <div class="wpui-col-md-2">
                           <p><strong>Section</strong></p>
                        </div>
                        <div class="wpui-col-md-1">
                           <p><strong>Status</strong></p>
                        </div>
                        <div class="wpui-col-md-1">
                           <p><strong>Unmodified</strong></p>
                        </div>
                        <div class="wpui-col-md-1">
                           <p><strong>Edited</strong></p>
                        </div>
                        <div class="wpui-col-md-2">
                           <p><strong>Action</strong></p>
                        </div>
                        <div class="wpui-col-md-2">
                           <p><strong>Delete</strong></p>
                        </div>
                     </div>

                     <?php foreach ($apiStatus as $key => $api) :
                         $repop_status   = !empty($api['status']) ? 'Generated' : '—';
                         $repop_slug     = esc_attr(sanitize_title($key));
                         $repop_endpoint = esc_attr($api['endpoint']);
                         $repop_label    = esc_html($key);
                         $canned_row     = $cannedByName[$key] ?? null;
                         $canned_unmodified = $canned_row ? (int) $canned_row['unmodified'] : 0;
                         $canned_edited  = $canned_row ? (int) $canned_row['edited'] : 0;
                         $can_delete     = $canned_row && $canned_unmodified > 0;
                     ?>
                     <div class="wpui-row wpui-row-box aios-manage-table__row" id="repopulate-row-<?= $repop_slug ?>">
                        <div class="wpui-col-md-2">
                           <p><strong><?= $repop_label ?></strong></p>
                        </div>
                        <div class="wpui-col-md-1">
                           <p class="aios-repopulate-status <?= !empty($api['status']) ? 'is-generated' : '' ?>"
                              data-repop-status="<?= $repop_slug ?>"><?= $repop_status ?></p>
                        </div>
                        <div class="wpui-col-md-1">
                           <?php if ($canned_row) : ?>
                           <p class="aios-canned-count"
                              data-canned-count="<?= esc_attr($canned_row['slug']) ?>">
                              <?= (int) $canned_unmodified ?>
                           </p>
                           <?php else : ?>
                           <p>—</p>
                           <?php endif; ?>
                        </div>
                        <div class="wpui-col-md-1">
                           <?php if ($canned_row) : ?>
                           <p class="aios-canned-edited"
                              data-canned-edited="<?= esc_attr($canned_row['slug']) ?>">
                              <?= (int) $canned_edited ?>
                           </p>
                           <?php else : ?>
                           <p>—</p>
                           <?php endif; ?>
                        </div>
                        <div class="wpui-col-md-2 aios-repopulate-actions">
                           <button type="button"
                                   class="aios-repopulate-route-btn"
                                   data-endpoint="<?= $repop_endpoint ?>"
                                   data-name="<?= $repop_label ?>"
                                   data-slug="<?= $repop_slug ?>">
                              Repopulate
                           </button>
                        </div>
                        <div class="wpui-col-md-2 aios-delete-actions">
                           <?php if ($canned_row) : ?>
                           <button type="button"
                                   class="aios-delete-canned-btn"
                                   data-section="<?= esc_attr($canned_row['slug']) ?>"
                                   data-name="<?= esc_attr($canned_row['name']) ?>"
                                   data-slug="<?= $repop_slug ?>"
                                   <?= ! $can_delete ? 'disabled' : '' ?>>
                              Delete unmodified
                           </button>
                           <?php else : ?>
                           <p>—</p>
                           <?php endif; ?>
                        </div>
                     </div>
                     <?php endforeach; ?>
                     </div>
                  </div>

               </div>
            </div>

            <!-- Logs Tab -->
            <div data-id="logs" class="wpui-tabs-content logs" style="display: block;">
               <div class="wpui-tabs-title">Logs</div>

               <div class="wpui-tabs-container">
                  
                  <div class="wpui-row wpui-row-box list-of-logs-heading">
                     <div class="wpui-col-md-2">
                        <p><strong>API</strong></p>
                     </div>
                     <div class="wpui-col-md-1">
                        <p><strong>Status</strong></p>
                     </div>
                     <div class="wpui-col-md-1">
                        <p><strong>Date Populated</strong></p>
                     </div>
                  </div>
                  <?php
                     foreach ($apiStatus as $key => $api) {
                         $status = !empty($api['status']) ? 'Generated' : '';
                         $slug   = esc_attr(sanitize_title($key));
                         echo '<div class="wpui-row wpui-row-box" data-status-row="' . $slug . '">
                           <div class="wpui-col-md-2">
                              <p><strong>' . esc_html($key) . '</strong></p>
                           </div>
                           <div class="wpui-col-md-1" data-status-cell="' . $slug . '">
                              <p><strong>' . esc_html($status) . '</strong></p>
                           </div>
                           <div class="wpui-col-md-1" data-date-cell="' . $slug . '">
                              <p><strong>' . esc_html($api['date']) . '</strong></p>
                           </div>
                        </div>';
                     }
?>
               </div>
            </div>
         </div>
         <!-- END: Body -->
      </div>
      <!-- END: Tabs -->
   </div>
   <!-- END: Container -->
</div>
