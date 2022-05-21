<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$ipara_error_message = getFlash('ipara_error_message');
$ipara_url = plugins_url('/', dirname(__FILE__));
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="<?php echo $ipara_url ?>/assets/js/card.js"></script>
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
<div class="woocommerce-notices-wrapper"><?php if($ipara_error_message) echo wc_print_notice( $ipara_error_message ,  'error' ) ;?></div>
<div class= "row">
        <div id="ipara-form" class="col-xs-12 iparaform" >
            <h3 class="odemeform-baslik"><?php echo __('Payment Form', 'ipara-woocommerce') ?></h3>
            <div class="hepsi">
                <div class="form-group active ipara">
                    <form method="POST" id="iparapostform" action="">
                        <div class="card-wrapper" style="margin-left:5px; display: none"></div>
                        <div class="ipara-container card-container">
                            <h6 class="odemeform-baslik"><?php echo __('Credit Cart', 'ipara-woocommerce') ?></h6>
                            <div class="iparaname iparafull">
                                <span class="cc-label"><?php echo __('Name on Card', 'ipara-woocommerce'); ?></span>
                                <input type="text" class="input-text c-card card-name" type="text" required    oninvalid="this.setCustomValidity('Kart sahibinin adını yazınız.')"  oninput="setCustomValidity('')" name="card-name" id="card-name" value="<?=getFlash('card-name')?>">
                            </div>
                            <input value="<?php echo $orderid ?>" name="order_id" type="hidden">
                            <div class="iparacard iparaorta">

                                <span class="cc-label"><?php echo __('Card Number', 'ipara-woocommerce'); ?></span>
                                <input type="text" value="" id="iparacardnumber" class="input-text c-card cardnumber" required   oninvalid="this.setCustomValidity('Kartın üzerindeki 16 haneli numarayı giriniz.')" oninput="setCustomValidity('')" type="tel" name="number"  value="<?=getFlash('number')?>">
                            </div>
                            <div class="iparaleft iparaexpry">
                                <span class="cc-label"><?php echo __('Card Expiry', 'ipara-woocommerce'); ?></span>
                                <input type="text" class="input-text c-date c-card"  type="tel" maxlength="7" required  oninvalid="this.setCustomValidity('Kartın son kullanma tarihini giriniz')" oninput="setCustomValidity('')" name="expiry" >
                            </div>
                            <div class="ipararight iparacvc">
                                <span class="cc-label"><?php echo __('CVC Number', 'ipara-woocommerce'); ?></span>
                                <input type="text" class="input-text card-cvc c-card" required  type="number"  oninvalid="this.setCustomValidity('Kartın arkasındaki 3 ya da 4 basamaklı sayıyı giriniz')" oninput="setCustomValidity('')" name="cvc" >
                            </div>
                        </div>
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
    var theme = "<?php echo $ipara_url ?>";
    var taksit = "<?php echo $installments_mode ?>";
    new Card({
        form: document.querySelector('.hepsi'),
        container: '.card-wrapper',
        formSelectors: {
            nameInput: 'input#card-name'
        },
    });
    $(document).ready(function () {
        $('body').on('change', 'select.taksit', function () {
            $('button.iparaode.taksit').val( $(this).find('option:selected').data('total') );
            $('button.iparaode.taksit .showtotal').html( $(this).find('option:selected').data('total') + '<?=get_woocommerce_currency_symbol()?>' );
        });
    });

    if (taksit == 'on') {
        $(document).ready(function () {
            $("#iparacardnumber").bind('paste', function() {
                $('#instalment-options').data('bin', '');
            });

            $.ajaxSetup({cache: false});
            $('#iparacardnumber').on('keyup change', function () {
                $('.instalment-table').hide();
                var searchField = $('#iparacardnumber').val();
                searchField = searchField.replace(/\s/g, '').substring(0,6);
                    if(searchField.length < 6){
                        $('.tekcekim-container').show();
                        $('#instalment-options').html('');
                        $('#instalment-options').data('bin', '');
                        return;
                    }
                console.log($('#instalment-options').data('bin') + '==' + searchField);
                if($('#instalment-options').data('bin')==searchField){
                    return ;
                }

                $('#instalment-options').data('bin', searchField);

                jQuery.ajax({
                    type: "POST",
                    url: "/ipara-woocommerce/wc-api/ipara/",
                    data: {
                        BinNumber: searchField,
                        order_id: '<?php echo $orderid ?>'
                    },
                    success: function (data) {
                        var response = JSON.parse(data);
                        console.log(response);
                        if(response.is_available){
                            $('.tekcekim-container').hide();
                            $('#instalment-options').html(response.table);
                        } else{
                            $('.tekcekim-container').show();
                            $('#instalment-options').html('');
                        }
                    },
                    error: function (errorThrown) {
                        $('.tekcekim-container').show();
                        $('#instalment-options').html('');
                        alert(errorThrown);
                    }
                });
            });
        });
    }

    $('.c-card').bind('keypress keyup keydown focus', function (e) {
        var ErrorInput = false;
        if ($("input.card-name").hasClass("jp-card-invalid")) {
            ErrorInput = true;
            $("input.card-name").addClass("border");
        }
        if ($("input.cardnumber").hasClass("jp-card-invalid")) {
            ErrorInput = true;
            $("input.cardnumber").addClass("border");
        }
        if ($("input.c-date").hasClass("jp-card-invalid")) {
            ErrorInput = true;
            $("input.c-date").addClass("border");
        }
        if ($("input.card-cvc").hasClass("jp-card-invalid")) {
            ErrorInput = true;
            $("input.card-cvc").addClass("border");
        }
        if (ErrorInput === true) {
            //$('.iparaode').attr("disabled", true);
            //$(".iparaode").css("opacity", "0.5");
        } else {
            $("input.card-name").removeClass("border");
            $("input.cardnumber").removeClass("border");
            $("input.c-date").removeClass("border");
            $("input.card-cvc").removeClass("border");
            //$('.iparaode').attr("disabled", false);
            //$(".iparaode").css("opacity", "1");
        }
    });
</script>