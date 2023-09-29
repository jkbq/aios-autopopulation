<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta id="viewport-tag" name="viewport" content="width=device-width, initial-scale=1"/>
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <main id="aios-thankyou-wrap" class="aios-thankyou-wrap">
        <div class="aios-thankyou-inner">
            <h1 class="aios-thankyou-title">Thank You!</h1>
            <i class="ai-font-check aios-thankyou-icon" aria-hidden="true"></i>
            <div class="aios-thankyou-content">
                <p>Thank you very much for choosing <br> <strong>Descartes AgentPro Wordpress Theme.</strong></p><br>
                
                
            </div>
            <div class="aios-thankyou-copyright">
                Copyright&copy; <?= date( 'Y' ) ?>. All rights reserved. <br class="hidden-md hidden-lg">Real Estate Website Design by <a href="https://www.agentimage.com/" target="_blank" class="aios-thankyou-agentimage">Agent Image</a>
            </div>
        </div>
    </main>
    

	<!-- Your existing HTML content -->

  <div id="buttons-container"></div>
<!-- Your existing HTML content -->


    <div class="aios-starter-theme-landing-page">
		<div class="landing-page-wrapper">
			<div class="aios-landing-holder">
				<div class="landing-page-logo">
					<a href="#"><i class="ai-font-agentimage-logo"></i></a>
				</div>
				
				<?php if ( ! get_option( 'aios_countdown_timer' ) ) : ?>
				<div class="landing-page-slogan">
					<h2>Thank you for choosing Agent Image!</h2>
					<p style="display: none;" class="aios-thankyou-countdown-timer" data-timer-success="Your theme setup is already done. Please click the link below to proceed.">Please wait for <span class="aios-thankyou-countdown-timer"></span> as we setup your theme files...</p><br><br>
					<p><a href="<?= do_shortcode('[blogurl]'); ?>" style="display: none;" class="aios-thankyou-link">Visit your Homepage</a></p>
				</div>
				<?php 
                    update_option( 'aios_countdown_timer', 'done' );
                    else: 
                ?>
				<div class="landing-page-slogan done">
					<h2>Thank you for choosing Agent Image!</h2>
					<p>Your theme setup is already done. Please click the link below to proceed.</p><br><br>
					<p><a href="<?= do_shortcode('[blogurl]'); ?>">Visit your Homepage</a></p>
				</div>
				<?php endif; ?>
				
				<div class="landing-page-phone">
					<div class="landing-phone">
						<span>Sales</span>
						<a href="tel:1.800.979.5799"><i class="ai-font-phone"></i>1.800.979.5799</a>
					</div>
					<div class="landing-phone">
						<span>Support</span>
						<a href="tel:1.877.317.4111"><i class="ai-font-phone"></i>1.877.317.4111</a>
					</div>
				</div>				
			</div>

		</div>

		<div class="landing-page-footer">
			<h3 class="cws-title"><span>Connect with us</span></h3>
			<div class="cws-media">
				<a href="https://www.facebook.com/AgentImage/" target="_blank" class="cws-facebook"><i class="ai-font-facebook"></i></a>
				<a href="https://twitter.com/agentimage/" target="_blank" class="cws-twitter"><i class="ai-font-twitter"></i></a>
				<a href="https://www.instagram.com/agentimage/" target="_blank" class="cws-instagram"><i class="ai-font-instagram"></i></a>
				<a href="https://www.linkedin.com/company/agent-image/" target="_blank" class="cws-linkedin"><i class="ai-font-linkedin"></i></a>
				<a href="https://www.youtube.com/channel/UCi61s5-PpJSTqVMy-ed92XA" target="_blank" class="cws-youtube"><i class="ai-font-youtube"></i></a>
				<a href="https://www.pinterest.com/agentimage/" target="_blank" class="cws-pinterest"><i class="ai-font-pinterest"></i></a>
				<a href="https://www.yelp.com/biz/agent-image-el-segundo" target="_blank" class="cws-yelp"><i class="ai-font-yelp"></i></a>
			</div>
			<p>Copyright <?php echo date('Y')?> Agent Image, a Division of The Design People, Inc. All Rights Reserved.</p>
		</div>

	</div>
    
	<?php wp_footer(); ?>
	
</body>
</html>