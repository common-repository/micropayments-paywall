<?php
// if ABSPATH is not defined, exit the script
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
* @link               https://wordpress.org/plugins/micropayments-paywall/
 * @since             1.0.0
 * @package           Trelis_Micropayments_Paywall
 *
 * @wordpress-plugin
 * Plugin Name:       Micropayments Paywall
 * Plugin URI:        https://wordpress.org/plugins/micropayments-paywall/
 * Description:       Paywall your posts with a micropayments paywall... 
 * Version:           4.0.2
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            ronantrelis
 * Contributors:      ronantrelis
 * Author URI:        https://www.Trelis.com
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       micropayments-paywall
 * Domain Path:       /languages
*/

// Plugin activation hook
register_activation_hook(__FILE__, 'trelismpw_activate');

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'trelismpw_deactivate');

// Meta box save handler
add_action('save_post_paywall', 'trelismpw_save_paywall_meta_box', 10, 2);

// Add paywall meta box to post editor
add_action('add_meta_boxes', 'trelismpw_add_meta_boxes');

// Plugin activation function
function trelismpw_activate() {
    // Create the paywall custom post type
    register_post_type('paywall', array(
        'labels' => array(
            'name' => __('Paywalls', 'micropayments-paywall'),
            'singular_name' => __('Paywall', 'micropayments-paywall')
        ),
        'public' => false,
        'show_ui' => true,
        'capability_type' => 'post',
        'supports' => array('title')
    ));

    // Create the paywall meta box
    add_meta_box(
        'trelismpw_meta_box',
        'Micropayments Paywall',
        'trelismpw_display_paywall_meta_box_content',
        'paywall',
        'side'
    );

    // Set the default paywall meta box values
    update_option('trelismpw_enabled', true);
    update_option('trelismpw_product_price', 0);

    // Create a new table for payment links
    trelismpw_create_payment_links_table();

    // Flush the rewrite rules to register the new custom post type
    flush_rewrite_rules();
}

// Plugin deactivation function
function trelismpw_deactivate() {
    // Remove the paywall custom post type
    unregister_post_type('paywall');

    // Remove the paywall meta box
    remove_meta_box('trelismpw_meta_box', 'paywall', 'side');

    // Delete the paywall meta box values
    delete_option('trelismpw_enabled');
    delete_option('trelismpw_product_price');

    // Flush the rewrite rules to unregister the custom post type
    flush_rewrite_rules();
}

// Hook the function to 'template_redirect' instead of 'the_content'
add_action('template_redirect', 'trelismpw_check_post_access');

// Update your function to check if the current request is for a single post page
function trelismpw_check_post_access() {
    // If this isn't a single post page, return early
    if (!is_single()) {
        return;
    }

    global $post;

    $paywall_enabled = get_post_meta($post->ID, '_trelismpw_enabled', true) === 'true';

    if ($paywall_enabled) {
        $user_id = get_current_user_id();

        if ($user_id !== 0) { // Checks if user is logged in
            $user_access = get_user_meta($user_id, 'trelismpw_access', true);

            if ($user_access && in_array($post->ID, $user_access)) {
                return; // User has access, display the post content
            }
        }

        // User does not have access, display the paywall content and button
        add_filter('the_content', 'trelismpw_hide_paywalled_content');
    }
}

function trelismpw_enqueue_styles() {
    wp_enqueue_style( 'paywall-style', esc_url(plugin_dir_url(__FILE__) . 'assets/css/paywall-style.css') );
}

add_action( 'wp_enqueue_scripts', 'trelismpw_enqueue_styles' );

function trelismpw_hide_paywalled_content() {
    $product_price = get_post_meta(get_the_ID(), '_trelismpw_product_price', true);
    $lifetime_price = get_option('lifetime_access_price');

    $stripe_enabled = get_option('stripe_enabled', false) === 'on';
    $stripe_api_key = get_option('stripe_api_key', '');
    $stripe_api_secret = get_option('stripe_api_secret', '');
    
    $stripe_payment_link = ''; // Initialize the variable

    if ($stripe_enabled && $stripe_api_key && $stripe_api_secret) {
        $stripe_payment_link = stripe_generate_payment_link(get_the_ID(), $product_price);
    } else {
        $stripe_enabled = false;
    }    

    // If neither Stripe nor Trelis is enabled (or if the API keys are missing), return the error HTML
    if (!$stripe_enabled) {
        return '
        <div class="micropayments-paywall">
            <div class="paywall-message">
                <h3 class="paywall-title">Paywalled Content</h3>
                <p class="paywall-text">Payment methods are not correctly configured. Contact the site owner.</p>
            </div>
        </div>
        ';
    }        

    if (is_user_logged_in()) {
    
        // Section for buying this post
        $buy_post_section = '<div class="buy-post-section paywall-button-container">';
        $buy_post_section .= '<h4>Buy This Post:</h4>';
        
        if ($stripe_enabled) {
            $buy_post_section .= '<button class="micropayments-paywall-button first-button" onclick="window.location.href=\'' . esc_url($stripe_payment_link) . '\'">Card Payment - $' . $product_price . '</button>';
        } else {
            $buy_post_section .= '<p>Payment is not configured. Please contact the site owner.</p>';
        }
        $buy_post_section .= '</div>';
    
        $button_html = $buy_post_section;

        if ($lifetime_access_enabled) {
            // Section for lifetime access to all posts
            $all_access_section = '<div class="all-access-section">';
            $all_access_section .= '<h4 class="center-text">Lifetime Access to All Posts:</h4>';
            $all_access_section .= '<button class="micropayments-paywall-button center-button" onclick="window.location.href=\'' . esc_url($stripe_lifetime_access_payment_link) . '\'">Card Payment - $' . $lifetime_price . '</button>';
            $all_access_section .= '</div>';
        
            $button_html = $button_html . $all_access_section;
        }        
    
    } else {
        $login_url = esc_url(wp_login_url(get_permalink()));
        $button_html = '<button class="micropayments-paywall-button" onclick="window.location.href=\'' . esc_url($login_url) . '\'">Log in to purchase</button>';
    }    
    
    return '
    <div class="micropayments-paywall">
        <div class="paywall-message">
            <h3 class="paywall-title">Paywalled Content</h3>
            <p class="paywall-text">Price: $' . $product_price . '</p>
            <ul class="paywall-steps">
                <li class="paywall-step">Already paid? Wait 10 secs & refresh the page!</li>
            </ul>
        </div>
        ' . $button_html . '
    </div>
    ';
}

// Create a new table for payment links
function trelismpw_create_payment_links_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trelismpw_payment_links';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('stripe', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'trelismpw_stripe_webhook',
        'permission_callback' => '__return_true',
    ));
});

function trelismpw_stripe_webhook() {
    $payload_raw = @file_get_contents('php://input');

    // Validate and sanitize the payload input
    if (!is_string($payload_raw)) {
        http_response_code(400);
        exit();
    }

    // no sanitisation because this doesns't risk attacks and may mess with parsing of the response.
    $payload = $payload_raw;

    $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field($_SERVER['HTTP_STRIPE_SIGNATURE']) : '';

    $timestamp = '';
    $received_signatures = [];

    // Step 1: Extract the timestamp and signatures from the header
    $elements = explode(',', $sig_header);
    foreach ($elements as $element) {
        list($prefix, $value) = explode('=', $element, 2);
        if ($prefix == 't') {
            // Ensure the received timestamp is numeric before assigning
            if (!is_numeric($value)) {
                http_response_code(400);
                exit();
            }
            $timestamp = $value;
        } else if ($prefix == 'v1') {
            // Ensure the received signature is a string before adding to the list
            if (!is_string($value)) {
                http_response_code(400);
                exit();
            }
            $received_signatures[] = sanitize_text_field($value);
        }
    }

    // Step 2: Prepare the signed_payload string
    $signed_payload = $timestamp . '.' . $payload;

    $endpoint_secret = get_option('stripe_webhook_secret', '');

    // Step 3: Determine the expected signature
    $expected_signature = hash_hmac('sha256', $signed_payload, $endpoint_secret);

    $signature_verified = false;

    // Step 4: Compare the signatures
    foreach ($received_signatures as $received_signature) {
        if (hash_equals($expected_signature, $received_signature)) {
            $signature_verified = true;
            break;
        }
    }

    if (!$signature_verified) {
        // Invalid signature.
        http_response_code(400);
        exit();
    }

    // If the signature is valid, parse the payload into an event object
    $event = json_decode($payload);

    // Validate that the session object exists and is of the correct format
    if (!isset($event->data->object) || !is_object($event->data->object)) {
        http_response_code(400);
        exit();
    }

    $session = $event->data->object;
    $payment_status = $session->payment_status;
    $session_id = $session->id; // Set the session_id to the session id.

    // // Send email with webhook response
    // $to = 'DEBUG_EMAIL';
    // $subject = 'Stripe Webhook Response';
    // $message = 'Session ID from Webhook: ' . $session_id; // Initialize $message here
    // $headers = array('Content-Type: text/plain; charset=UTF-8');
    // wp_mail($to, $subject, $message, $headers);

    // Handle the checkout.session.completed event.
    if ($event->type == 'checkout.session.completed') {
        if ($payment_status === 'paid') {

            global $wpdb;
            
            // Check the payment links table
            $payment_table_name = $wpdb->prefix . 'trelismpw_payment_links';
            $payment_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $payment_table_name WHERE session_id = %s", $session_id));

            if ($payment_row) {
                $user_id = $payment_row->user_id;
                $post_id = $payment_row->post_id;
                trelismpw_grant_post_access($post_id, $user_id);
            }
        }
    }

    http_response_code(200);
}

function stripe_generate_payment_link($post_id, $product_price) {
    // Retrieve the Stripe API key from the options.
    $stripe_api_secret = get_option('stripe_api_secret', '');

    // Validate the secret key
    if (!is_string($stripe_api_secret) || empty($stripe_api_secret)) {
        // Handle invalid secret key
    }

    // Set the Stripe API endpoint URL.
    $endpoint = esc_url("https://api.stripe.com/v1/checkout/sessions");

    // Validate and sanitize the product price
    if (!is_numeric($product_price)) {
        // Handle invalid product price
    }
    $product_price = floatval($product_price) * 100;

    // Validate and sanitize the post title
    $post_title = get_the_title($post_id);
    if (!is_string($post_title)) {
        // Handle invalid post title
    }
    $post_title = sanitize_text_field($post_title);
    $post_title = html_entity_decode($post_title, ENT_QUOTES, 'UTF-8');

    // Set the parameters.
    $params = array(
        'payment_method_types' => array('card'),
        'line_items' => array(
            array(
                'price_data' => array(
                    'currency' => 'usd',
                    'product_data' => array(
                        'name' => $post_title,
                    ),
                    'unit_amount' => $product_price,
                ),
                'quantity' => 1,
            ),
        ),
        'mode' => 'payment',
        'success_url' => esc_url(get_permalink($post_id) . '?session_id={CHECKOUT_SESSION_ID}'),
        'cancel_url' => esc_url(get_permalink($post_id)),
    );

    // Sanitize success_url and cancel_url
    $params['success_url'] = esc_url($params['success_url']);
    $params['cancel_url'] = esc_url($params['cancel_url']);

    // Send the request.
    $response = wp_remote_post($endpoint, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($stripe_api_secret . ':'),
        ),
        'body' => http_build_query($params),
    ));

    // Check for errors.
    if (is_wp_error($response)) {
        return 'Stripe API request error: ' . esc_html($response->get_error_message());
    }

    // Decode the response.
    $result = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the response contains a session ID.
    if (isset($result['id']) && is_string($result['id'])) {
        $session_id = $result['id'];
        $user_id = get_current_user_id();
        $post_id = intval($post_id);
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'trelismpw_payment_links',
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'post_id' => $post_id,
            )
        );

        // Grab the payment url
        if (isset($result['url']) && is_string($result['url'])) {
            $payment_link = $result['url'];
            // Return the payment link.
            return esc_url($payment_link);
        }
    } else {
        // Return an error message.
        if (isset($result['error'])) {
            return wp_kses_post(json_encode($result['error']));
        }
    }
}

function trelismpw_grant_post_access($post_id, $user_id) {
    $user_access = get_user_meta($user_id, 'trelismpw_access', true);
    $post_access = get_post_meta($post_id, 'trelismpw_access', true);

    if (!$user_access) {
        $user_access = array();
    }

    if (!$post_access) {
        $post_access = array();
    }

    if (!in_array($post_id, $user_access)) {
        $user_access[] = $post_id;
    }

    if (!in_array($user_id, $post_access)) {
        $post_access[] = $user_id;
    }

        // // Send an email with user_access and post_access values
        // $to = 'DEBUG_EMAIL';
        // $subject = 'Trelis Grant Post Access Debug';
        // $message = "Post ID: {$post_id}\r\n";
        // $message .= "User ID: {$user_id}\r\n";
        // $message .= "User Access: " . implode(', ', $user_access) . "\r\n";
        // $message .= "Post Access: " . implode(', ', $post_access);
        // $headers = array('Content-Type: text/plain; charset=UTF-8');
        // wp_mail($to, $subject, $message, $headers);

    update_user_meta($user_id, 'trelismpw_access', $user_access);
    update_post_meta($post_id, 'trelismpw_access', $post_access);
}

function trelismpw_save_paywall_meta_box($post_id, $post) {
    // Verify the nonce before proceeding.
    if (!isset($_POST['trelismpw_nonce']) || !wp_verify_nonce(sanitize_key($_POST['trelismpw_nonce']), basename(__FILE__))) {
        return $post_id;
    }

    // Check if this is an autosave or a revision.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // Sanitize user input.
    $enabled = isset($_POST['trelismpw_enabled']) ? sanitize_text_field($_POST['trelismpw_enabled']) : 'false';
    // Validate that $enabled is 'true' or 'false'
    $enabled = in_array($enabled, ['true', 'false']) ? $enabled : 'false'; 

    // Sanitize user input.
    $product_price = isset($_POST['trelismpw_product_price']) ? sanitize_text_field($_POST['trelismpw_product_price']) : 0;
    $product_price = is_numeric($product_price) ? floatval($product_price) : 0;
    // Validate that $product_price is a positive number or 0
    $product_price = $product_price >= 0 ? $product_price : 0; 

    // Update the post meta.
    update_post_meta($post_id, '_trelismpw_enabled', $enabled);
    update_post_meta($post_id, '_trelismpw_product_price', $product_price);
}

add_action('save_post', 'trelismpw_save_paywall_meta_box', 10, 2);

function trelismpw_add_meta_boxes() {
    add_meta_box(
        'trelismpw_meta_box',
        'Micropayments Paywall Settings',
        'trelismpw_display_paywall_meta_box_content',
        'post',
        'normal',
        'default'
    );
}

function trelismpw_display_paywall_meta_box_content($post) {
    // Retrieve the paywall settings from the post meta.
    $post_mpw_enabled = get_post_meta($post->ID, '_trelismpw_enabled', true) === 'true';
    $product_price = floatval(get_post_meta($post->ID, '_trelismpw_product_price', true));

    // Display the paywall settings fields.
    wp_nonce_field(basename(__FILE__), 'trelismpw_nonce');
    ?>
    <p>
        <label for="trelismpw_enabled">Enable paywall:</label>
        <input type="checkbox" id="trelismpw_enabled" name="trelismpw_enabled" value="true" <?php checked($post_mpw_enabled, true); ?>>
    </p>
    <p>
        <label for="trelismpw_product_price">Product price (USD):</label>
        <input type="number" step="0.01" id="trelismpw_product_price" name="trelismpw_product_price" value="<?php echo $product_price; ?>">
    </p>
    <?php
}

function trelismpw_settings_menu() {
    $icon_url = plugins_url('assets/menu-icon.png', __FILE__);

    add_menu_page(
        'Micropayments Paywall Settings',
        'Paywall',
        'manage_options',
        'micropayments-paywall-settings',
        'trelismpw_settings_page',
        'dashicons-remove',
        5 // Position
    );

    // Enqueue custom CSS for the menu item
    add_action('admin_enqueue_scripts', 'trelismpw_menu_styles');
}

function trelismpw_menu_styles() {
    wp_enqueue_style('trelismpw_menu_styles', plugins_url('assets/css/menu-styles.css', __FILE__));
}

add_action('admin_menu', 'trelismpw_settings_menu');

function trelismpw_settings_init() {
    // Stripe settings
    register_setting('micropayments-paywall', 'stripe_enabled');
    register_setting('micropayments-paywall', 'stripe_api_key');
    register_setting('micropayments-paywall', 'stripe_api_secret');
    register_setting('micropayments-paywall', 'stripe_webhook_secret');
}

add_action('admin_init', 'trelismpw_settings_init');

function trelismpw_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }    
        // Retrieve the Stripe API key, API secret, and webhook secret settings.
        $stripe_api_key = get_option('stripe_api_key', '');
        $stripe_api_secret = get_option('stripe_api_secret', '');
        $stripe_webhook_secret = get_option('stripe_webhook_secret', '');
        $stripe_enabled = get_option('stripe_enabled', false);
        $stripe_enabled = ($stripe_enabled === 'on') ? true : false; // Convert 'on' to true, other values to false
    
        // Retrieve the Stripe webhook URL.
        $stripe_webhook_url = rest_url('stripe/webhook');

    // Display the Micropayments Paywall settings page.
    ?>
    <div class="wrap">
        <h1><?php _e('Micropayments Paywall Settings', 'micropayments-paywall'); ?></h1>
        <p><?php _e('For help getting set up, visit the <a href="https://docs.trelis.com/products/micropayments-paywall" target="_blank" rel="noopener noreferrer">Micropayments Paywall documentation</a>.', 'micropayments-paywall'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('micropayments-paywall'); ?>
            <?php do_settings_sections('micropayments-paywall'); ?>

            <h2><?php _e('Stripe Payments Gateway', 'micropayments-paywall'); ?></h2>
            <p><?php _e('Get your API credentials from the dashboard at <a href="https://www.stripe.com">Stripe.com</a>.', 'micropayments-paywall'); ?></p>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="stripe_enabled"><?php _e('Enable Stripe', 'micropayments-paywall'); ?></label></th>
                        <td><input type="checkbox" id="stripe_enabled" name="stripe_enabled" <?php checked($stripe_enabled, true); ?>></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Stripe Webhook URL:', 'micropayments-paywall'); ?></th>
                        <td><code><?php echo esc_html($stripe_webhook_url); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_api_key"><?php _e('Stripe API key', 'micropayments-paywall'); ?></label></th>
                        <td><input type="text" id="stripe_api_key" name="stripe_api_key" value="<?php echo esc_attr($stripe_api_key); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_api_secret"><?php _e('Stripe API secret', 'micropayments-paywall'); ?></label></th>
                        <td><input type="password" id="stripe_api_secret" name="stripe_api_secret" value="<?php echo esc_attr($stripe_api_secret); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="stripe_webhook_secret"><?php _e('Stripe Webhook secret', 'micropayments-paywall'); ?></label></th>
                        <td><input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Save Settings', 'micropayments-paywall'), 'primary', 'submit', false); ?>
        </form>

        <h2><?php _e('Get the Premium Plugin', 'micropayments-paywall'); ?></h2>
        <p><?php _e('Allow customers to purchase lifetime access to all posts', 'micropayments-paywall'); ?></p>
        <p><?php _e('Price: €9.99 one-off payment', 'micropayments-paywall'); ?></p>
        <a href="https://buy.stripe.com/5kA9Bm5tPdmb4ow6oy" class="button button-primary" target="_blank"><?php _e('Purchase Now', 'micropayments-paywall'); ?></a>

    </div>
<?php
}