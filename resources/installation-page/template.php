<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta id="viewport-tag" name="viewport" content="width=device-width, initial-scale=1"/>
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<meta name="robots" content="noindex,follow" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>






    <main id="aios-thankyou-wrap" class="aios-thankyou-wrap">
        <div class="aios-thankyou-inner">
            <h1 class="aios-thankyou-title">Thank You!</h1>
            <i class="ai-font-check aios-thankyou-icon" aria-hidden="true"></i>
            <div class="aios-thankyou-content">
                <p>Thank you very much for choosing <br> <strong>Descartes AgentPro Wordpress Theme.</strong></p><br>
                
				<div class="table table-header">
					<div class="table-cell">API Name</div>
					<div class="table-cell">Status</div>
					<div class="table-cell">Date Complete</div>
					<div class="table-cell">Action</div>
				</div>

				<div id="apiTableBody"></div>
	
				<a href="<?= do_shortcode('[blogurl]')?>" id="visit-homepage" style="display:none">Visit homepage</a>
                
            </div>
            <div class="aios-thankyou-copyright">
                Copyright&copy; <?= date( 'Y' ) ?>. All rights reserved. <br class="hidden-md hidden-lg">Real Estate Website Design by <a href="https://www.agentimage.com/" target="_blank" class="aios-thankyou-agentimage">Agent Image</a>
            </div>
        </div>
    </main>
    


    
	<?php wp_footer(); ?>
	
</body>
</html>