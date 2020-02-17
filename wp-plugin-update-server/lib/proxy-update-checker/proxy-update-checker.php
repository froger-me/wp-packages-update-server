<?php
/**
 * Proxy Update Checker Library 1.0
 * https://froger.me/
 *
 * Copyright 2018 Alexandre Froger
 * Released under the MIT license. See license.txt for details.
 */

require dirname(__FILE__) . '/Proxuc/Factory.php';
require dirname(__FILE__) . '/Proxuc/Autoloader.php';
new Proxuc_Autoloader();

//Register classes defined in this file with the factory.
Proxuc_Factory::setCheckerVersion('1.0');

Proxuc_Factory::addVersion('Vcs_PluginUpdateChecker', 'Proxuc_Vcs_PluginUpdateChecker', '1.0');
Proxuc_Factory::addVersion('Vcs_ThemeUpdateChecker', 'Proxuc_Vcs_ThemeUpdateChecker', '1.0');

Proxuc_Factory::setApiVersion('4.0');

Proxuc_Factory::addVersion('GitHubApi', 'Puc_v4p4_Vcs_GitHubApi', '4.4');
Proxuc_Factory::addVersion('BitBucketApi', 'Puc_v4p4_Vcs_BitBucketApi', '4.4');
Proxuc_Factory::addVersion('GitLabApi', 'Puc_v4p4_Vcs_GitLabApi', '4.4');