<?php 
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://fundyourfx.com
 * @since             1.0.0
 * @package           Fyfx_PayNext
 *
 * @wordpress-plugin
 * Plugin Name:       A - FYFX x PayNext Gateway WooCommerce
 * Plugin URI:        https://fundyourfx.com
 * Description:       FYFX x PayNext Payment Gateway for WooCommerce
 * Version:           1.2.5
 * Author:            Ardi JM (Editor) | Original By PayNext 
 * Author URI:        https://fundyourfx.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fyfx-payNext
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

include_once('woo-check-card-class.php');

if (!defined('ABSPATH'))
    exit;
add_action('plugins_loaded', 'woocommerce_paynext_init', 0);

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

/**
* Check for the existence of WooCommerce and any other requirements
*/
function fyfx_paynext_check_requirements() {
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        return true;
    } else {
        add_action( 'admin_notices', 'fyfx_paynext_missing_wc_notice' );
        return false;
    }
}

/**
* Display a message advising WooCommerce is required
*/
function fyfx_paynext_missing_wc_notice() { 
    $class = 'notice notice-error';
    $message = __( 'FYFX Propfirm User requires WooCommerce to be installed and active.', 'fyfx-propfirm-user' );
 
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}

add_action( 'plugins_loaded', 'fyfx_paynext_check_requirements' );

function filter_action_fyfx_paynext_links( $links ) {
     $links['settings'] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paynext' ) . '">' . __( 'Settings', 'fyfx-propfirm-user' ) . '</a>';
     $links['support'] = '<a href="https://portal.online-epayment.com/developers.do"  target="_blank">' . __( 'Doc', 'fyfx-propfirm-user' ) . '</a>';
     // if( class_exists( 'Fyfx_Payment' ) ) {
     //  $links['upgrade'] = '<a href="https://fundyourfx.com">' . __( 'Upgrade', 'fyfx-propfirm-user' ) . '</a>';
     // }
     return $links;
}
add_filter( 'plugin_action_links_fyfx-payNext/fyfx-payNext.php', 'filter_action_fyfx_paynext_links', 10, 1 );

function woocommerce_paynext_init()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;
    /**
     * Gateway class
     */
     
 
     
    class WC_paynext extends WC_Payment_Gateway
    {
        
        public function __construct()
        {
            // Go wild in here
            $this->id           = 'paynext';
            $this->method_title = __('FYFX x PayNext Gateway');
            $this->method_description = __('FYFX Plugin Payment Woocomerce using API payNext Gateway');
            $this->has_fields   = true;
            $this->init_form_fields();
            $this->init_settings();
          
          
          
           if ($this->settings['logo']=="yes"){         
                $this->icon = woocommerce_plugin_url_paynext . '/img/visamastjcb.png';
            }
            
            
            
            $this->title            = $this->settings['title'];
            
            $this->description      = $this->settings['description'];
            
            $this->api_token        = $this->settings['api_token'];
            $this->website_id         = $this->settings['website_id'];

            $this->response_method = $this->settings['response_method'];
            
            $this->transaction_url        = $this->settings['transaction_url'];
            
            $this->paynext_type   = $this->settings['paynext_type'];
            $this->validation_3ds   = $this->settings['validation_3ds'];
            $this->status_completed = $this->settings['status_completed'];
            $this->status_cancelled = $this->settings['status_cancelled'];
            $this->status_pending   = $this->settings['status_pending'];
            $this->checkout_language   = $this->settings['checkout_language'];
            $this->notify_url       = home_url('/wc-api/WC_paynext');
            $this->msg['message']   = "";
            $this->msg['class']     = "";
          //  add_action('woocommerce_api_wc_tasaction_status', array(  $this, 'check_trasaction'));
            
            
            //add_action('woocommerce_api_wc_paynext', array(
               // $this,
                //'check_paynext_response'
            //));
            
            add_action('valid-paynext-request', array(
                $this,
                'successful_request'
            ));
            
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            add_action( 'admin_enqueue_scripts', 'admin_paynext_load_scripts' );
            
           
            
            
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                    $this,
                    'process_admin_options'
                ));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(
                    &$this,
                    'process_admin_options'
                ));
            }
            add_action('woocommerce_receipt_paynext', array(
                $this,
                'receipt_page'
            ));
        }
        

        
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', ''),
                    'type' => 'checkbox',
                    'label' => __('Payment Module.', ''),
                    'default' => 'no'
                ),

                'response_method' => array(
                    'title' => __('Methods', ''),
                    'default' => 'wp_remote_post',
                    'type' => 'select',
                    'options' => array(
                        'wp_remote_post' => __('WP_REMOTE_POST', ''),
                        'curl' => __('CURL', '')
                    )
                ),
                
                
                'website_id' => array(
                    'title' => __('WEBSITE ID', ''),
                    'type' => 'text',
                    'description' => __('', '')
                ),
                'api_token' => array(
                    'title' => __('WEBSITE API TOKEN', ''),
                    'type' => 'text',
                    'description' => __('.', '')
                ),

                'transaction_url' => array(
                    'title' => __('TRANSACTION URL', ''),
                    'type' => 'text',
                    'description' => __('', '')
                ),

                'additional_option' => array(
                    'title' => __('Additional Options', ''), 
                    'default' => 'Additional Options',
                    'type' => 'title',
                    
                    //'type' => 'button',
                    'custom_attributes' => array(
                        //'onclick' => "woocommerce_addvalf();",
                    ),
                    'css'  => 'color: #032279;text-align:center;font-weight:bold;font-size:16px;padding:10px 0;height:50px;',
                    'class' => 'button-secondary addButtonId_paynext',
                    'id'       => 'woocommerce_addButtonId_paynext',
                    'desc' => __('The following options are default.', '')
                ),
                
                'paynext_type' => array(
                    'title' => __('Payment Type', ''),
                    'default' => 'host',
                    'type' => 'select',
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'card' => __('Card Payment Gateway (Direct by Curl)', ''),
                        'host' => __('Payment Gateway (Re-Direct)', '')
                    )
                ),

                'validation_3ds' => array(
                    'title' => __('3DS Validation', ''),
                    'default' => 'no',
                    'type' => 'select',
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'yes' => __('3DS Validation A', ''),
                        'no' => __('3DS Validation B', '')
                    )
                ),

                'title' => array(
                    'title' => __('Title:', ''),
                    'type' => 'text',
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'description' => __('Process secure payment by Credit Card & Crypto eWallet', ''),
                    'default' => __('Credit Card & Crypto eWallet', '')
                ),
                'logo' => array(
                    'title' => __('Display Icon:', ''),
                    'type' => 'Checkbox', 
                        'class' => 'ao1_paynext dispIcon1_paynext',
                        'css' => '',
                    'description' => __('This controls the title which the user sees during checkout.', ''),
                    'default' => __('paynext', '')
                ),
                'description' => array(
                    'title' => __('Description:', ''),
                    'type' => 'textarea', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'description' => __('This controls the description which the user sees during checkout.', ''),
                    'default' => __('Pay  Credit card  through paynext Secure Servers.', '')
                ),
                'status_completed' => array(
                    'title' => __('If Completed/Successfull/Test Transaction', ''),
                    'default' => 'completed',
                    'type' => 'select', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                ),
                'status_cancelled' => array(
                    'title' => __('If Cancelled/Failed', ''),
                    'default' => 'cancelled',
                    'type' => 'select', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                ),
                'status_pending' => array(
                    'title' => __('If Any error/ No Response', ''),
                    'default' => 'failed',
                    'type' => 'select', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'pending' => __('Pending payment', ''),
                        'processing' => __('Processing', ''),
                        'on-hold' => __('On hold', ''),
                        'completed' => __('Completed', ''),
                        'cancelled' => __('Cancelled', ''),
                        'refunded' => __('Refunded', ''),
                        'failed' => __('Failed', '')
                    )
                ),
                'checkout_language' => array(
                    'title' => __('Checkout Language', ''),
                    'default' => 'en',
                    'type' => 'select', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'options' => array(
                        'en' => __('English', ''),'af' => __('Afrikaans', ''),'sq' => __('Albanian', ''),'am' => __('Amharic', ''),'ar' => __('Arabic', ''),'hy' => __('Armenian', ''),'az' => __('Azerbaijani', ''),'eu' => __('Basque', ''),'be' => __('Belarusian', ''),'bn' => __('Bengali', ''),'bs' => __('Bosnian', ''),'bg' => __('Bulgarian', ''),'ca' => __('Catalan', ''),'ceb' => __('Cebuano', ''),'ny' => __('Chichewa', ''),'zh-CN' => __('Chinese (Simplified)', ''),'zh-TW' => __('Chinese (Traditional)', ''),'co' => __('Corsican', ''),'hr' => __('Croatian', ''),'cs' => __('Czech', ''),'da' => __('Danish', ''),'nl' => __('Dutch', ''),'eo' => __('Esperanto', ''),'et' => __('Estonian', ''),'tl' => __('Filipino', ''),'fi' => __('Finnish', ''),'fr' => __('French', ''),'fy' => __('Frisian', ''),'gl' => __('Galician', ''),'ka' => __('Georgian', ''),'de' => __('German', ''),'el' => __('Greek', ''),'gu' => __('Gujarati', ''),'ht' => __('Haitian Creole', ''),'ha' => __('Hausa', ''),'haw' => __('Hawaiian', ''),'iw' => __('Hebrew', ''),'hi' => __('Hindi', ''),'hmn' => __('Hmong', ''),'hu' => __('Hungarian', ''),'is' => __('Icelandic', ''),'ig' => __('Igbo', ''),'id' => __('Indonesian', ''),'ga' => __('Irish', ''),'it' => __('Italian', ''),'ja' => __('Japanese', ''),'jw' => __('Javanese', ''),'kn' => __('Kannada', ''),'kk' => __('Kazakh', ''),'km' => __('Khmer', ''),'rw' => __('Kinyarwanda', ''),'ko' => __('Korean', ''),'ku' => __('Kurdish (Kurmanji)', ''),'ky' => __('Kyrgyz', ''),'lo' => __('Lao', ''),'la' => __('Latin', ''),'lv' => __('Latvian', ''),'lt' => __('Lithuanian', ''),'lb' => __('Luxembourgish', ''),'mk' => __('Macedonian', ''),'mg' => __('Malagasy', ''),'ms' => __('Malay', ''),'ml' => __('Malayalam', ''),'mt' => __('Maltese', ''),'mi' => __('Maori', ''),'mr' => __('Marathi', ''),'mn' => __('Mongolian', ''),'my' => __('Myanmar (Burmese)', ''),'ne' => __('Nepali', ''),'no' => __('Norwegian', ''),'or' => __('Odia (Oriya)', ''),'ps' => __('Pashto', ''),'fa' => __('Persian', ''),'pl' => __('Polish', ''),'pt' => __('Portuguese', ''),'pa' => __('Punjabi', ''),'ro' => __('Romanian', ''),'ru' => __('Russian', ''),'sm' => __('Samoan', ''),'gd' => __('Scots Gaelic', ''),'sr' => __('Serbian', ''),'st' => __('Sesotho', ''),'sn' => __('Shona', ''),'sd' => __('Sindhi', ''),'si' => __('Sinhala', ''),'sk' => __('Slovak', ''),'sl' => __('Slovenian', ''),'so' => __('Somali', ''),'es' => __('Spanish', ''),'su' => __('Sundanese', ''),'sw' => __('Swahili', ''),'sv' => __('Swedish', ''),'tg' => __('Tajik', ''),'ta' => __('Tamil', ''),'tt' => __('Tatar', ''),'te' => __('Telugu', ''),'th' => __('Thai', ''),'tr' => __('Turkish', ''),'tk' => __('Turkmen', ''),'uk' => __('Ukrainian', ''),'ur' => __('Urdu', ''),'ug' => __('Uyghur', ''),'uz' => __('Uzbek', ''),'vi' => __('Vietnamese', ''),'cy' => __('Welsh', ''),'xh' => __('Xhosa', ''),'yi' => __('Yiddish', ''),'yo' => __('Yoruba', ''),'zu' => __('Zulu', '')
                    )
                ),
                'additional_value' => array(
                    'title' => __('Additional Value', ''),
                    'type' => 'textarea', 
                        'class' => 'ao1_paynext',
                        'css' => '',
                    'description' => __('', '')
                )
            );
        }
        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         **/
        public function admin_options()
        {
            echo '<h3>' . __('payNext Payment Gateway', '') . '</h3>';
            echo '<p>' . __('payNext is most popular payment gateway for online shopping') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }
        public function validate_fields()
        {
            global $woocommerce;
            if ($this->paynext_type == 'card') {
                
                $billing_creditcard_paynext = str_replace(' ', '', $_POST['billing_creditcard_paynext']);
                if (!WC_E_CHECK_CARD_paynext::e_valid_card_number($billing_creditcard_paynext)) {
                    wc_add_notice(__('Credit card number you entered is invalid.', 'woocommerce'), 'error');
                }
               
                if (!WC_E_CHECK_CARD_paynext::e_valid_expiry($_POST['billing_expdatemonth_paynext'], $_POST['billing_expdateyear_paynext'])) {
                    wc_add_notice(__('Card expiration date is not valid.', 'woocommerce'), 'error');
                }
                if (!WC_E_CHECK_CARD_paynext::e_valid_cvv_number($_POST['billing_ccvnumber_paynext'])) {
                    wc_add_notice(__('Card verification number (CVV) is not valid. You can find this number on your credit card.', 'woocommerce'), 'error');
                }
            }
        }
        
        
        public function payment_scripts() {
            
             if ($this->paynext_type == 'card') {
                     wp_enqueue_script( 'woocommerce_paynext_custom',plugins_url( '/assets/js/custom.js', __FILE__ ), array( 'jquery' ) );
             }
             
              wp_enqueue_style( 'woocommerce_paynext_custom',plugins_url( '/assets/style.css', __FILE__ ), true );
        }
        
        
        /**
         *  There are no payment fields for paynext, but we want to show the description if set.
         **/
        function payment_fields()
        { 
            if ($this->settings['logo'] == 'yes') { /* ?>
                    <img class='icon_paynext' src='<?php echo woocommerce_plugin_url_paynext;?>/img/visamastjcb.png' >
            <?php */
            }
        ?>
          
            <?php
             if ($this->description){
                echo $description ="<div class='paynext_description' >".wpautop(wptexturize($this->description))."</div>";
             }
            ?>
         
        
        
        <?php
        
            if ($this->paynext_type == 'host') {
                //if ($this->description) echo wpautop(wptexturize($this->description));
            } else {
                $billing_creditcard_paynext = isset($_REQUEST['billing_creditcard_paynext']) ? esc_attr($_REQUEST['billing_creditcard_paynext']) : '';
        ?>
       <div class="payment_method_paynext-wrap">
       <div class="form-row form-row-wide wc-fyfx-form-wrap validate-required">
            <?php
                $card_number_field_placeholder = __('Card Number', 'woocommerce');
?>            
            <label><?php
                _e('Card Number', 'woocommerce');
?> <span class="required">*</span></label>
            <input class="input-text check_creditcard wc-fyfx-form-field" type="text" size="19" maxlength="19" name="billing_creditcard_paynext" value="<?php
                echo $billing_creditcard_paynext;
?>" placeholder="1234 1234 1234 1234" />



        </div>         
          
        <div class="clear"></div>
        <div class="form-row form-row-first wc-fyfx-form-wrap validate-required">
            <label><?php
                _e('Expiry Date', 'woocommerce');?> <span class="required">*</span></label>
            <div class="credit-card-input">
    <input name="billing_expdatemonth_paynext" type="text" class="input-text wc-fyfx-form-field" id="expMonth" placeholder="MM" maxlength="2">
    <span class="slasher">/</span>
    <input name="billing_expdateyear_paynext" type="text" class="input-text wc-fyfx-form-field" id="expYear" placeholder="YY" maxlength="2">
  </div>

  <div id="errorContainer" class="error-message"></div>
                   
        </div>
        <div class="form-row form-row-last wc-fyfx-form-wrap validate-required">
            <?php
                $cvv_field_placeholder = __('Card Code (CVC)', 'woocommerce');
?>
           <label><?php
                _e('CVC', 'woocommerce');
?> <span class="required">*</span></label>
            <input class="input-text wc-fyfx-form-field" type="text" size="4" maxlength="4" name="billing_ccvnumber_paynext" value="" placeholder="CVC" />
        </div>

<div class="clear"></div>
</div>
        
        <?php
            }
        }
        /**
         * Receipt Page
         **/
        function receipt_page($order)
        {
            if ($this->paynext_type == 'host') {
                echo '<p>' . __('Thank you for your order, please click the button below to pay with paynext.', '') . '</p>';
                echo $this->generate_paynext_form($order);
            }
        }


        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            $logger = wc_get_logger();
            $context = array('source' => 'a-fyfx-paynext-log');
            $logger->info('', $context);
            $logger->info('-----------------------', $context);
            $logger->info('-----FYFX-PAYNEXT------', $context);
            $logger->info('-----------------------', $context);
            $logger->info('This is new info log entry.', $context);
            $order = new WC_Order($order_id);
            if ($this->paynext_type == 'card') {
                $order_id = $order_id;
                global $woocommerce;
                $items = $woocommerce->cart->get_cart();
                foreach ($items as $item => $values) {
                    $_product        = wc_get_product($values['data']->get_id());
                    $product_title[] = $_product->get_title();
                }
                $product_title              = implode(',', $product_title);
                
                
                $products = $order->get_items();
                $product_title = "";
                if ( is_array( $products ) && count( $products ) > 0 ) {
                    $product = current( $products );
                    $product_title = $product->get_name();
                }
                
                
                $the_currency               = get_woocommerce_currency();
                $the_order_total            = @$order->get_total();
                
                $gateway_url                =  implode('/', explode('/', $this->transaction_url, -1))."/directapi.do";
                
                $curlPost                   = array();
                $country=$order->get_billing_country();
                //<!--Replace of 3 very important parameters * your product API code -->
                $curlPost["checkout_language"]      = $this->checkout_language; // language converter  
                $curlPost["api_token"]      = $this->api_token; // WEBSITE API TOKEN 
                $curlPost["website_id"]       = $this->website_id; // Website Id 
                //<!--default (fixed) value * default -->
                $curlPost["cardsend"]       = "curl";
                $curlPost["client_ip"]      = ($_SERVER['HTTP_X_FORWARDED_FOR']?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
                $curlPost["action"]         = "product";
                $curlPost["source"]         = "Curl-Direct (WV " . WOOCOMMERCE_VERSION . ")";
                //<!--product price,curr and product name * by cart total amount -->
                $curlPost["price"]          = $the_order_total;
                $curlPost["curr"]           = $the_currency;
                $curlPost["product_name"]   = $product_title;
                //<!--billing details of .* customer -->
                //$curlPost["ccholder"]       = $order->billing_first_name;
                //$curlPost["ccholder_lname"] = $order->billing_last_name;
                $billing_address_1=$order->get_billing_address_1();
                if(empty($billing_address_1)){$billing_address_1=$country;}

                $curlPost["fullname"] = $order->get_billing_first_name(). " " .$order->get_billing_last_name();
                $curlPost["email"]          = $order->get_billing_email();
                $curlPost["bill_street_1"]  = $billing_address_1;
                $curlPost["bill_street_2"]  = $billing_address_1;
                $curlPost["bill_city"]      = $order->get_billing_city();
                $curlPost["bill_state"]     = $order->get_billing_state();
                
                
                $billing_phone=$order->get_billing_phone();
                if(empty($billing_phone)){$billing_phone="8".rand(100000000,999999999);}
                
                $curlPost["bill_country"]   = $order->get_billing_country();
                $curlPost["bill_zip"]       = $order->get_billing_postcode();
                $curlPost["bill_phone"]     = $order->get_billing_phone();
                $curlPost["id_order"]       = $order_id;


                //$curlPost["sctest"]           = "22";
                $curlPost["notify_url"]     = $this->notify_url;
                $curlPost["success_url"]    = $this->get_return_url( $order );
                $curlPost["error_url"]      = site_url() ."/cart/?cancel_order=true&order=wc_order_&order_id=".$order_id."&redirect&_wpnonce=";
                
                //<!--card details of .* customer -->
                $curlPost["ccno"]           = $_POST['billing_creditcard_paynext'];
                $curlPost["ccvv"]           = $_POST['billing_ccvnumber_paynext'];
                $curlPost["month"]          = $_POST['billing_expdatemonth_paynext'];
                $curlPost["year"]           = $_POST['billing_expdateyear_paynext'];
                //$curlPost["notes"]="Remark for transaction";
                
                
                
                $curlPost["source"]           = "Curl-Direct (WV " . WOOCOMMERCE_VERSION . ")";
                
                $protocol = isset($_SERVER["HTTPS"])?'https://':'http://';
                $source_url=$protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    
                $curlPost["source_url"]           = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:$source_url);
                
                $additional_value = json_decode( $this->additional_value, true );
                
                if (($additional_value) && is_array( $additional_value ) ) {
                    $curlPost=array_merge($curlPost, $additional_value);
                }
                
                $protocol                   = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
                $referer                    = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $curl_cookie                = "";
                $args = array(
                    'method'      => 'POST',
                    'timeout'     => 100,  // Response timeout
                    'redirection' => 5,   // Number of allowed redirects
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(
                        'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                        'Referer'    => $referer
                    ),
                    'body'        => $curlPost,  // Your POST data
                    'cookies'     => array()
                );

                $response = wp_remote_post($gateway_url, $args);

                if (is_wp_error($response)) {
                    $error_message = $response->get_error_message() ?: 'no-error';
                    $logger->info('', $context);
                    $logger->error("--New Response Error--", $context);
                    $logger->error("HTTP Request Error: " . $error_message, $context);
                    $logger->error("--End Response Error--", $context);
                } else {
                    $raw_response = wp_remote_retrieve_body($response);
                    $logger->info('', $context);
                    $logger->info("--New Response Info--", $context);
                    $logger->info("Raw Response: " . $raw_response, $context);
                     $logger->info("--End Response Info--", $context);

                    $status_code = wp_remote_retrieve_response_code($response, $context);
                    $logger->info('', $context);
                    $logger->info("HTTP Status Code: " . $status_code, $context);

                    $results = json_decode($raw_response, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $logger->info('', $context);
                        $logger->error("JSON Decode Error: " . json_last_error_msg(), $context);
                    }

                    if (!$results) {
                        update_post_meta($order_id, 'payment_status', 'failed - no response from paynext ' . $results);
                        update_post_meta($order_id, 'reason', 'Max. transactions allowed within (1 days)');
                        $logger->info('', $context);
                        $logger->error('WC Payment API result error: Error Response Code : Empty Result - ' . $raw_response, $context);
                        wc_add_notice(sprintf(__('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase. <p>Code : Payment Declined (ReCT)</p>', 'fyfx-payNext')), 'error');
                        $order->update_status($this->status_pending);
                        return;
                    }
                }
                    
                $status = $results["status"];
                $response_encode = json_encode($results, true) . " || " . $raw_response;
                $status_nm = (int)($results["status_nm"]);
                $sub_query = http_build_query($results);
                $transaction_id = $results["transaction_id"];
                $authurl = "https://portal.online-epayment.com/authurl.do?api_token=" . $api_token . "&transaction_id=" . $transaction_id;
                $url_auth_url_1 = isset($results["authurl"]);
                $url_auth_url_2 = $results["authurl"];

                if (!isset($results["authurl"])) {
                    update_post_meta( $order_id, 'payment_status', 'failed - authurl is empty - response from paynext : ' .$results );
                    update_post_meta( $order_id, 'reason', 'authurl is empty' );
                    $logger->info('', $context);
                    $logger->error('Payment API response error code: No Response Auth URL' . print_r($results, true), $context);
                    wc_add_notice( sprintf( __('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase.<p>Code : Payment Declined (ANR)</p>', 'fyfx-payNext')), 'error' );
                    $order->update_status($this->status_pending);
                    return;
                }
                     
              
               if (isset($results["authurl"]) && $results["authurl"]) { 
                    $redirecturl = $results["authurl"];
                    
                    // Arguments for POST request - if you have specific data to post, add it in the 'body' key
                    $args = array(
                        'method' => 'POST',
                        'timeout' => 100,
                        'redirection' => 5, // Maximum number of redirects
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'body' => array(), // Example: 'key1' => 'value1', 'key2' => 'value2'
                        'cookies' => array()
                    );

                    // Use wp_remote_post
                    $response_auth = wp_remote_post($redirecturl, $args);
                    
                    // Check for errors
                    if (is_wp_error($response_auth)) {
                        $error_message = $response_auth->get_error_message() ?: 'no-error';
                        $logger->info('', $context);
                        $logger->error("--New Response Error - authurl--", $context);
                        $logger->error("HTTP Request Error - authurl: " . $error_message, $context);
                        $logger->error("--End Response Error - authurl--", $context);
                        wc_add_notice(sprintf(__('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase. <p>Code : Payment Declined (CT)</p>', 'fyfx-payNext')), 'error');
                        return; // Exit the function
                    } else {
                        $raw_response = wp_remote_retrieve_body($response_auth);
                        $logger->info('', $context);
                        $logger->info("--New Response Info  - authurl--", $context);
                        $logger->info("Raw Response - authurl: " . $raw_response, $context);
                        $logger->error("--End Response Info  - authurl--", $context);

                        $status_code = wp_remote_retrieve_response_code($response_auth);
                        $logger->info('', $context);
                        $logger->info("HTTP Status Code - authurl: " . $status_code, $context);

                        $responseArray = json_decode($raw_response, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $logger->error("JSON Decode Error - authurl: " . json_last_error_msg(), $context);
                            wc_add_notice(sprintf(__('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase. <p>Code : Payment Declined (JDE)</p>', 'fyfx-payNext')), 'error');
                            return; // Exit the function
                        } elseif (!$responseArray) {
                            $logger->error('WC Payment API result error - authurl: Error Response Code : Empty Result - ' . print_r($responseArray, true), $context);
                            wc_add_notice(sprintf(__('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase. <p>Code : Payment Declined (ER)</p>', 'fyfx-payNext')), 'error');
                            return; // Exit the function
                        } else {
                            update_post_meta($order_id, 'auth_url', $redirecturl);
                        }
                    }
                }

                $response_encode_3ds = json_encode($responseArray, true) . " || " . $response_body;
                $sub_query_3ds = http_build_query($responseArray);

                // Extract the required information from the response
                $status_nm_3ds = $responseArray["status_nm"];
                $status_cc = $responseArray["status"];
                $transaction_id = $responseArray["transaction_id"];
                $reason = $responseArray["reason"];

           
                if ($status_nm_3ds == 1) { // 1:Approved/Success, 9:Test Transaction
                    $redirecturl = $curlPost["success_url"];
                    $order->payment_complete();
                    update_post_meta( $order_id, 'auth_transaction_id', $transaction_id );
                    update_post_meta( $order_id, 'auth_url_3ds', $url_auth_url_2 );
                    update_post_meta( $order_id, 'payment_status', $status_cc );
                    update_post_meta( $order_id, 'reason', $reason ); 
                    update_post_meta( $order_id, 'status_nm', $status_nm_3ds );
                    update_post_meta( $order_id, 'sub_query_3ds', $sub_query_3ds );
                    
                    $order->add_order_note(__('<button id="'.$responseArray['transaction_id'].'" api="'.$responseArray['api_token'].'" name="current-status" class="button-primary woocommerce-validate-current-status-paynext" type="button" value="Validate Current Status.">Validate Current Status.</button>', ''));               
                    // this is important part for empty cart
                    $woocommerce->cart->empty_cart();  
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );                  

                } elseif ($status_nm_3ds == 9) { // 9 = Test Payment
                    wc_add_notice( sprintf( __('We’re sorry, but your payment attempt was unsuccessful. Your Credit Card is Test Payment, Please consider using an alternative payment method to complete your purchase.', 'fyfx-payNext'), $status_cc ), 'error' );
                    $order->add_order_note('Payment Cancel - cError: Test Paymet Card');
                    $order->add_order_note(__('<button id="'.$responseArray['transaction_id'].'" api="'.$responseArray['api_token'].'" name="current-status" class="button-primary woocommerce-validate-current-status-paynext" type="button" value="Validate Current Status.">Validate Current Status.</button>', ''));
                    update_post_meta( $order_id, 'auth_transaction_id', $transaction_id );
                    update_post_meta( $order_id, 'auth_url_3ds', $url_auth_url_2 );
                    update_post_meta( $order_id, 'payment_status', $status_cc );
                    update_post_meta( $order_id, 'reason', $reason ); 
                    update_post_meta( $order_id, 'status_nm', $status_nm_3ds );
                    update_post_meta( $order_id, 'sub_query_3ds', $sub_query_3ds );
                    $order->update_status($this->status_cancelled);
                    return;
                } elseif ($status_nm_3ds == 2 || $status_nm_3ds == 22 || $status_nm_3ds == 23) { // 2:Declined/Failed, 22:Expired, 23:Cancelled
                    // Add a notice and link to go back to the previous checkout page
                    wc_add_notice( sprintf( __('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase.', 'fyfx-payNext'), $status_cc ), 'error' );
                    $order->add_order_note('Payment Cancel - cError log: ' . $response_encode_3ds );
                    $order->add_order_note(__('<button id="'.$responseArray['transaction_id'].'" api="'.$responseArray['api_token'].'" name="current-status" class="button-primary woocommerce-validate-current-status-paynext" type="button" value="Validate Current Status.">Validate Current Status.</button>', ''));                    
                    update_post_meta( $order_id, 'auth_transaction_id', $transaction_id );
                    update_post_meta( $order_id, 'auth_url_3ds', $url_auth_url_2 );
                    update_post_meta( $order_id, 'payment_status', $status_cc );
                    update_post_meta( $order_id, 'reason', $reason ); 
                    update_post_meta( $order_id, 'status_nm', $status_nm_3ds );
                    update_post_meta( $order_id, 'sub_query_3ds', $sub_query_3ds );                  
                    update_post_meta( $order_id, 'fyfxaddress', $billing_address_1 );
                    $order->update_status($this->status_cancelled);
                    return;
                } else { // Pending
                    // Add a notice and link to go back to the previous checkout page
                    wc_add_notice( sprintf( __('We’re sorry, but your payment attempt was unsuccessful. Please consider using an alternative payment method to complete your purchase.', 'fyfx-payNext'), $status_cc ), 'error' ); 
                    $order->add_order_note('Payment Pending - cError unknown log: '. $response_encode_3ds );
                    $order->add_order_note(__('<button id="'.$responseArray['transaction_id'].'" api="'.$responseArray['api_token'].'" name="current-status" class="button-primary woocommerce-validate-current-status-paynext" type="button" value="Validate Current Status.">Validate Current Status.</button>', ''));                    
                    $order->add_order_note('payment cancel - cError 1 : ' . $response_encode_3ds);
                    $order->add_order_note('payment cancel - cError 2 : ' . $sub_query_3ds);             
                    update_post_meta( $order_id, 'auth_transaction_id', $transaction_id );
                    update_post_meta( $order_id, 'auth_url_3ds', $url_auth_url_2 );
                    update_post_meta( $order_id, 'payment_status', $status_cc );
                    update_post_meta( $order_id, 'reason', $reason ); 
                    update_post_meta( $order_id, 'status_nm', $status_nm_3ds );
                    update_post_meta( $order_id, 'status_add', 'unknown' );
                    update_post_meta( $order_id, 'sub_query_3ds', $sub_query_3ds );
                    $order->update_status($this->status_pending);
                    return;                   
                }               

            }
        }
        
       
        
        /**
         * Check for valid paynext server callback
         **/
        function check_paynext_response()
        {
            global $woocommerce;
            $msg['class']   = 'error';
            $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
            $json_response  = $_POST;
            
              $order_id       = $json_response['id_order'];
            $order_id = explode('_', $order_id);
            $order_id = $order_id[0];
            
            
            
            $check_transaction_id = get_post_meta( $order_id, 'transaction_id', true );
           if(isset($json_response["status"]))
           {
             update_post_meta( $order_id, 'payment_amount', $json_response['amt'] );
            update_post_meta( $order_id, 'payment_currency', $json_response['curr'] );
            update_post_meta( $order_id, 'payment_date', $json_response['tdate'] );
            update_post_meta( $order_id, 'payment_descriptor', $json_response['descriptor'] );
            update_post_meta( $order_id, 'payment_status', $json_response["status"] );
            update_post_meta( $order_id, 'transaction_id', $json_response['transaction_id'] );
            
           }
            
            
            $get_settings=get_option( 'woocommerce_paynext_settings', true );
  
          
            if (isset($json_response['transaction_id'])) {
                
                if ($order_id != '') {
                    try {
                        $order        = new WC_Order($order_id);
                        if(empty( $check_transaction_id)){
                        $order->add_order_note(__('<button id="'.$json_response['transaction_id'].'" api="'.$get_settings['api_token'].'" name="current-status" class="button-primary woocommerce-validate-current-status-paynext" type="button" value="Validate Current Status!">Validate Current Status!</button>', ''));
                        }
                        $order_status = $json_response['status'];
                        if ($order->get_status() !== 'Completed') {
                            if ($order_status == "Completed" || $order_status == "Success" || $order_status == "Test" || $order_status == "Test Transaction"|| $order_status == "Approved" || $status == "Scrubbed") {
                                $transauthorised = true;
                                $msg['message']  = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $msg['class']    = 'success';
                                if ($order->get_status() != 'processing') {
                                    $order->payment_complete();
                                    $order->update_status($this->status_completed);
                                       if(empty( $check_transaction_id)){
                                    $order->add_order_note('paynext payment successful<br/>Transaction ID: ' . $json_response['transaction_id']);
                                       }
                                    $woocommerce->cart->empty_cart();
                                }
                            } else if ($status == "Failed" || $status == "Cancelled") {
                                $msg['class']   = 'error';
                                $msg['message'] = " We are waiting for your order status from the bank-Transaction pending";
                                $order->update_status($this->status_cancelled);
                            } else {
                                $msg['class']   = 'error';
                                $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                                $order->update_status($this->status_pending);
                            }
                            /* if($transauthorised==false){
                            $order -> update_status('failed');
                            $order -> add_order_note('Failed');
                            $order -> add_order_note($this->msg['message']);
                            }*/
                        }
                    }
                    catch (Exception $e) {
                        $msg['class']   = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                    }
                }
            }
            if (function_exists('wc_add_notice')) {
                wc_add_notice($msg['message'], $msg['class']);
            } else {
                if ($msg['class'] == 'success') {
                    $woocommerce->add_message($msg['message']);
                } else {
                    $woocommerce->add_error($msg['message']);
                }
                $woocommerce->set_messages();
            }
           
            $redirect_url = $this->get_return_url($order);
            wp_redirect($redirect_url);
            exit;
        }


        

        /*
      
        
        /**
         * Generate  button link
         **/
        public function generate_paynext_form($order_id)
        {
            global $woocommerce;
            $order     = new WC_Order($order_id);
            $order_id  = $order_id;
            $post_data = get_post_meta($order_id, '_post_data', true);
            update_post_meta($order_id, '_post_data', array());
            $the_currency    = get_woocommerce_currency();
            $the_order_total = @$order->get_total();
            if ($this->paynext_type == 'host') {
                $form = '';
                wc_enqueue_js('
                    $.blockUI({
                        message: "<h2>Please wait while we process your request</h2><p>Since this may take a few seconds, please do not close/refresh this window.</p>",
                        baseZ: 99999,
                        overlayCSS:
                        {
                            background: "red",
                            opacity: 0.6
                        },
                        css: {
                            padding:        "20px",
                            zindex:         "9999999",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:     "24px",
                        }
                    });
                jQuery("#submit_paynext_payment_form").click();
                ');
                $targetto = 'target="_top"';
                $items    = $woocommerce->cart->get_cart();
                foreach ($items as $item => $values) {
                    $_product        = wc_get_product($values['data']->get_id());
                    $product_title[] = $_product->get_title();
                }
                $product_title      = implode(',', $product_title);
                
                
                $products = $order->get_items();
                $product_title = "";
                if ( is_array( $products ) && count( $products ) > 0 ) {
                    $product = current( $products );
                    $product_title = $product->get_name();
                }
                
                
                $paynext_args_array   = array();
                $paynext_args_array[] = "<input type='hidden' name='checkout_language' value='" . $this->checkout_language . "'/>";
                $paynext_args_array[] = "<input type='hidden' name='api_token' value='" . $this->api_token . "'/>";
                $paynext_args_array[] = "<input type='hidden' name='website_id' value='" . $this->website_id . "'/>";
                $paynext_args_array[] = '<input type="hidden" name="cardsend" value="CHECKOUT"/>';
                $paynext_args_array[] = '<input type="hidden" name="client_ip" value="'.($_SERVER['HTTP_X_FORWARDED_FOR']?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']).'"/>';
                $paynext_args_array[] = '<input type="hidden" name="action" value="product"/>'; 
                $paynext_args_array[] = '<input type="hidden" name="source" value="Host-Re-Direct (WV ' . WOOCOMMERCE_VERSION . ')"/>';
                $paynext_args_array[] = '<input type="hidden" name="price" value="' . $the_order_total . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="curr" value="' . $the_currency . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="product_name" value="' . $product_title . ' "/>';
                //$paynext_args_array[] = '<input type="hidden" name="ccholder" value="' . @$order->billing_first_name . '"/>';
                //$paynext_args_array[] = '<input type="hidden" name="ccholder_lname" value="' . @$order->billing_last_name . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="fullname" value="' . @$order->billing_first_name ." " . @$order->billing_last_name. '"/>';
                $paynext_args_array[] = '<input type="hidden" name="email" value="' . @$order->billing_email . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_street_1" value="' . @$order->billing_address_1 . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_street_2" value="' . @$order->billing_address_2 . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_city" value="' . @$order->billing_city . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_state" value="' . @$order->billing_state . '"/>';
                $country=$order->get_billing_country();
                $billing_phone=$order->billing_phone;
                if(empty($billing_phone)){$billing_phone="8".rand(100000000,999999999);}
                $paynext_args_array[] = '<input type="hidden" name="bill_country" value="' .$country. '"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_zip" value="0000"/>';
                $paynext_args_array[] = '<input type="hidden" name="bill_phone" value="' . $billing_phone . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="id_order" value="' . $order_id . '"/>';
               
               $paynext_args_array[] = '<input type="hidden" name="notify_url" value="' . $this->notify_url . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="success_url" value="' . $this->get_return_url( $order ) . '"/>';
                $paynext_args_array[] = '<input type="hidden" name="error_url" value="' . site_url() ."/cart/?cancel_order=true&order=wc_order_&order_id=".$order_id."&redirect&_wpnonce=" . '"/>';
                
                $protocol = isset($_SERVER["HTTPS"])?'https://':'http://';
                $source_url=$protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
                
                $paynext_args_array[] = '<input type="hidden" name="source_url" value="' . (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:$source_url) . '"/>';
                
                
                
                $additional_value = json_decode( $this->additional_value, true );
                if (($additional_value) && is_array( $additional_value ) ) {
                    foreach($additional_value as $key=>$value){
                        $paynext_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
                    }
                }
                
                $host_url=implode('/', explode('/', $this->transaction_url, -1))."/checkout.do";
                
                $form .= '<form action="'. $host_url .'" method="post" id="paynext_payment_form"  ' . $targetto . '>
                ' . implode('', $paynext_args_array) . '
                <!-- Button Fallback -->
                <div class="payment_buttons">
                <input type="submit" class="button alt" id="submit_paynext_payment_form" value="' . __('Pay via paynext', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
                </div>
                <script type="text/javascript">
                jQuery(".payment_buttons").hide();
                </script>
                </form>';
                return $form;
            }
        }
    }
    
    
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_paynext_gateway($methods)
    {
        $methods[] = 'WC_paynext';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_paynext_gateway');
}


 function admin_paynext_load_scripts( $hook ) {
    $get_settings=get_option( 'woocommerce_paynext_settings', true );
   echo "<script>
                var paynext_api_token='{$get_settings['api_token']}';
        </script>";
        
    //wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'assets/js/paynext_admin_custom.js', array(), '1.0' ); 
    
     wp_enqueue_script( 'my-plugin-script-paynext', plugin_dir_url( __FILE__ ) . 'assets/js/paynext_admin_custom.js'); 
}

    

add_action( 'admin_enqueue_scripts', 'admin_paynext_load_scripts' );


add_action('wp_ajax_check_paynext_transaction_status', 'check_paynext_transaction_status');

function enqueue_custom_scripts() {
  // Daftarkan script kustom untuk validasi bulan dan tahun
  wp_enqueue_script( 'woocommerce_paynext_custom_validation',plugins_url( '/assets/js/custom-script.js', __FILE__ ), array( 'jquery' ) );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

function check_paynext_transaction_status() {
    $validateurl = get_option('woocommerce_paynext_settings', true);

    $transaction_id = $_POST['tra_id'];
    $url = implode('/', explode('/', $validateurl['transaction_url'], -1)) . "/validate.do";

    $setPost = array(
        'transaction_id' => $transaction_id,
        'api_token' => $validateurl['api_token']
    );

    $args = array(
        'method' => 'POST',
        'timeout' => 45,  // Response timeout. You can adjust this.
        'redirection' => 5,   // Number of allowed redirects
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => $setPost,  // Your POST data
        'cookies' => array(),
        'sslverify' => false  // Equivalent to CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST set to false
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
    } else {
        $json_response = json_decode(wp_remote_retrieve_body($response), true);

        $htmlresponse = "";
        foreach ($json_response as $key => $data) {
            $htmlresponse .= "<br><b>" . $key . ":</b>" . $data;
        }
        echo $htmlresponse;
    }
    exit;
}


// Function to trigger the 'before_page_content' action hook
function add_notice_content_shortcode() {
    ob_start();
    do_action('add_notice_content');
    return ob_get_clean();
}
add_shortcode('add_notice_content_shortcode', 'add_notice_content_shortcode');

// Menampilkan pemberitahuan pada halaman "Thank You"
function display_order_payment_notices() {
    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }
}
add_action('add_notice_content', 'display_order_payment_notices');

// Additional Script Response
function paynext_js_script_response() {
    // Display API response header in inspect element
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/checkout/order-received/') !== false){
        $order_id = absint(get_query_var('order-received'));
        $api_paynext = get_post_meta($order_id, 'response_status', true);
        ?>
        <script>
            var paynextResponse = <?php echo json_encode($api_paynext); ?>;
            console.log(paynextResponse);
        </script>
        <?php
    }
    elseif (strpos($current_url, '/sellkit_step/') !== false && strpos($current_url, '?order-key=wc_order') !== false) {
        $key = isset( $_GET['order-key'] ) ? sanitize_text_field( $_GET['order-key'] ) : false;
        $current_page_id = get_queried_object_id();
        if ( empty( $key ) ) {
            return;
        }
        if ( $key ) {
            $order_id = wc_get_order_id_by_order_key( $key );
        }
        $api_paynext = get_post_meta($order_id, 'response_status', true);
        ?>
        <script>
            var paynextResponse = <?php echo json_encode($api_paynext); ?>;
            console.log(paynextResponse);
        </script>
        <?php
    }
    else{
        return;
    }
}
do_action('paynext_hook_paynext_js_script_response');
add_action('paynext_hook_paynext_js_script_response', 'paynext_js_script_response');

function run_paynext_js_script_response() {
    do_action('paynext_hook_paynext_js_script_response');
}
add_action('wp_footer', 'run_paynext_js_script_response');

function add_custom_script_to_footer() {
    ?>
    <script type="text/javascript">
    jQuery(document).ajaxComplete(function(event) {
            if (jQuery('.woocommerce-NoticeGroup').length > 0) {
                console.log("Error notice displayed on checkout page.");
                var scrollPos =  jQuery(".woocommerce-NoticeGroup").offset().top - 100;
                jQuery(window).scrollTop(scrollPos, 800);
                jQuery('.woocommerce-NoticeGroup').focus();
                return false;
            }
    });
    </script>
    <?php
}

/**
 * Add or modify States
 */
add_filter( 'woocommerce_states', 'custom_woocommerce_states' );
function custom_woocommerce_states( $states ) {
  $states['JP'] = array(
        'HK' => 'Hokkaido',
        'AO' => 'Aomori',
        'IW' => 'Iwate',
        'MY' => 'Miyagi',
        'AK' => 'Akita',
        'YM' => 'Yamagata',
        'FK' => 'Fukushima',
        'IB' => 'Ibaragi',
        'TC' => 'Tochigi',
        'GU' => 'Gunma',
        'SI' => 'Saitama',
        'CB' => 'Chiba',
        'TK' => 'Tokyo',
        'KN' => 'Kanagawa',
        'NI' => 'Niigata',
        'TY' => 'Toyama',
        'IS' => 'Ishikawa',
        'FI' => 'Fukui',
        'YN' => 'Yamanashi',
        'NG' => 'Nagano',
        'GF' => 'Gifu',
        'SZ' => 'Shizuoka',
        'AI' => 'Aichi',
        'ME' => 'Mie',
        'SG' => 'Shiga',
        'KT' => 'Kyoto',
        'OS' => 'Osaka',
        'HG' => 'Hyogo',
        'NR' => 'Nara',
        'WK' => 'Wakayama',
        'TT' => 'Tottori',
        'SM' => 'Shimane',
        'OK' => 'Okayama',
        'HR' => 'Hiroshima',
        'YG' => 'Yamaguchi',
        'TS' => 'Tokushima',
        'KG' => 'Kagawa',
        'EH' => 'Ehime',
        'KC' => 'Kouchi',
        'FO' => 'Fukuoka',
        'SA' => 'Saga',
        'NS' => 'Nagasaki',
        'KM' => 'Kumamoto',
        'OI' => 'Ooita',
        'MZ' => 'Miyazaki',
        'KS' => 'Kagoshima'
    );
  return $states;
}
add_filter( 'woocommerce_states', 'wc_uk_counties_add_counties' );
function  wc_uk_counties_add_counties( $states ) {
    $states['GB'] = array(
                            'AV' => 'Avon',
                            'BE' => 'Bedfordshire',
                            'BK' => 'Berkshire',
                            'BU' => 'Buckinghamshire',
                            'CA' => 'Cambridgeshire',
                            'CH' => 'Cheshire',
                            'CL' => 'Cleveland',
                            'CO' => 'Cornwall',
                            'CD' => 'County Durham',
                            'CU' => 'Cumbria',
                            'DE' => 'Derbyshire',
                            'DV' => 'Devon',
                            'DO' => 'Dorset',
                            'ES' => 'East Sussex',
                            'EX' => 'Essex',
                            'GL' => 'Gloucestershire',
                            'HA' => 'Hampshire',
                            'HE' => 'Herefordshire',
                            'HT' => 'Hertfordshire',
                            'IW' => 'Isle of Wight',
                            'KE' => 'Kent',
                            'LA' => 'Lancashire',
                            'LE' => 'Leicestershire',
                            'LI' => 'Lincolnshire',
                            'LO' => 'London',
                            'ME' => 'Merseyside',
                            'MI' => 'Middlesex',
                            'NO' => 'Norfolk',
                            'NH' => 'North Humberside',
                            'NY' => 'North Yorkshire',
                            'NS' => 'Northamptonshire',
                            'NL' => 'Northumberland',
                            'NT' => 'Nottinghamshire',
                            'OX' => 'Oxfordshire',
                            'SH' => 'Shropshire',
                            'SO' => 'Somerset',
                            'SM' => 'South Humberside',
                            'SY' => 'South Yorkshire',
                            'SF' => 'Staffordshire',
                            'SU' => 'Suffolk',
                            'SR' => 'Surrey',
                            'TW' => 'Tyne and Wear',
                            'WA' => 'Warwickshire',
                            'WM' => 'West Midlands',
                            'WS' => 'West Sussex',
                            'WY' => 'West Yorkshire',
                            'WI' => 'Wiltshire',
                            'WO' => 'Worcestershire',
                            'ABD' => 'Scotland / Aberdeenshire',
                            'ANS' => 'Scotland / Angus',
                            'ARL' => 'Scotland / Argyle & Bute',
                            'AYR' => 'Scotland / Ayrshire',
                            'CLK' => 'Scotland / Clackmannanshire',
                            'DGY' => 'Scotland / Dumfries & Galloway',
                            'DNB' => 'Scotland / Dunbartonshire',
                            'DDE' => 'Scotland / Dundee',
                            'ELN' => 'Scotland / East Lothian',
                            'EDB' => 'Scotland / Edinburgh',
                            'FIF' => 'Scotland / Fife',
                            'GGW' => 'Scotland / Glasgow',
                            'HLD' => 'Scotland / Highland',
                            'LKS' => 'Scotland / Lanarkshire',
                            'MLN' => 'Scotland / Midlothian',
                            'MOR' => 'Scotland / Moray',
                            'OKI' => 'Scotland / Orkney',
                            'PER' => 'Scotland / Perth and Kinross',
                            'RFW' => 'Scotland / Renfrewshire',
                            'SB' => 'Scotland / Scottish Borders',
                            'SHI' => 'Scotland / Shetland Isles',
                            'STI' => 'Scotland / Stirling',
                            'WLN' => 'Scotland / West Lothian',
                            'WIS' => 'Scotland / Western Isles',
                            'AGY' => 'Wales / Anglesey',
                            'GNT' => 'Wales / Blaenau Gwent',
                            'CP' => 'Wales / Caerphilly',
                            'CF' => 'Wales / Cardiff',
                            'CAE' => 'Wales / Carmarthenshire',
                            'CR' => 'Wales / Ceredigion',
                            'CW' => 'Wales / Conwy',
                            'DEN' => 'Wales / Denbighshire',
                            'FLN' => 'Wales / Flintshire',
                            'GLA' => 'Wales / Glamorgan',
                            'GWN' => 'Wales / Gwynedd',
                            'HAM' => 'Wales / Hampshire',
                            'MT' => 'Wales / Merthyr Tydfil',
                            'MON' => 'Wales / Monmouthshire',
                            'PT' => 'Wales / Neath Port Talbot',
                            'NP' => 'Wales / Newport',
                            'PEM' => 'Wales / Pembrokeshire',
                            'POW' => 'Wales / Powys',
                            'RT' => 'Wales / Rhondda Cynon Taff',
                            'SS' => 'Wales / Swansea',
                            'TF' => 'Wales / Torfaen',
                            'WX' => 'Wales / Wrexham',
                            'ANT' => 'Northern Ireland / County Antrim',
                            'ARM' => 'Northern Ireland / County Armagh',
                            'DOW' => 'Northern Ireland / County Down',
                            'FER' => 'Northern Ireland / County Fermanagh',
                            'LDY' => 'Northern Ireland / County Londonderry',
                            'TYR' => 'Northern Ireland / County Tyrone',
                           );
    return $states;
}

?>