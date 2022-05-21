<?php

/*
 * Plugin Name:ipara WooCommerce
 * Plugin URI: https://www.kahvedigital.com
 * Description: ipara woocommerce
 * Version: 1.0.1
 * Author: kahvedigital.com
 * Author URI: https://kahvedigital.com
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 4.4.1
 * Modül kodları ahmethamdibayrak@hotmail.com tarafından yeniden düzenlendi ve değitirildi
 */
define('IPARA_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('IPARA_PLUGIN', '/' . plugin_basename(dirname(__FILE__)));
if (!defined('ABSPATH')) {
    exit;
}
global $ipara_db_version;
$ipara_db_version = '3.0.1';
register_deactivation_hook(__FILE__, 'ipara_deactivation');
register_activation_hook(__FILE__, 'ipara_activate');
add_action('plugins_loaded', 'ipara_update_db_check');
include_once IPARA_PATH . '/lib/class-kahvedigital_iparaconfig.php';
function ipara_update_db_check()
{
    global $ipara_db_version;
    global $wpdb;
    $installed_ver = get_option("ipara_db_version");
    if ($installed_ver != $ipara_db_version) {
        ipara_update();
    }
}

function ipara_update()
{
    global $ipara_db_version;
    update_option("ipara_db_version", $ipara_db_version);
}

function ipara_activate()
{
    global $wpdb;
    global $ipara_db_version;
    $ipara_db_version = '3.0.1';

    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    add_option('ipara_db_version', $ipara_db_version);
}

function ipara_deactivation()
{
    global $wpdb;
    global $ipara_db_version;

    delete_option('ipara_db_version');
    flush_rewrite_rules();
}

function ipara_install_data()
{
    global $wpdb;
}

add_action('plugins_loaded', 'woocommerce_ipara_payment_init', 0);

add_filter('woocommerce_payment_gateways', 'woocommerce_add_ipara_woocommerce');

function woocommerce_add_ipara_woocommerce($methods)
{
    $methods[] = 'WC_Gateway_Ipara';
    return $methods;
}

function ipara_checkout_form_load_plugin_textdomain()
{
    load_plugin_textdomain('ipara-woocommerce', false, plugin_basename(dirname(__FILE__)) . '/i18n/languages/');
}

add_action('plugins_loaded', 'ipara_checkout_form_load_plugin_textdomain');

function woocommerce_ipara_payment_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_Ipara extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->id = 'ipara';
            $this->method_title = __('ipara Payment', 'ipara-woocommerce');
            $this->method_description = __('ipara Payment Module', 'ipara-woocommerce');
            $this->icon = plugins_url('/ipara-woocommerce/assets/img/cards.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->supports = array('products', 'refunds');
            $this->rates = get_option('ipara_rates');
            $this->init_form_fields();
            $this->init_settings();
            $this->ipara_woocommerce_public_key = $this->settings['ipara_public_key'];
            $this->ipara_woocommerce_private_key = $this->settings['ipara_private_key'];
            $this->ipara_settings_testmode = $this->settings['ipara_settings_testmode'];
            $this->ipara_settings_threeDMode = $this->settings['ipara_settings_threeDMode'];

            $this->title = $this->settings['title'];
            $this->installments_mode = $this->settings['ipara_settings_installement'];
            $this->description = $this->settings['description'];
            $this->enabled = $this->settings['enabled'];
            $this->order_button_text = $this->settings['button_title'];

            add_action('init', array(&$this, 'check_ipara_response'));
            add_action('woocommerce_api_wc_gateway_ipara', array($this, 'check_ipara_response'));
            add_action('woocommerce_api_' . $this->id, array($this, 'ipara_bincheck'));
            add_action('admin_notices', array($this, 'checksFields'));
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
            }
            add_action('woocommerce_receipt_ipara', array($this, 'receipt_page'));

        }

        function checksFields()
        {
            global $woocommerce;

            if ($this->enabled == 'no') {
                return;
            }

        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'ipara-woocommerce'),
                    'label' => __('Enable ipara Payment', 'ipara-woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'ipara-woocommerce'),
                    'type' => 'text',
                    'description' => __('This message will show to the user during checkout.', 'ipara-woocommerce'),
                    'default' => 'Kredi Kartı İle Öde',
                ),
                'description' => array(
                    'title' => __('Description.', 'ipara-woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the description which the user sees during checkout.', 'ipara-woocommerce'),
                    'default' => __('Pay with your credit card via ipara.', 'ipara-woocommerce'),
                    'desc_tip' => true,
                ),
                'button_title' => array(
                    'title' => __('Checkout Button.', 'ipara-woocommerce'),
                    'type' => 'text',
                    'description' => __('Checkout Button.', 'ipara-woocommerce'),
                    'default' => __('Pay With Credit Card.', 'ipara-woocommerce'),
                    'desc_tip' => true,
                ),
                'ipara_public_key' => array(
                    'title' => __('ipara public key.', 'ipara-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('public key Given by ipara System.', 'ipara-woocommerce'),
                ),
                'ipara_private_key' => array(
                    'title' => __('ipara secret key.', 'ipara-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('secret key Given by ipara System.', 'ipara-woocommerce'),
                ),
                'ipara_settings_installement' => array(
                    'title' => __('Installments Options.', 'ipara-woocommerce'),
                    'type' => 'select',
                    'default' => 'off',
                    'options' => array(
                        'off' => __('OFF', 'ipara-woocommerce'),
                        'on' => __('ON', 'ipara-woocommerce'),
                    ),
                ),
                'ipara_settings_threeDMode' => array(
                    'title' => __('ThreeD Mode.', 'ipara-woocommerce'),
                    'type' => 'select',
                    'default' => 'off',
                    'options' => array(
                        'off' => __('3D Payment.', 'ipara-woocommerce'),
                        'on' => __('Non 3D Payment.', 'ipara-woocommerce'),
                    ),
                ),
                'ipara_settings_testmode' => array(
                    'title' => __('Payment Mode.', 'ipara-woocommerce'),
                    'type' => 'select',
                    'default' => 'T',
                    'options' => array(
                        'T' => __('Test.', 'ipara-woocommerce'),
                        'P' => __('Live.', 'ipara-woocommerce'),
                    ),
                ),

            );
        }

        public function admin_options()
        {

            $this->rates = get_option('ipara_rates');
            if (isset($_POST['ipara_rates'])) {
                KahveDigitalIpara::register_all_ins();
            }

            $ipara_url = plugins_url() . IPARA_PLUGIN . '/assets/img/logo.svg';
            echo '<img src="' . $ipara_url . '" width="150px"/>';
            echo '<h2>ipara woocommerce</h2><hr/>';
            echo '<table class="form-table">';
            $this->generate_settings_html();

            if ($this->rates == false) {
                $installments = KahveDigitalIpara::createRatesUpdateForm(KahveDigitalIpara::setRatesDefault());
            } else {
                $installments = KahveDigitalIpara::createRatesUpdateForm(get_option('ipara_rates'));
            }
            echo "<hr/><h1>";
            echo __('installments options.', 'ipara-woocommerce');
            echo "</h1><hr/> ";
            echo $installments;

            echo '</table>';
        }
        function ipara_bincheck()
        {
            header('HTTP/1.1 200 OK');
            if (empty($_POST["BinNumber"])) {die();}
            include_once IPARA_PATH . '/lib/Settings.php';
            include_once IPARA_PATH . '/lib/base.php';
            include_once IPARA_PATH . '/lib/helper.php';
            include_once IPARA_PATH . '/lib/BinNumberInquiryRequest.php';
            include_once IPARA_PATH . '/lib/restHttpCaller.php';

            $settings = new Ipara_Settings();
            $settings->PublicKey = $this->settings['ipara_public_key'];
            $settings->PrivateKey = $this->settings['ipara_private_key'];
            $settings->Mode = $this->settings['ipara_settings_testmode'];

            $request = new BinNumberInquiryRequest();
            $request->binNumber = $_POST["BinNumber"];
            $response = BinNumberInquiryRequest::execute($request, $settings);

            $output = Helper::formattoJSONOutput($response);
            $info = json_decode($output,1);
            $info['is_available'] = false;
            if(isset($info['result']) && $info['result']=='1'){
                $order_id = $_POST['order_id'];
                $instalment_order = $this->instalment_option_for_order($order_id, $info['cardFamilyName']);
                $info['is_available'] = $instalment_order['is_available'];
                $info['table'] = $instalment_order['table'];
            }
            $output = json_encode($info);
            print_r($output);die();
        }
        function ipara_bininfo()
        {
            if (empty($_POST["BinNumber"])) {die();}
            include_once IPARA_PATH . '/lib/Settings.php';
            include_once IPARA_PATH . '/lib/base.php';
            include_once IPARA_PATH . '/lib/helper.php';
            include_once IPARA_PATH . '/lib/BinNumberInquiryRequest.php';
            include_once IPARA_PATH . '/lib/restHttpCaller.php';

            $settings = new Ipara_Settings();
            $settings->PublicKey = $this->settings['ipara_public_key'];
            $settings->PrivateKey = $this->settings['ipara_private_key'];
            $settings->Mode = $this->settings['ipara_settings_testmode'];

            $request = new BinNumberInquiryRequest();
            $request->binNumber = $_POST["BinNumber"];
            $response = BinNumberInquiryRequest::execute($request, $settings);
            $info = json_decode($response,1);
            return $info;
        }

        private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
        {

            if (PHP_VERSION_ID < 70300) {

                setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
            } else {
                setcookie($name, $value, [
                    'expires' => $expire,
                    'path' => $path,
                    'domain' => $domain,
                    'samesite' => 'None',
                    'secure' => $secure,
                    'httponly' => $httponly,
                ]);

            }
        }

        function iparaCheckOutRequest($order_id)
        {
            global $woocommerce;
            if (version_compare(get_bloginfo('version'), '4.5', '>=')) {
                wp_get_current_user();
            } else {
                get_currentuserinfo();
            }

            $order = new WC_Order($order_id);
            $customerCart = $woocommerce->cart->get_cart();

            $ip = $_SERVER['REMOTE_ADDR'];
            $siteLanguage = get_locale();
            $user_meta = $order->get_user_id();
            $siteLang = explode('_', get_locale());
            $locale = ($siteLang[0] == "tr") ? "tr" : "en";
            $phone = !empty($order->get_billing_phone()) ? $order->get_billing_phone() : 'NOT PROVIDED';
            $email = !empty($order->get_billing_email()) ? $order->get_billing_email() : 'NOT PROVIDED';
            $city_buyer = WC()->countries->states[$order->get_billing_country()][$order->get_billing_state()];

            $city = !empty($city_buyer) ? $city_buyer : 'NOT PROVIDED';

            $order_amount = $order->get_total();
            $currency = $order->get_currency();

            $city1 = isset(WC()->countries->states[$order->get_shipping_country()][$order->get_shipping_state()]) ? WC()->countries->states[$order->get_shipping_country()][$order->get_shipping_state()] : '';
            $city2 = isset(WC()->countries->states[$order->get_shipping_state()]) ? WC()->countries->states[$order->get_shipping_state()] : '';
            $countryShipping = isset(WC()->countries->countries[$order->get_shipping_country()]) ? WC()->countries->countries[$order->get_shipping_country()] : '';
            $cityShipping = $city1 . $city2;
            $wooCommerceCookieKey = 'wp_woocommerce_session_';
            foreach ($_COOKIE as $name => $value) {
                if (stripos($name, $wooCommerceCookieKey) === 0) {
                    $wooCommerceCookieKey = $name;
                }
            }

            $setCookie = $this->setcookieSameSite($wooCommerceCookieKey, $_COOKIE[$wooCommerceCookieKey], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);

            if ($currency == 'TRY') {$currency = "TL";}

            $user_meta = get_user_meta(get_current_user_id());

            function replaceSpaceIparaCard($veri)
            {
                $veri = str_replace("/s+/", "", $veri);
                $veri = str_replace(" ", "", $veri);
                $veri = str_replace(" ", "", $veri);
                $veri = str_replace(" ", "", $veri);
                $veri = str_replace("/s/g", "", $veri);
                $veri = str_replace("/s+/g", "", $veri);
                $veri = trim($veri);
                return $veri;
            }
            $name = $_POST['card-name'];
            $number = $_POST['number'];
            $expiry = $_POST['expiry'];
            $cvc = $_POST['cvc'];
            $total = $_POST['iparatotal'];
            $installement = isset($_POST['taksitsayisi'])?$_POST['taksitsayisi']:1;
            $bank = isset($_POST['bank'])?$_POST['bank']:1;

            $error_message = false;
            if($bank && $installement>1){
                $rates = KahvedigitalIpara::calculatePrices($order->get_total(), $this->rates);
                if(isset($rates[$bank]) && isset($rates[$bank]['installments'])){
                    if(isset($rates[$bank]['installments'][$installement])){
                        if($total < $rates[$bank]['installments'][$installement]['total']){
                            $error_message = 'Taksitli Tutar Hatası: Taksit sayısı ile taksit tutarı eşleşmedi.';
                        }
                    } else {
                        $error_message = 'Taksit Sayısı Hatası: KArtınız seçtiğiniz taksit sayısını desteklememektedir.';
                    }
                } else {
                    $error_message = 'Taksit yapılmayan kart hatası. Seçtiğiniz karta taksit yapılamamaktadır.';
                }
            } else {
                if($order->get_total() < $total ){
                    $error_message = 'Toplam Tutar Hatası: Tek çekimli işlemlerde ödeme tutarı sepet tutarından düşük olamaz.';
                }
            }
            if($error_message!==false){
                $order->add_order_note($error_message, 0, true);
                $redirectUrl = remove_query_arg('pay_for_order', $order->get_checkout_payment_url());
                setFlash('ipara_error_message', $error_message.' Lütfen tekrar deneyiniz ve sorun devam ederse bizimle iletişime geçiniz.');
                return wp_redirect($redirectUrl);
            }

            $expiry = explode("/", $expiry);
            $expiryMM = $expiry[0];
            $expiryYY = $expiry[1];
            $expiryMM = wc_clean($expiryMM);
            $expiryYY = wc_clean($expiryYY);
            setFlash('card-name', $name);
            setFlash('number', $number);
            $number = replaceSpaceIparaCard(wc_clean($number));

            $paid = number_format($total, 2, '.', '');
            $paid = str_replace('.', '', $paid);
            $paid = str_replace(' ', '', $paid);
            $paid = str_replace(',', '', $paid);

            include_once IPARA_PATH . '/lib/Settings.php';
            include_once IPARA_PATH . '/lib/base.php';
            include_once IPARA_PATH . '/lib/ApiPaymentRequest.php';
            include_once IPARA_PATH . '/lib/helper.php';
            include_once IPARA_PATH . '/lib/Api3DPaymentRequest.php';
            include_once IPARA_PATH . '/lib/BinNumberInquiryRequest.php';
            include_once IPARA_PATH . '/lib/restHttpCaller.php';

            $settings = new Ipara_Settings();
            $settings->PublicKey = $this->settings['ipara_public_key'];
            $settings->PrivateKey = $this->settings['ipara_private_key'];
            $settings->Mode = $this->settings['ipara_settings_testmode'];
            $threeD = $this->settings['ipara_settings_threeDMode'];

            $request = new Api3DPaymentRequest();
            $request->OrderId = $order_id . '-' . Helper::Guid();
            $request->Echo = "Echo";
            $request->Mode = $settings->Mode;
            $request->Amount = $paid; //
            $request->CardOwnerName = $name;
            $request->CardNumber = $number;
            $request->CardExpireMonth = $expiryMM;
            $request->CardExpireYear = $expiryYY;
            $request->Installment = $installement;
            $request->ThreeDSecureCode = $this->settings['ipara_public_key'];

            $request->Cvc = $cvc;
            $request->SuccessUrl = add_query_arg('wc-api', 'WC_Gateway_Ipara', $order->get_checkout_order_received_url());
            $request->FailUrl = add_query_arg('wc-api', 'WC_Gateway_Ipara', $order->get_checkout_order_received_url());

            // region Sipariş veren bilgileri
            $request->Purchaser = new Purchaser();
            $request->Purchaser->Name = $order->get_billing_first_name();
            $request->Purchaser->SurName = $order->get_billing_last_name();

            $request->Purchaser->Email = $email;
            $request->Purchaser->ClientIp = Helper::get_client_ip();
            // endregion

            // region Fatura bilgileri
            $request->Purchaser->InvoiceAddress = new PurchaserAddress();
            $request->Purchaser->InvoiceAddress->Name = $order->get_billing_first_name();
            $request->Purchaser->InvoiceAddress->SurName = $order->get_billing_last_name();
            $request->Purchaser->InvoiceAddress->Address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
            $request->Purchaser->InvoiceAddress->ZipCode = $order->get_billing_postcode();
            // endregion

            // region Kargo Adresi bilgileri
            $request->Purchaser->ShippingAddress = new PurchaserAddress();
            $request->Purchaser->ShippingAddress->Name = $order->get_shipping_first_name();
            $request->Purchaser->ShippingAddress->SurName = $order->get_shipping_last_name();
            $request->Purchaser->ShippingAddress->Address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
            $request->Purchaser->ShippingAddress->ZipCode = $order->get_shipping_postcode();

            // endregion
            $ProductsBasket = $this->generateBasketItems($customerCart, $order);
            // region Ürün bilgileri

            $request->Products = $ProductsBasket;
            // $request = $request->toXmlString();
            $message = "Seçilen Taksit Sayısı: ".$request->Installment;
            $message .= "\nTaksit Farkı: ".($total - $order->get_total());
            $order->add_order_note($message, 0, true);
            if ($threeD == "on") {
                $request->ThreeD = "false";
                $response = $request->execute($request, $settings);
                $output = Helper::formattoXMLOutput($response);
                $xml = simplexml_load_string($output);

                $xmlToarray = json_decode(json_encode($xml), true);
//print_r([483, $xmlToarray]);die();
                if ($xmlToarray['result']) {
                    $order = new WC_Order($order_id);

                    $comissionRate = $xmlToarray['commissionRate'];
                    $amount = $xmlToarray['amount']/100;
                    $amount = number_format($amount, 2, '.', '');
                    $comissionTotal = ($amount * $comissionRate) / 100;
                    $fee_amount = $amount - $order->get_total();
                    $fee_message = false;
                    if($fee_amount<0){
                        $fee_message = 'Kart İndirimi';
                    } elseif($fee_amount>0) {
                        $fee_message = 'Taksit Farkı';
                    }
                    if($fee_message){
                        $item_fee = new WC_Order_Item_Fee();
                        $item_fee->set_name( $fee_message); // Generic fee name
                        $item_fee->set_amount( $fee_amount ); // Fee amount
                        $item_fee->set_tax_class( '' ); // default for ''
                        $item_fee->set_tax_status( 'none' ); // or 'none'
                        $item_fee->set_total( $fee_amount ); // Fee amount
                        $item_fee->calculate_taxes( 0 );
                        $order->add_item( $item_fee );

                    }

                    $orderMessage = 'Payment ID: ' . $orderid;
                    $orderMessage .= 'Toplam Çekilen: ' . $amount."\n";
                    $orderMessage .= 'Ödeme Komisyonu: ' . $comissionRate . " Ödeme Komisyon Toplamı: " . $comissionTotal;
                    $order->add_order_note($orderMessage, 0, true);
                    $order->payment_complete();
                    $woocommerce->cart->empty_cart();
                    $checkoutOrderUrl = $order->get_checkout_order_received_url();
                    $redirectUrl = add_query_arg(array('msg' => 'Thank You', 'type' => 'woocommerce-message'), $checkoutOrderUrl);
                    return wp_redirect($redirectUrl);
                } else {
                    $message = sanitize_text_field($xmlToarray['responseMessage']);
                    $message = !empty($message) ? $message : "Invalid Request";
                    $order = new WC_Order($order_id);
                    $order->update_status('failed', sprintf(__('ipara payment failed', 'ipara-woocommerce'), $message));
                    $order->add_order_note($message, 0, true);
                    wc_add_notice(__($message, 'ipara-woocommerce'), 'error');
                    $redirectUrl = $woocommerce->cart->get_cart_url();
                    return wp_redirect($redirectUrl);
                }
            } else {
                $response = $request->execute3D($settings); // 3D secure ile ödeme yapma servis çağrısının yapıldığı kısımdır.
                print $response;
            }

            // return $response;

        }

        public function generateBasketItems($items, $order)
        {

            $itemSize = count($items);

            if (!$itemSize) {

                return $this->calcProduct($order);
            }

            $keyNumber = 0;

            foreach ($items as $key => $item) {

                $productId = $item['product_id'];
                $product = wc_get_product($productId);
                $realPrice = $this->realPrice($item['line_subtotal'], $product->get_price());

                if ($realPrice && $realPrice != '0' && $realPrice != '0.0' && $realPrice != '0.00' && $realPrice != false) {

                    $basketItems[$keyNumber] = new Product();

                    $basketItems[$keyNumber]->Quantity = $item['quantity'];
                    $basketItems[$keyNumber]->Price = $this->priceParser($realPrice / $item['quantity']);
                    $basketItems[$keyNumber]->Title = $product->get_title();
                    $basketItems[$keyNumber]->Code = $product->get_title();

                    $keyNumber++;

                }

            }

            $shipping = $order->get_total_shipping() + $order->get_shipping_tax();

            if ($shipping && $shipping != '0' && $shipping != '0.0' && $shipping != '0.00' && $shipping != false) {

                $endKey = count($basketItems);

                $basketItems[$endKey] = new Product();

                $basketItems[$endKey]->Quantity = 1;
                $basketItems[$endKey]->Price = $this->priceParser($shipping);
                $basketItems[$endKey]->Title = 'Cargo';
                $basketItems[$endKey]->Code = $product->get_title();

            }

            return $basketItems;

        }
        public function realPrice($salePrice, $regularPrice)
        {

            if (empty($salePrice)) {

                $salePrice = $regularPrice;
            }

            return $salePrice;

        }
        public function priceParser($price)
        {
            $price = number_format($price, 2, '.', '');
            $price = str_replace('.', '', $price);
            $price = str_replace(' ', '', $price);
            $price = str_replace(',', '', $price);

            return $price;
        }

        public function calcProduct($order)
        {

            $keyNumber = 0;

            $basketItems[$keyNumber] = new stdClass();

            $basketItems[$keyNumber]->Quantity = $item['quantity'] ?? 1;
            $basketItems[$keyNumber]->Price = $this->priceParser($order->get_total());
            $basketItems[$keyNumber]->Title = 'Woocommerce - Custom Order Page';
            $basketItems[$keyNumber]->Code = 'CUSTOM';

            return $basketItems;
        }
        function instalment_option_for_order($orderid, $bank){
            $result = [
                'is_available' => 0,
                'table' => '',
            ];
            global $woocommerce;
            $error_message = false;
            $order = new WC_Order($orderid);
            $rates = KahvedigitalIpara::calculatePrices($order->get_total(), $this->rates);
            $cards = KahveDigitalIpara::getAvailablePrograms();
            $status = $order->get_status();
            $ordertotal = $order->get_total();
            $currency = $order->get_currency();
            $installments_mode = $this->settings['ipara_settings_installement'];

            if(!isset($rates[$bank])){
                $bank = strtolower($bank);
            }

            $result['table'] = "Taksit Özelliği Devre Dışı";
            if($installments_mode=='on'){
                $result['table'] = "Taksit Tanımlanmamış";
                if(isset($rates[$bank]) && $rates[$bank]['installments']){
                    $result['is_available'] = 1;
                    $result['table'] = '<div class="ipara-container instalment-options">';
                    $result['table'] .= '<span class="cc-label"></span>';
                    $result['table'] .= '<input type="hidden" name="bank" value="'.$bank.'"></input>';
                    $result['table'] .= '<select name="taksitsayisi" class="taksit" >';
                    $showtotal = '';
                    $paytotal = '';
                    foreach($rates[$bank]['installments'] as $ins=>$installment){

                        if(!$installment['active']) continue;
                        if($ins=='1'){
                            $paytotal = $installment['total'];
                            $showtotal = wc_price($installment['total']);
                            $selected = "checked";
                            $installment_text = __('Tek çekim');
                        } else{
                            $selected = "";
                            $installment_text = $ins.' '.__('Taksit', 'ipara-woocommerce');
                        }

                        $result['table'] .= '<option value="'.$ins.'" data-total="'.$installment['total'].'">'.$installment_text.' ('.$installment['total'].' / '. $installment['monthly'].')</option>';
                    }
                    $card_logo = isset($cards[$bank])?$cards[$bank]['logo']:'/assets/img/'.$bank.'.svg';

                    $result['table'] .= '</select>';
                    $result['table'] .= '<span class="cc-label"></span>
<button type="submit" name="iparatotal" value="'.$paytotal.'" class="iparaode taksit button alt iparaode" style=""><span class="showtotal">'.$showtotal.'</span> <span class="iparaOdemeText">'. __('Pay', 'ipara-woocommerce').'</span></button>';
                    $result['table'] .= '<span class="cc-label"></span><div class="taksit-title "><img src="'.$card_logo.'"></div>';
                    $result['table'] .= '<div style="text-align: center">'.$rates[$bank]['message'].'</div><span class="cc-label"></span>';
                    $result['table'] .= '</div>';
                }
            }
            return $result;
        }
        function receipt_page($orderid)
        {

            global $woocommerce;
            $error_message = false;
            $order = new WC_Order($orderid);
            $rates = KahvedigitalIpara::calculatePrices($order->get_total(), $this->rates);
            $cards = KahveDigitalIpara::getAvailablePrograms();
            $status = $order->get_status();
            $showtotal = $order->get_total();
            $currency = $order->get_currency();
            $installments_mode = $this->settings['ipara_settings_installement'];
            if (isset($_POST['order_id']) and $_POST['order_id'] == $orderid) {
                $record = $this->iparaCheckOutRequest($orderid);
            } else {
                $text_credit_card = __('Credit Cart Form', 'ipara-woocommerce');
                include dirname(__FILE__) . '/lib/form.php';
            }
        }

        public function check_ipara_response()
        {

            global $woocommerce;
            $orderIdFull = explode("-", $_POST['orderId']);
            $orderExplodeId = $orderIdFull[0];
            if ($_POST['result'] == true) {
                include_once IPARA_PATH . '/lib/Settings.php';
                include_once IPARA_PATH . '/lib/base.php';
                include_once IPARA_PATH . '/lib/helper.php';
                include_once IPARA_PATH . '/lib/ApiPaymentRequest.php';

                include_once IPARA_PATH . '/lib/PaymentInquiryRequest.php';
                include_once IPARA_PATH . '/lib/ThreeDPaymentInitRequest.php';
                include_once IPARA_PATH . '/lib/ThreeDPaymentCompleteRequest.php';
                include_once IPARA_PATH . '/lib/restHttpCaller.php';

                $settings = new Ipara_Settings();
                $settings->PublicKey = $this->settings['ipara_public_key'];
                $settings->PrivateKey = $this->settings['ipara_private_key'];
                $settings->Mode = $this->settings['ipara_settings_testmode'];
                $request = new PaymentInquiryRequest();

                $request->orderId = $orderExplodeId;
                $request->Echo = "Echo";
                $request->Mode = $settings->Mode;
                $response = PaymentInquiryRequest::execute($request, $settings);

                $output = Helper::formattoXMLOutput($response);
                $xml = simplexml_load_string($output);

                $xmlToarray = json_decode(json_encode($xml), true);
//print_r([708, $xmlToarray, $_POST]);die();
                if ($xmlToarray['result']) {
                    $orderId = wc_clean($orderExplodeId);
                    $order = new WC_Order($orderId);
                    $comissionRate = $_POST['commissionRate'];
                    $amount = $_POST['amount']/100;
                    $amount = number_format($amount, 2, '.', '');

                    $comissionTotal = ($amount * $comissionRate) / 100;
                    $orderMessage = 'Payment ID: ' . $orderId . "\n";
                    $orderMessage .= 'Toplam Çekilen: ' . $amount."\n";
                    $orderMessage .= 'Ödeme Komisyonu: ' . $comissionRate . " Ödeme Komisyon Toplamı:" . $comissionTotal;

                    $fee_amount = $amount - $order->get_total();
                    $fee_message = false;
                    if($fee_amount<0){
                        $fee_message = 'Kart İndirimi';
                    } elseif($fee_amount>0) {
                        $fee_message = 'Taksit Farkı';
                    }
                    if($fee_message){
                        $item_fee = new WC_Order_Item_Fee();
                        $item_fee->set_name( $fee_message ); // Generic fee name
                        $item_fee->set_amount( $fee_amount ); // Fee amount
                        $item_fee->set_tax_class( '' ); // default for ''
                        $item_fee->set_tax_status( 'none' ); // or 'none'
                        $item_fee->set_total( $fee_amount ); // Fee amount
                        $item_fee->calculate_taxes( 0 );
                        $order->add_item( $item_fee );
                    }

                    $order->calculate_totals();

                    $order->update_status('on-hold');

                    $order->save();

                    $order->add_order_note($orderMessage, 0, true);
                    $order->payment_complete();
                    $woocommerce->cart->empty_cart();
                    $checkoutOrderUrl = $order->get_checkout_order_received_url();
                    $redirectUrl = add_query_arg(array('msg' => 'Thank You', 'type' => 'woocommerce-message'), $checkoutOrderUrl);
                    return wp_redirect($redirectUrl);
                } else {
                    $message = sanitize_text_field($xmlToarray['responseMessage']);
                    $message = !empty($message) ? $message : "Invalid Request";
                    $order = new WC_Order($orderExplodeId);
                    $order->update_status('failed', sprintf(__('ipara payment failed', 'ipara-woocommerce'), $message));
                    $order->add_order_note($message, 0, true);
                    //wc_add_notice(__($message, 'ipara-woocommerce'), 'error');
                    setFlash('ipara_error_message', $message);
                    $redirectUrl = $woocommerce->cart->get_cart_url();
                    return wp_redirect($redirectUrl);
                }
            } else {
                $message = sanitize_text_field($_POST["errorMessage"]);
                $message = !empty($message) ? $message : "Invalid Request";
                $order = new WC_Order($orderExplodeId);
                $order->update_status('failed', sprintf(__('ipara payment failed', 'ipara-woocommerce'), $message));
                $order->add_order_note($message, 0, true);
                //wc_add_notice(__($message, 'ipara-woocommerce'), 'error');
                setFlash('ipara_error_message', $message);
                $redirectUrl = remove_query_arg('pay_for_order', $order->get_checkout_payment_url());
                return wp_redirect($redirectUrl);
            }

        }

        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
                /* 2.1.0 */
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
                /* 2.0.0 */
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
            return array(
                'result' => 'success',
                'redirect' => $checkout_payment_url,
            );
        }
    }
    function setFlash($key, $message){
        if( !session_id() )
        {
            session_start();
        }
        $_SESSION['ipara'][$key] = $message;
    }
    function getFlash($key){
        if( !session_id() )
        {
            session_start();
        }
        if(isset($_SESSION['ipara'][$key])){
            $message = $_SESSION['ipara'][$key];
            unset($_SESSION['ipara'][$key]);
            return $message;
        }
        return false;
    }

}
