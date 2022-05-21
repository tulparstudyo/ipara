<?php
add_action('admin_menu', 'ipara_wooturk_add_menu');
add_action( 'admin_enqueue_scripts', 'ipara_wooturk_load_wp_media_files' );
function ipara_wooturk_load_wp_media_files( $page ) {
    if($page=='settings_page_ipara_wooturk_options'){
        wp_enqueue_media();
        wp_enqueue_script( 'ipara_wooturk_script', plugins_url( '/js/ipara_wooturk.js?_v='.time() , __FILE__ ), array('jquery'), '0.1' );
    }
}
add_action( 'wp_ajax_ipara_wooturk_get_image', 'ipara_wooturk_get_image'   );
function ipara_wooturk_get_image() {
    if(isset($_GET['id']) ){
        $id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
        $image = wp_get_attachment_image( $id, 'medium', false, array( 'id' => 'myprefix-preview-image' ) );
        $data = array(
            'url'    => wp_get_attachment_url($id),
            'image'  => $image,
        );
        wp_send_json_success( $data );
    } else {
        wp_send_json_error();
    }
}
function ipara_wooturk_add_menu(){
    add_options_page(
        'Ipara Bin Ayarları',
        'Ipara Bin Ayarları',
        'manage_options',
        'ipara_wooturk_options',
        'ipara_wooturk_settings_page'
    );
}
function ipara_wooturk_options(){

}
function ipara_wooturk_settings_page(){
    $action = add_query_arg( array(
        'page' => 'ipara_wooturk_options'
    ), admin_url( 'options-general.php' ) );
    if(isset($_POST['save'])){
        $defined_bins = [];
        if(isset($_POST['bins'])){
            $defined_bins = $_POST['bins'];
        }
        if(isset($_POST['new'])){
            foreach($_POST['new'] as $new){
                $defined_bins[$new['code']] =[
                    'code'=>$new['code'],
                    'name'=>$new['name'],
                    'bank'=>$new['bank'],
                    'installments'=>$new['installments']
                ] ;
            }
        }
        update_option("ipara_defined_bins", $defined_bins);
        wp_redirect($action);
    }
    $defined_bins = get_option("ipara_defined_bins");
    if(isset($_GET['default_cards'])){
        foreach( get_defined_bins() as $code => $defined_bin){
            if(!isset($defined_bins[$code])){
                $defined_bins[$code] = $defined_bin;
            }
        }
    }
    $ipara_bininfo = '';
    $BinNumber = '';
    if(isset($_POST['sorgula'])){
        $BinNumber = $_POST['BinNumber'];
        $_POST['BinNumber'] = substr($_POST['BinNumber'].'000000',0, 6);
        $ipara = new WC_Gateway_Ipara();
        $ipara_bininfo = $ipara->ipara_bininfo();
    }
    include_once('templates/bin-list.php');
}
function get_defined_bins()
{
    return array(
        'axess' => array('name' => 'Axess', 'bank' => 'Akbank A.Ş.', 'installments' => true),
        'world' => array('name' => 'WordCard', 'bank' => 'Yapı Kredi Bankası', 'installments' => true),
        'bonus' => array('name' => 'BonusCard', 'bank' => 'Garanti Bankası A.Ş.', 'installments' => true),
        'cardfinans' => array('name' => 'CardFinans', 'bank' => 'FinansBank A.Ş.', 'installments' => true),
        'maximum' => array('name' => 'Maximum', 'bank' => 'T.C. İş Bankası', 'installments' => true),
        'paraf' => array('name' => 'Paraf', 'bank' => 'Halk Bankası', 'installments' => true),
        'combo' => array('name' => 'Kart Combo', 'bank' => 'Ziraat Bankası', 'installments' => true),
        'kuveyt' => array('name' => 'Sağlam Kart', 'bank' => 'KuveytTürk', 'installments' => true),
        // 'amex' => array('name' => 'Amex', 'bank' => 'Amerikan Express', 'installments' => true),
    );
}

