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

    <main id="aios-installation" class="aios-installation">

        <div class="aios-installation__info">
			<div class="aios-installation__logo">
				<i class="ai-font-agentimage-logo"></i>
			</div>
            <h1 class="aios-installation__title">Thank you for choosing Agent Image!</h1>
            <div class="aios-installation__content">
                <p>Please wait for<strong> as we setup your theme files...</strong></p><br>
                
				<div class="aios-installation__table">
					<div class="aios-installation__table--header">
						<div class="aios-installation__table--cell">API Name</div>
						<div class="aios-installation__table--cell">Status</div>
						<div class="aios-installation__table--cell">Date Complete</div>
					</div>

					<div id="apiTableBody"></div>
				</div>
	
				<a href="<?= do_shortcode('[blogurl]')?>" id="visit-homepage">Visit homepage</a>
            </div>
        </div>
		<div class="aios-installation__footer">
			<h3 class="aios-installation__footer--title">Connect with us</h3>
			<div class="aios-installation__footer--smi">
				<a href="https://www.facebook.com/AgentImage/" target="_blank" class="cws-facebook"><i class="ai-font-facebook"></i></a>
				<a href="https://twitter.com/agentimage/" target="_blank" class="cws-twitter"><i class="ai-font-twitter"></i></a>
				<a href="https://www.instagram.com/agentimage/" target="_blank" class="cws-instagram"><i class="ai-font-instagram"></i></a>
				<a href="https://www.linkedin.com/company/agent-image/" target="_blank" class="cws-linkedin"><i class="ai-font-linkedin"></i></a>
				<a href="https://www.youtube.com/channel/UCi61s5-PpJSTqVMy-ed92XA" target="_blank" class="cws-youtube"><i class="ai-font-youtube"></i></a>
				<a href="https://www.pinterest.com/agentimage/" target="_blank" class="cws-pinterest"><i class="ai-font-pinterest"></i></a>
				<a href="https://www.yelp.com/biz/agent-image-el-segundo" target="_blank" class="cws-yelp"><i class="ai-font-yelp"></i></a>
			</div>
			<p>Copyright <?php echo date('Y')?> <a href="https://agentimage.com/" target="_blank">Agent Image</a> All Rights Reserved.</p>
		</div>
    </main>    
	<?php wp_footer(); ?>
	
</body>
</html>