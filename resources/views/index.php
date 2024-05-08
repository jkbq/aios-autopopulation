<?php
   use AIOS\AUTOPOPULATE\Helpers\Helpers;
   $helpers = new Helpers();
   $themes = $helpers->agentpro_themes();
   $apiStatus = $helpers->api_status();

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

            <!-- Contents -->
            <div data-id="settings" class="wpui-tabs-content settings" style="display: block;">
               <div class="wpui-tabs-title">Settings</div>
               <div class="wpui-tabs-container">
                  <!-- BEGIN: Row Box -->
                  <div class="wpui-row wpui-row-box">
                     <div class="wpui-col-md-3">
                        <div class="form-group">

                           <?php
                              $beforeTheme = get_option('aios_autopopulation_theme');
                              $active_theme = get_option('template');
                              $active_child_theme = get_option('stylesheet');

                              $active_theme = $active_theme === 'aios-starter-theme' ?  $active_child_theme : $active_theme;
                              
                              $currentThenme = '';
                              foreach ($themes as $theme){  

                               
                                 $themeName = sanitize_title($theme);

                             

                                 $currentThenme .= $active_theme == $themeName ? $theme : '';
                              }

                           ?>
                           <input type="text" disabled id="selectedTheme" name="aios_population_settings[theme]" value="<?= $currentThenme ?>">
                           
                           </select>
                           <label for="selectedTheme">Current Active Theme</label>
                        </div>
                     </div>
                     <div class="wpui-col-md-9">
                        <?php if($active_theme != $beforeTheme) : ?>
                        <a href="#" class="wpui-default-button text-uppercase aios-repopulate-widgets">Generate</a>
                        <?php else :?>
                           <p>Please Download or Activate your new theme <a href="/wp-admin/themes.php">here</a></p>
                        <?php endif; ?>
                     </div>
                  </div>
                  <!-- END: Row Box -->
               </div>
            </div>

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
                     foreach($apiStatus as $key=>$api){
                        $status = !empty($api['status']) ? 'Generated' : '';
                        echo '<div class="wpui-row wpui-row-box">
                           <div class="wpui-col-md-2">
                              <p><strong>'.$key.'</strong></p>
                           </div>
                           <div class="wpui-col-md-1">
                              <p><strong>'.$status.'</strong></p>
                           </div>
                           <div class="wpui-col-md-1">
                              <p><strong>'.$api['date'].'</strong></p>
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