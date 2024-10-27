<?php
/**
 * Plugin Name: AI Content Copilot - Auto Social Media Posting
 * Text Domain: ai-content-copilot-auto-social-media-posting
 * Description: Uses OpenAI to generate content and auto-posts to Facebook. Includes Facebook login in the admin area.
 * Version:     1.0
 * Author:      AI GROWTH GUYS
 * Author URI:  https://aigrowthguys.com
 * License: GPL-2.0 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die('Direct script access disallowed.');

define('AI_CONTENT_TO_FB_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once AI_CONTENT_TO_FB_PLUGIN_DIR . 'includes/admin-settings.php';
// require_once AI_CONTENT_TO_FB_PLUGIN_DIR . 'includes/facebook-integration.php';
require_once AI_CONTENT_TO_FB_PLUGIN_DIR . 'includes/openai-integration.php';
