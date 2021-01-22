<?php

namespace nicomartin\ProgressiveWordPress;

/*
Plugin Name: Progressive WordPress (PWA)
Plugin URI: https://github.com/SayHelloGmbH/progressive-wordpress
Description: Turn your website into a Progressive Web App and make it installable, offline ready and send push notifications.
Author: Nico Martin
Author URI: https://nicomartin.ch
Version: 2.2.-1
Text Domain: progressive-wp
Domain Path: /languages
 */

global $wp_version;
if (version_compare($wp_version, '4.7', '<') || version_compare(PHP_VERSION, '7.2.1', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        // translators: Dependency warning
        echo sprintf(
            __(
                '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.',
                'pwp'
            ),
            'Advanced WPPerformance',
            '5.3',
            '4.7',
            PHP_VERSION,
            $GLOBALS['wp_version']
        );
        echo '</p></div>';
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    });

    add_action('admin_init', function () {
        deactivate_plugins(plugin_basename(__FILE__));
    });

    return;
} else {
    require_once 'src/Helpers.php';
    require_once 'src/Plugin.php';
    function pwpGetInstance(): Plugin
    {
        return Plugin::getInstance(__FILE__);
    }

    pwpGetInstance();

    require_once 'src/Settings.php';
    pwpGetInstance()->settings = new Settings();
    pwpGetInstance()->settings->run();

    require_once 'src/AdminPage.php';
    pwpGetInstance()->admin_page = new AdminPage();
    pwpGetInstance()->admin_page->run();

    require_once 'src/Assets.php';
    pwpGetInstance()->assets = new Assets();
    pwpGetInstance()->assets->run();
} // End if().
