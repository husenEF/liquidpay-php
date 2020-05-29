<?php

require_once __DIR__ . '/vendor/autoload.php';

use Curl\Curl;
use Faker\Factory;
use Dotenv\Dotenv;

class LiquidPay
{

    private $api_key = '';
    private $secret_key = '';
    private $payee = '';
    private $base_url = '';

    private $curl;
    private $faker;
    private $curency = 'IDR';
    private $nonce = '12835819715';

    function __construct($curency = 'IDR')
    {

        Dotenv::createImmutable(__DIR__)->load();
        $this->api_key = $_ENV['API_KEY'];
        $this->secret_key = $_ENV['SECRET_KEY'];
        $this->payee = $_ENV['PAYEE'];
        $this->base_url = $_ENV['BASE_URL'];

        $this->curl = new Curl();
        $this->curl->setHeader('Liquid-Api-Key', $this->api_key);

        $this->faker = Factory::create();
        $this->faker->addProvider(new Faker\Provider\it_IT\Person($this->faker));

        $this->curency = $curency;
    }

    private function _parseArgs(array $args, array $defaults = [])
    {
        return array_replace_recursive($defaults, $args);
    }

    private function _createSign($data)
    {
        $data['nonce'] = $this->WPBJ4iq5XJ8QHDxG;
        $sign = hash('sha512', http_build_query($data));
        return $sign;
    }

    function paymentType()
    {
        $paymentType = $this->curl->get("$this->base_url/v1/bill/qr/payloadtypes", ['payee' => $this->payee]);
        return $paymentType;
    }

    function createBill(array $detail = [])
    {
        $default = [
            'ammount' => 150000,
            'paymentcode' => 'GRABPAYQR'
        ];
        $detail = $this->_parseArgs($detail, $default);

        $this->curl->setHeader('Content-Type', 'application/json');
        $payload = [
            'payee' => $this->payee,
            'bill_ref_no' => (string) time(),
            'service_code' => 31,
            'currency_code' => $this->curency,
            'amount' => $this->generateItems()['total'],
            'payload_code' => $detail['paymentcode'],
            'items' => $this->generateItems()['items'],

        ];
        // $sign = $this->_createSign($payload);
        $payload['nonce'] = '1590709277';
        $payload['sign'] = '30C96A39E5437891250B9E8B06293FC6FA6B05C3B39295AAA42B6B27502F7964DF9F964F4197F3BEB0B728B1CF152CCFE6F36014570A758E7D2E6D08430CF1C4';
        $create = $this->curl->post("$this->base_url/v1/bill/consumer/scan", json_encode($payload));
        $error = [
            $this->curl->error,
            $this->curl->errorCode,
            $this->curl->errorMessage
        ];
        return [$create, json_encode($payload), $error];
    }

    function generateItems()
    {
        $data = [];
        $rand = $this->faker->randomElement([1, 2, 3, 4, 5]);
        $mount = 0;
        for ($n = 0; $n <= $rand; $n++) {
            $qty = $this->faker->randomDigitNot(0);
            $price = $this->faker->randomNumber(3) * 1000;
            $data[$n] = [
                'item_number' => $this->faker->taxId(),
                'item_name' => $this->faker->randomElement(['sepatu', 'topi', 'kemeja', 'tas', 'sepeda']),
                'item_quantity' => $qty,
                'item_unit_price' => $price
            ];
            $mount += $price * $qty;
        }
        return ['items' => $data, 'total' => $mount];
    }
}

$liq = new LiquidPay();

$paymentType = $liq->paymentType();

$createBill = $liq->createBill();


r($paymentType, $createBill);
