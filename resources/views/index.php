<?php
   use AIOS\AUTOPOPULATE\Helpers\helpers;
   $themes = Helpers::agentpro_themes();
   $apiStatus = Helpers::api_status();

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
                           <select name="aios_population_settings[theme]" id="">
                              <option value="#">Theme Not Supported</option>
                              <?php 
                                 $active_theme = get_option('template');
                                 foreach ($themes as $theme){  
                                    $themeName = sanitize_title($theme);
                                    $seleted = $active_theme == $themeName ? 'selected' : '';

                                    echo '<option value="'.$themeName.'" '.$seleted.'>'.$theme.'</option>';
                                 }
                              ?>
                              
                           </select>
                        </div>
                     </div>
                     <div class="wpui-col-md-9">
                        <a href="#" class="wpui-secondary-button text-uppercase aios-repopulate-widgets">Generate</a>
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