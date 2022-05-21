<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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
<link rel="stylesheet" type="text/css" href="<?php echo $ipara_url ?>assets/css/ipara.css?_v=1.0.15">
<div class="woocommerce-notices-wrapper"><?php  echo getFlash('ipara_error_message') ;?></div>
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
                                <input class="c-card card-name" placeholder="<?php echo __('Name on Card', 'ipara-woocommerce'); ?>" type="text" required    oninvalid="this.setCustomValidity('Kart sahibinin adını yazınız.')"  oninput="setCustomValidity('')" name="card-name" id="card-name">
                            </div>
                            <input value="<?php echo $orderid ?>" name="order_id" type="hidden">
                            <div class="iparacard iparaorta">
                                <i class="iparacardicon"></i>
                                <input value="" id="iparacardnumber" class="c-card cardnumber" placeholder="<?php echo __('Card Number', 'ipara-woocommerce'); ?>" required   oninvalid="this.setCustomValidity('Kartın üzerindeki 16 haneli numarayı giriniz.')" oninput="setCustomValidity('')" type="tel" name="number" >
                            </div>
                            <div class="iparaleft iparaexpry">
                                <input class="c-date c-card"  placeholder="<?php echo __('MM/YY', 'ipara-woocommerce'); ?>" type="tel" maxlength="7" required  oninvalid="this.setCustomValidity('Kartın son kullanma tarihini giriniz')" oninput="setCustomValidity('')" name="expiry" >
                            </div>
                            <div class="ipararight iparacvc">
                                <input class="card-cvc c-card" placeholder="CVC" required  type="number"  oninvalid="this.setCustomValidity('Kartın arkasındaki 3 ya da 4 basamaklı sayıyı giriniz')" oninput="setCustomValidity('')" name="cvc" >
                                <div class="ipara-i-icon"><img src="<?php echo $ipara_url ?>assets/img/icons/info.png" width="14px"> </div>
                            </div>
                        </div>
                        <div class="ipara-container tekcekim-container ">
                            <div class="tekcekim">
                                <li class="taksit-li " for="s-option" >
                                    <input type="radio" id="s-option"  name="iparatotal"  value="<?php echo $showtotal ?>" checked class="option-input taksitradio radio " >
                                    <label for="s-option">Tek Çekim</label>
                                    <div class="taksit-fiyat"> <?php echo $showtotal; ?></div>
                                    <div class="check"><div class="inside"></div></div>
                                </li>
                                <div class="taksit-secenek tek-cekim">
                                    <?php if ($installments_mode == 'on') {?>
                                        <h3 class="taksit-secenekleri"><?php echo __('All Installment', 'ipara-woocommerce'); ?></h3>
                                        <div class="logolar-ipara">
                                            <?php foreach ($cards as $card_code => $card) {?>
                                            <?php if($card['installments']) {?>
                                                <div class="ipara-banka-logo <?php echo $card_code; ?>-logo" data-banka="<?php echo $card_code; ?>"><img alt="<?=$card['name']?>" src="<?=$card['logo']?>"	></img></div>
                                            <?php }?>
                                            <?php }?>
                                        </div>
                                    <?php }?>
                                </div>
                            </div>
                            <button type="submit" class="iparaode" style=""><span class="iparaOdemeTutar"><?php echo wc_price($showtotal); ?></span> <span class="iparaOdemeText"> <?php echo __('Pay', 'ipara-woocommerce'); ?></span></button>
                        </div>
                        <?php if ($installments_mode == 'on') {?>
                            <div class="taksit-container ">
                                <?php foreach ($rates as $bank => $rate) {?>
                                    <div class="ipara-container instalment-table <?php echo $bank; ?>" style="display: none;">
                                        <?php $card_logo = isset($cards[$bank])?$cards[$bank]['logo']:'>assets/img/'.$bank.'.svg'; ?>
                                        <div class="taksit-title "><img src="<?php echo $card_logo ?>" alt="<?=$bank?>"></div>
                                        <div style="text-align: center"><?=$rates[$bank]['message']?></div>
                                        <?php for ($ins = 1; $ins < 13; $ins++) {?>
                                            <?php foreach ($rates as $banks => $rate) {?>
                                                <?php
                                                if($ins=='1'){
                                                    $installment_text = __('Tek çekim');
                                                } else{
                                                    $installment_text = $ins.' '.__('Installment', 'ipara-woocommerce');
                                                }
                                                ?>
                                                <?php if ($bank == $banks) {?>
                                                    <?php if ($rates[$banks]['installments'][$ins]['active'] == 1) {?>
                                                        <li class="taksit-li iparaorta">
                                                            <label for="s-option2"><?php echo $installment_text; ?> <span class="taksit-fiyat"> <?php echo $rates[$banks]['installments'][$ins]['total']; ?> / <?php echo $rates[$banks]['installments'][$ins]['monthly']; ?> </span></label>

                                                        </li>
                                                    <?php }?>
                                                <?php }?>
                                            <?php }?>
                                        <?php }?>
                                    </div>
                                <?php }?>
                                <div id="instalment-options" data-bin=""></div>
                            </div>
                        <?php }?>
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
        $('body').on('change', 'input[type=radio][name=iparatotal]', function () {
            $('#iparaode .showtotal').html($(this).prev('span').html());
        });
    });

    if (taksit == 'on') {
        $(document).ready(function () {
            $("#iparacardnumber").bind('paste', function() {
                $('#instalment-options').data('bin', '');
            });

            $(".ipara-banka-logo").click(function () {
                $('.instalment-table').hide();
                $( ".instalment-table." + $(this).data('banka') ).show();
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
    $(".ipara-i-icon img").hover(function () {
        $(".info-window").toggleClass("info-window-active");
    });
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