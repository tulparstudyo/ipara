<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class KahveDigitalIpara
{

    const max_installment = 12;

    public static function getAvailablePrograms()
    {
        return get_option("ipara_defined_bins");
    }

    public static function setRatesFromPost($posted_data)
    {
        $banks = KahveDigitalIpara::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array();
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = isset($posted_data[$k]['installments'][$i]['value']) ? ((float) $posted_data[$k]['installments'][$i]['value']) : 0.0;
                $return[$k]['installments'][$i]['active'] = isset($posted_data[$k]['installments'][$i]['active']) ? ((int) $posted_data[$k]['installments'][$i]['active']) : 0;
            }
            $return[$k]['discount'] = isset($posted_data[$k]['discount']) ? ((int) $posted_data[$k]['discount']) : 0;
        }
        // ahmethamdi
        return $return;
    }

    public static function setRatesDefault()
    {
        $banks = KahveDigitalIpara::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array('active' => 0);
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = (float) (1 + $i + ($i / 5) + 0.1);
                $return[$k]['installments'][$i]['active'] = $v['installments'];
                if ($i == 1) {
                    $return[$k]['installments'][$i]['value'] = 0.00;
                    $return[$k]['installments'][$i]['active'] = 1;
                }
            }
        }
        return $return;
    }

    public static function register_all_ins()
    {
        if (isset($_POST['ipara_rates'])) {
            update_option('ipara_rates', KahveDigitalIpara::setRatesFromPost($_POST['ipara_rates']));
        }

    }

    public static function createRatesUpdateForm($rates)
    {
        // ahmethamdi
        $ipara_url = plugins_url('/assets//', dirname(__FILE__));
        $return = '<table class="kahvedigital_ipara_table table">'
            . '<thead>'
            . '<tr><th>Banka</th>';
        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<th>' . $i . ' taksit</th>';
        }
        $return .= '<td>İndirim</td></tr></thead><tbody>';

        $banks = KahveDigitalIpara::getAvailablePrograms();
        foreach ($banks as $k => $v) {
            if(empty($v['installments'])) continue;
            if(!isset($v['logo'])){
                $v['logo'] = '';
            }
            $return .= '<tr>'
                . '<th text-align="left"><img src="'.$v['logo'].'" width="40px" alt="'.$v['name'].'" title="'.$v['name'].'"></th>';
            for ($i = 1; $i <= self::max_installment; $i++) {
                if(!isset($rates[$k]) || !isset($rates[$k]['installments'][$i])){
                    $rates[$k]['installments'][$i]['active'] = 0;
                    $rates[$k]['installments'][$i]['value'] = 0;
                    $rates[$k]['discount'] = 0;
                }
                $return .= '<td>'
                    . ' <input type="checkbox"  name="ipara_rates[' . $k . '][installments][' . $i . '][active]" '
                    . ' value="1" ' . ((int) $rates[$k]['installments'][$i]['active'] == 1 ? 'checked="checked"' : '') . '/>'
                    . '<input type="number" step="0.01" maxlength="4" size="4" style="width:60px" '
                    . ((int) $rates[$k]['installments'][$i]['active'] == 0 ? 'disabled="disabled"' : '')
                    . ' value="' . ((float) $rates[$k]['installments'][$i]['value']) . '"'
                    . ' name="ipara_rates[' . $k . '][installments][' . $i . '][value]"/></td>';
            }
            $return .= '<td style="text-align: right"><input type="number" step="0.01" 
maxlength="4" size="4" style="width:60px" value="' . ((float) $rates[$k]['discount']) . '" name="ipara_rates[' . $k . '][discount]"></td>';
            $return .= '</tr>';

        }
        $return .= '</tbody></table>';
        return $return;
    }

    public static function calculatePrices($price, $rates)
    {
        // ahmethamdi
        $banks = KahveDigitalIpara::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            if(!isset($rates[$k])) continue;
            $return[$k]['base_price'] =  $price;
            $new_price = $price - ( ($price * $rates[$k]['discount']) / 100 );
            $return[$k]['discount'] = $return[$k]['base_price'] - $new_price;
            $return[$k]['discounted'] = $new_price;
            if($return[$k]['discount']>0){
                $return[$k]['message'] = $v['name']." Kullanalara Özel ".wc_price($return[$k]['discount'])." İndirim";
            } else{
                $return[$k]['message'] = '';
            }
            if ($v['installments'] == false) {
                continue;
            }
            for ($i = 1; $i <= self::max_installment; $i++) {
                if(!isset($rates[$k]['installments'][$i])) continue;
                $return[$k]['installments'][$i] = array(
                    'active' => $rates[$k]['installments'][$i]['active'],
                    'total' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $new_price) / 100), 2, '.', ''),
                    'monthly' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $new_price) / 100) / $i, 2, '.', ''),
                );
            }
        }
        return $return;
    }

    public function getRotatedRates($price, $rates)
    {
        $prices = KahveDigitalIpara::calculatePrices($price, $rates);
        for ($i = 1; $i <= self::max_installment; $i++) {

        }
    }

    public static function createInstallmentsForm($price, $rates)
    {
        $ipara_url = plugins_url('/', dirname(__FILE__));

        $prices = KahveDigitalIpara::calculatePrices($price, $rates);
        $return = '<table class="kahvedigital_ipara_table table installments">'
            . '<thead>'
            . '<tr><th>Banka</th>';
        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<th>' . $i . ' taksit</th>';
        }
        $return .= '</tr></thead><tbody>';

        $banks = KahveDigitalIpara::getAvailablePrograms();
        foreach ($banks as $k => $v) {
            $return .= '<tr>'
                . '<th><img src="' . $v['logo'] . '.svg"></th>';
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return .= '<td><input type="number" step="0.001" maxlength="4" size="4" '
                    . ' value="' . ((float) $rates[$k]['installments'][$i]) . '"'
                    . ' name="ipara_rates[' . $k . '][installments][' . $i . ']"/></td>';
            }
            $return .= '</tr>';
        }
        $return .= '</tbody></table>';
        return $return;
    }

    // public static function getProductInstallments($price, $rates)
    // {
    //     $prices = KahveDigitalIpara::calculatePrices($price, $rates);
    //     $banks = KahveDigitalIpara::getAvailablePrograms();
    //     $return = '<style>

    //        .ipara-rates-table {      border-spacing: 0;      border-collapse: collapse;      width: 100%;   }
    //        .ipara-rates-table * {      font-family: "Helvetica Neue",Helvetica,Arial,sans-serif!important;   }
    //        .ipara-rates-table-with-bg img{      width: 64px;   }
    //        .ipara-rates-table-with-bg {      color: #111;      background: #f8f8f8;      border: 1px solid #e3e3e3;   }
    //        .ipara-rates-table td.axess{    background-color:#e2b631;    color:#fff;   }
    //        .ipara-rates-table td.maximum{       background-color:#f52295;       color:#fff;   }
    //        .ipara-rates-table td.cardfinans{       background-color: #2d5fc2;       color:#fff;   }
    //        .ipara-rates-table td.world{       background-color: #6f6b99;       color:#fff;   }
    //        .ipara-rates-table td.bonus{       background-color: #479279;       color:#fff;   }
    //        .ipara-rates-table td{      padding: 5px 10px;      text-align: center;   }
    //        .ipara-amount {      font-size: 13px;      font-weight: 700;      line-height: 20px;   }
    //        .ipara-rates-table td span {      display: inline-block;      width: 100%;      text-align: center;   }
    //        .ipara-rates-table
    //        .ipara-total-amount {      font-size: 11px;      font-weight: 400;      line-height: 20px;   }
    //        .ipara-rates-table td {      border: 1px dashed #d6d6d6;   }
    //        .ipara-rates-table-with-bg {      font-size: 13px!important;   }
    //             </style>

    //             <table  style="width: 100%;" class="ipara-rates-table"> <tbody>   <tr style="height:50px;">  <th>Taksit</th>    ';
    //     foreach ($banks as $k => $v) {
    //         $return .= '    <th class="ipara-rates-table-with-bg">
    //                <img src="catalog/view/theme/default/image/ipara_payment/' . $k . '.svg">     </th> ';
    //     }
    //     $return .= '</tr>
    //          <tr>
    //          </tr><tr> ';

    //     for ($ins = 1; $ins < self::max_installment; $ins++) {
    //         if ($ins == 1) {
    //             $return .= '<td class="ipara-rates-table-with-bg" style="height:50px;"> Peşin </td> ';
    //         } else {

    //             $return .= '<td class="ipara-rates-table-with-bg" style="height:50px;"> ' . $ins . ' Taksit </td> ';
    //         }
    //         foreach ($banks as $k => $v) {

    //             if ($ins == 1) {
    //                 $return .= ' <td class="' . $k . '">  <span class="ipara-amount"> ' . $prices[$k]['installments'][$ins]['total'] . '  TL</span> </td>   ';
    //             } else {

    //                 $return .= ' <td class="' . $k . '">  <span class="ipara-amount"> ' . $prices[$k]['installments'][$ins]['monthly'] . ' x ' . $ins . ' </span><span class="ipara-total-amount"> TOPLAM ' . $prices{$k}['installments']{ $ins}['total'] . ' TL </span> </td>   ';
    //             }
    //         }

    //         $return .= '</tr>';
    //     }
    //     $return .= '<tbody></table>';

    //     return $return;
    // }
}
