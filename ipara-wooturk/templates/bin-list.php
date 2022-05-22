<div class="wrap woocommerce">
    <style>
        .form-table table td {
            margin-bottom: 1px;
            padding: 5px 1px;
            line-height: 1.3;
            vertical-align: middle;
        }

        tr.main-row {
            border-bottom: 1px solid;
        }
        img.card-logo {
            width: 100%;
            max-width: 64px;
            clear: both;
            display: block;
        }
    </style>

    <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active">Ayarlar</a>
        </nav>
        <div id="panel-setting">
            <h2>Ödemede Kullanılacak Özel Banka Kartları Ayarları</h2>
            <div id="store_address-description">
                <p>Bu sayfada tanımlanan ayarlar müşterinin ödeme sayfasında seçtiği karta göre ek imkanlar sağlamak için kullanılır. İndirim <b>Kart Ailesi</b> ne göre yapılır</p>
            </div>
            <div>
                <table id="bin-table" class="form-table">
                    <?php if($defined_bins){?>
                    <?php foreach($defined_bins as $bincode=>$bin){?>
                    <tr class="main-row" valign="top">
                        <th scope="row">
                            <label><?=$bin['name']?></label>
                            <label  class="select-card-image">
                                <img class="card-logo" src="<?=$bin['logo']?>">&#127912;
                            </label>
                            <input type="hidden" name="bins[<?=$bincode?>][logo]" value="<?=$bin['logo']?>" />
                        </th>
                        <td class="forminp forminp-text">
                            <table>
                                <tr>
                                    <td>Kart Ailesi</td>
                                    <td><input type="text" name="bins[<?=$bincode?>][bincode]" value="<?=$bincode?>"></td>
                                </tr>
                                <tr>
                                    <td>Kart Adı</td>
                                    <td><input type="text" name="bins[<?=$bincode?>][name]" value="<?=$bin['name']?>"></td>
                                </tr>
                                <tr>
                                    <td>Banka</td>
                                    <td><input type="text" name="bins[<?=$bincode?>][bank]" "type="text" style="" value="<?=$bin['bank']?>" class="" placeholder=""></td>
                                </tr>
                                <tr>
                                    <td>Taksit İmkanı</td>
                                    <td>
                                        <select name="bins[<?=$bincode?>][installments]">
                                            <?php if($bin['installments']){?>
                                                <option value="1" selected>Evet</option>
                                                <option value="0">Hayır</option>
                                            <?php } else { ?>
                                                <option value="1">Evet</option>
                                                <option value="0" selected>Hayır</option>
                                            <?php }  ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>

                        </td>
                        <td>
                            <button class="button-secondary delete-bin" type="button">Sil</button>
                        </td>
                    </tr>
                    <?php }?>

                    <?php } else { ?>
                        <tr><td><a href="<?=$action?>&default_cards">Ön Tanımlı Kartlar</a></td><td></td><td></td></tr>
                    <?php }?>
                </table>
            </div>
        </div>
        <p class="submit">
            <button name="save" class="button-primary woocommerce-save-button" type="submit" value="Save changes">Save changes</button>
            <button id="add-bin" class="button-secondary" type="button">Ekle</button>
            <input name="save" value="1" type="hidden">
        </p>
    </form>
    <template id="template-bin">
        <tr class="main-row" valign="top">
            <th scope="row">
                <label>Yeni Kredi Kart #{i}</label>
            </th>
            <td class="forminp forminp-text">
                <table>
                    <tr>
                        <td>Kart Ailesi</td>
                        <td><input type="text" name="new[{i}][bincode]" value="{bincode}"></td>
                    </tr>
                    <tr>
                        <td>Kart Adı</td>
                        <td><input type="text" name="new[{i}][name]" value="{name}"></td>
                    </tr>
                    <tr>
                        <td>Banka</td>
                        <td><input type="text" name="new[{i}][bank]" type="text" style="" value="{bank}" class="" placeholder=""></td>
                    </tr>
                    <tr>
                        <td>Taksit İmkanı</td>
                        <td>
                            <select name="new[{i}][installments]">
                                <option value="1" selected="">Evet</option>
                                <option value="0">Hayır</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <button class="button-secondary delete-bin" type="button">Sil</button>
            </td>
        </tr>
    </template>
    <hr>
    <form method="post"  action="" enctype="multipart/form-data">
        <table class="form-table">
            <tr><td>Bin Sorgula</td><td><input type="text" id="BinNumber" name="BinNumber" value="<?=$BinNumber?>"> <button id="sorgula" name="sorgula" class="button-primary woocommerce-save-button" type="submit" value="1">Sor ve Ekle</button></td></tr>
            <tr><td></td>
                <td>
                    <pre>
                        <?php print_r($ipara_bininfo);?>
                    </pre>
                </td>
            </tr>
        </table>
    </form>
    <script type="text/javascript">
        var bin_template = jQuery('#template-bin').html();
        var i = 1;
        jQuery('body').on('click', '.delete-bin', function(){
            jQuery(this).parents('tr').remove();
        });
        jQuery('body').on('click', '#add-bin', function(){
            jQuery('#bin-table').append(bin_template.replaceAll('{i}', i++));

        });
        jQuery('body').on('click', '#sorgula', function(e){
            e.preventDefault();
            var data = {
                'action': 'ipara_wooturk_action',
                'BinNumber': jQuery('#BinNumber').val()      // We pass php values differently!
            };
            jQuery.post(ajaxurl, data, function(response) {
                if(response.result){
                    var newtitem = bin_template.replaceAll('{i}', i++);
                    newtitem = newtitem.replaceAll('{bincode}', response.BinNumber);
                    newtitem = newtitem.replaceAll('{name}', response.cardFamilyName);
                    newtitem = newtitem.replaceAll('{bank}', response.bankName);
                    //supportsInstallment
                    jQuery('#bin-table').append(newtitem);
                }
            });
            return false;
        });
    </script>

</div>
