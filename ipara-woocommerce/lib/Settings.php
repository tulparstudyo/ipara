<?php
if (!defined('ABSPATH')) {
    exit;
}
class Ipara_Settings
{

    public function __construct()
    {

        $this->PublicKey = "";
        $this->PrivateKey = "";
        $this->BaseUrl = "https://api.ipara.com/";
        $this->Version = "1.0";
        $this->Mode = "";
        $this->HashString = md5(time() . mt_rand(1, 1000000));
    }

    public $PublicKey;
    public $PrivateKey;
    public $BaseUrl;
    public $Mode;
    public $Version;
    public $HashString;
    public $transactionDate;

}
