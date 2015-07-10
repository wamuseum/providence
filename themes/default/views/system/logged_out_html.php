<?php
/* ----------------------------------------------------------------------
 * app/views/system/logged_out_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
  AppController::getInstance()->removeAllPlugins();
?>
<html>
	<head>
		<title><?php print $this->request->config->get("app_display_name"); ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		
		<link href="<?php print $this->request->getThemeUrlPath(); ?>/css/login.css" rel="stylesheet" type="text/css" />
<?php
	print AssetLoadManager::getLoadHTML($this->request);
?>
<?php
	if (file_exists($this->request->getThemeDirectoryPath().'/css/wam-login.css')) {
		print '<link rel="stylesheet" href="'.$this->request->getThemeUrlPath().'/css/wam-login.css" type="text/css" media="screen" />
';
	}
	?>

		<script type="text/javascript">
			// initialize CA Utils
			jQuery(document).ready(function() { caUI.utils.disableUnsavedChangesWarning(true); });
		</script>
	</head>
	<body>
		<div align="center">
			<div id="loginBox">
				<div align="center">
					<img src="<?php print $this->request->getThemeUrlPath()."/graphics/logos/".$this->request->config->get('login_logo');?>" border="0">
				</div>
				<div id="systemTitle">
<?php 
					if ($va_notifications = $this->getVar('notifications')) {  
?>
						<p class="content"><?php foreach($va_notifications as $va_notification) { print $va_notification['message']."<br/>\n"; }; ?></p>
<?php
					}
?>
				</div><!-- end  systemTitle -->
				<div id="loginForm">
					<?php print caNavLink($this->request, _t("Login again"), 'loginAgainLink', 'system/auth', 'login', ''); ?>
				</div><!-- end loginForm -->
			</div><!-- end loginBox -->
		</div><!-- end center -->
	</body>
</html>