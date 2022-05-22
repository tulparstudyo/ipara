<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$ipara_error_message = getFlash('ipara_error_message');
$ipara_url = plugins_url('/', dirname(__FILE__));
?>
<script src="<?php echo $ipara_url ?>assets/js/card.js?_v=<?=time()?>"></script>
<?php if ($error_message) {?>
    <div class="row">
        <ul class="woocommerce-error" id="errDiv">
            <li>
                <?php echo __('Payment Error.', 'ipara-woocommerce') ?>
                <b><?php echo $error_message; ?></b><br/>
                <?php echo __('Please check the form and try again.', 'ipara-woocommerce') ?>
            </li>
        </ul>
    </div>
<?php }?>
<link rel="stylesheet" type="text/css" href="<?php echo $ipara_url ?>assets/css/ipara.css?_v=<?=time()?>">
<style>
    .card-js .icon{
        display: none;
        opacity: 0;
        visibility: hidden;
    }
    .card-js .expiry, .card-js .cvc{
        max-width: 100px;
    }

</style>
<div class="woocommerce-notices-wrapper"><?php if($ipara_error_message) echo wc_print_notice( $ipara_error_message ,  'error' ) ;?></div>
<div class= "row">
        <div id="ipara-form" class="col-xs-12 iparaform" >
            <h3 class="odemeform-baslik"><?php echo __('Payment Form', 'ipara-woocommerce') ?></h3>
            <div class="hepsi">
                <div class="form-group active ipara">
                    <form method="POST" id="iparapostform" action="">
                        <div class="card-wrapper" style="display: none" ></div>
                        <div class="ipara-container card-container">
                            <div class="iparaname iparafull">
                                <span class="cc-label"><?php echo __('Kart üzerindeki isim', 'ipara-woocommerce'); ?></span>
                            <input class="iparacard card-name" placeholder="" type="text" name="card-name" value="<?=getFlash('card-name')?>" required>
                            </div>
                            <div class="iparacard iparaorta">
                                <span class="cc-label"><?php echo __('Kart numarası', 'ipara-woocommerce'); ?></span>
                                <input placeholder="" type="tel" id="number" name="number" class="cardnumber" value="<?=getFlash('number')?>" required>
                            </div>
                            <div class="iparaleft iparaexpry">
                                <span class="cc-label"><?php echo __('Son kullanma tarihi', 'ipara-woocommerce'); ?></span>
                                <input placeholder="Gün/YY" type="tel" class="expiry" name="expiry" required maxlength="7">
                            </div>
                            <div class="ipararight iparacvc">
                                <span class="cc-label"><?php echo __('Güvenlik Kodu', 'ipara-woocommerce'); ?></span>
                                <input placeholder="CVC" type="number" class="cvc" name="cvc" required>
                            </div>
                        </div>
                        <input value="<?php echo $orderid ?>" name="order_id" type="hidden">

                        <div class="ipara-container tekcekim-container ">
                            <div class="tekcekim">
                                <span class="cc-label"></span>
                                <select name="taksitsayisi" >
                                    <option value="1" data-total="<?php echo $showtotal ?>" selected><?php echo __('Tek Çekim', 'ipara-woocommerce'); ?> <?php echo wc_price($showtotal); ?></option>
                                </select>
                            </div>
                            <span class="cc-label"></span>
                            <button type="submit" name="iparatotal" value="<?php echo $showtotal ?>" class="iparaode button alt" style=""><span class="iparaOdemeTutar"><?php echo wc_price($showtotal); ?></span> <span class="iparaOdemeText"> <?php echo __('Pay', 'ipara-woocommerce'); ?></span></button>
                        </div>
                        <div id="instalment-options" data-bin=""></div>
                    </form>
                </div>
            </div>
        </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function (){

        var c = new Card({
            form: document.querySelector('.ipara-container'),
            container: '.card-wrapper',
            formSelectors: {
                nameInput: 'input.iparacard.card-name'
            },

        });


        var taksit = "<?php echo $installments_mode ?>";
        jQuery(document).ready(function () {
            jQuery('body').on('change', 'select.taksit', function () {
                jQuery('button.iparaode.taksit').val( jQuery(this).find('option:selected').data('total') );
                jQuery('button.iparaode.taksit .showtotal').html( jQuery(this).find('option:selected').data('total') + '<?=get_woocommerce_currency_symbol()?>' );
            });
        });
        jQuery(".card-number").bind('paste', function() {
            jQuery('#instalment-options').data('bin', '');
        });

        jQuery.ajaxSetup({cache: false});
        if (taksit == 'on') {
            jQuery('.cardnumber').on('keyup change', function(e) {
                var searchField = jQuery(this).val().replace(/\s/g, '');
                //searchField = jQuery(searchField);
                console.log(searchField);
                if(searchField.length >= 16){
                    e.stopPropagation();
                    e.preventDefault();
                    e.returnValue = false;
                    e.cancelBubble = true;
                    return false;
                }
                searchField = searchField.substring(0,6);

                if(searchField.length < 6){
                    jQuery('.tekcekim-container').show();
                    jQuery('#instalment-options').html('');
                    jQuery('#instalment-options').data('bin', '');
                    return;
                }
                if(jQuery('#instalment-options').data('bin')==searchField){
                    return ;
                }
                jQuery('#instalment-options').data('bin', searchField);
                console.log('Bin checking');
                jQuery.ajax({
                    type: "POST",
                    url: "/ipara-woocommerce/wc-api/ipara/",
                    data: {
                        BinNumber: searchField,
                        order_id: '<?php echo $orderid ?>'
                    },
                    success: function (data) {
                        var response = JSON.parse(data);
                        console.log('Bin checked');

                        if(response.is_available){
                            jQuery('.tekcekim-container').hide();
                            jQuery('#instalment-options').html(response.table);
                        } else{
                            jQuery('.tekcekim-container').show();
                            jQuery('#instalment-options').html('');
                        }
                    },
                    error: function (errorThrown) {
                        jQuery('.tekcekim-container').show();
                        jQuery('#instalment-options').html('');
                        alert(errorThrown);
                    }
                });
            });
        }
        jQuery('#iparapostform').on('submit', function(e){
            if(jQuery('#number').val().replace(/\s/g, '').length !=16){
                alert("Kart numarası 16 haneli olmalıdır")
                e.preventDefault();

            }
        });
    });

</script>