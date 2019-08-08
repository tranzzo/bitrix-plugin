<?php
namespace Sale\Handlers\PaySystem;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem\Service;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale;
use \Tranzzo\Api\Payment as PayTranzzo;

Loc::loadMessages(__FILE__);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/tranzzo.payment/lib/api/Payment.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/tranzzo.payment/lib/api/InfoProduct.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/tranzzo.payment/lib/api/ResponseParams.php';

class TranzzoHandler extends PaySystem\ServiceHandler
{
    CONST MODULE_ID = "tranzzo.payment";

    public function initiatePay(Payment $payment, Request $request = null)
    {
        global $APPLICATION, $USER;
        // TODO: Implement initiatePay() method.

        if($payment->getField(PS_STATUS)=='Y')
            return $this->showTemplate($payment, 'template');

        $config = $this->getSettingsModule();
        $tranzzo = new PayTranzzo(
            $config['POS_ID'],
            $config['API_KEY'],
            $config['API_SECRET'],
            $config['ENDPOINTS_KEY']
        );

        $order_params = $this->getParamsBusValue($payment);

        $order_id = (int)$order_params['ORDER_ID'];
        $payment_id = (int)$order_params['PAYMENT_ID'];

        PayTranzzo::writeLog((array)$order_id, '$order_id');
        PayTranzzo::writeLog((array)$payment_id, '$payment_id');

        $tranzzo->setOrderId($payment_id);
        $tranzzo->setAmount($this->getBusinessValue($payment, 'AMOUNT'));
        $tranzzo->setCurrency($this->getBusinessValue($payment, 'CURRENCY'));
        $tranzzo->setDescription("#{$order_id}");

        $scheme = \CMain::IsHTTPS()? 'https' : 'http';
        $tranzzo->setServerUrl("{$scheme}://{$_SERVER['HTTP_HOST']}/bitrix/tools/sale_ps_result.php");
        $tranzzo->setResultUrl("{$scheme}://{$_SERVER['HTTP_HOST']}/personal/orders/{$order_id}");

        $order = Sale\Order::load($order_id);

        $USER_ID = $order->getField('USER_ID')? (int)$order->getField('USER_ID') : (int)$order_params['USER_ID'];
        $tranzzo->setCustomerId($USER_ID);

        if($USER->IsAuthorized()) {
            $tranzzo->setCustomerFirstName($USER->GetFirstName());
            $tranzzo->setCustomerLastName($USER->GetLastName());

            $props = $order->loadPropertyCollection();
            $tranzzo->setCustomerEmail($props->getUserEmail()->getValue());
            $tranzzo->setCustomerPhone($props->getPhone()->getValue());
        }
        else{
            $tranzzo->setCustomerFirstName($order_params['USER_FNAME']);
            $tranzzo->setCustomerLastName($order_params['USER_LNAME']);

            $props = $order->loadPropertyCollection();
            $tranzzo->setCustomerEmail($props->getUserEmail()->getValue());
            $tranzzo->setCustomerPhone($props->getPhone()->getValue());
        }

        $tranzzo->setProducts();
        $obBasket = Sale\Basket::getList(array('filter' => array('ORDER_ID' => $order_id )));
        while($orderItem = $obBasket->Fetch()){
            $infoProduct = new \Tranzzo\Api\InfoProduct();

            $infoProduct->setProductId($orderItem['PRODUCT_ID']);
            $NAME = $APPLICATION->ConvertCharset($orderItem['NAME'], SITE_CHARSET, "utf-8");
            $infoProduct->setProductName($NAME);
            $infoProduct->setCurrency($orderItem['CURRENCY']);
            $infoProduct->setAmount($orderItem['PRICE'] * $orderItem['QUANTITY']);
            $infoProduct->setQuantity($orderItem['QUANTITY']);

            $infoProduct->setProductURL("{$scheme}://{$_SERVER['HTTP_HOST']}{$orderItem['DETAIL_PAGE_URL']}");

            $tranzzo->addProduct($infoProduct->get());
        }

        if($config['PAYMENT_ACTION'] == PayTranzzo::P_METHOD_AUTH) {
            $response = $tranzzo->createPaymentAuth();
        } else {
            $response = $tranzzo->createPaymentPurchase();
        }

        if(!empty($response['redirect_url'])) {
            $this->setExtraParams([
                'redirect' => $response['redirect_url'],
                'text_btn' => Loc::getMessage('TRANZZO_BTN_PAY')
            ]);
        }
        else{
            $this->setExtraParams([
                'msg' => Loc::getMessage('TRANZZO_CREATE_PAYMENT_ERROR'),
                'error' => $response['message'] . ' - ' . implode(', ', $response['args'])
            ]);
        }

        return $this->showTemplate($payment, 'template');
    }

    public function processRequest(Payment $payment, Request $request)
    {
        PayTranzzo::writeLog('', 'processRequest', 'callback');

        // TODO: Implement processRequest() method.

        $data = $request->get('data');
        $signature = $request->get('signature');

        if(empty($data) && empty($signature)) die('LOL! Bad Request!!!');

        $response = PayTranzzo::parseDataResponse($data, 1);

        PayTranzzo::writeLog((array)$response, 'data', 'callback');

        $config = $this->getSettingsModule();
        $tranzzo = new PayTranzzo(
            $config['POS_ID'],
            $config['API_KEY'],
            $config['API_SECRET'],
            $config['ENDPOINTS_KEY']
        );

        $result = new PaySystem\ServiceResult();
        if($tranzzo -> validateSignature($data, $signature) && $payment->getField('PAY_SYSTEM_NAME') == 'TRANZZO') {

            if(isset($_GET['capture'])){

                $result->addError(new Error(''));

                return $result;
            }

            if(isset($_GET['refund'])){
                if($response->getStatus() == PayTranzzo::STATUS_SUCCESS) {
                    $fields = [
                    'PAY_RETURN_COMMENT' => "2Date refund - " . (new Date())->toString() . "\n"
                        . "Order id of TRANZZO - " . $response->getOrderId() . "\n"
                        . "Status - " . $response->getStatus() . "\n"
                        . "Payment id - " . $response->getPaymentId(),
                        'PAY_RETURN_DATE' => new Date(),
                        'IS_RETURN' => 'Y',
                    ];

                    $result->setPsData($fields);
                    $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
                } else {
                    $result->addError(new Error(''));
                }

                return $result;
            }

            if($response->getStatus() == PayTranzzo::STATUS_INIT
                || $response->getStatus() == PayTranzzo::STATUS_PROCESSING
                || $response->getStatus() == PayTranzzo::STATUS_PENDING){

                $result->addError(new Error(''));
                return $result;
            }

            $amount_payment = PayTranzzo::amountToDouble($response->getAmount());
            $amount_order = PayTranzzo::amountToDouble($payment->getField('SUM'));

            PayTranzzo::writeLog($response->getStatusCode(), 'getStatusCode', 'callback');
            PayTranzzo::writeLog((array)$amount_payment, '$amount_payment', 'callback');
            PayTranzzo::writeLog((array)$amount_order, '$amount_order', 'callback');

//            if ($response->getResponseCode() == 1000 && ($amount_payment >= $amount_order)) {
            if ($response->getStatusCode() == 1000 && ($amount_payment >= $amount_order)) {

                PayTranzzo::writeLog('status success', '', 'valid');

                $fields = array(
                    "PS_STATUS" => "Y",
//                    "PS_STATUS_CODE" => $response->getResponseCode(),
                    "PS_STATUS_CODE" => $response->getStatusCode(),
                    "PS_STATUS_MESSAGE" => $response->getStatus(),
                    "PS_STATUS_DESCRIPTION" => $response->getStatusDescription(),
                    "PS_SUM" => $amount_payment,
                    "PS_CURRENCY" => $response->getCurrency(),
//                    "PS_INVOICE_ID" => $response->getOrderId(),
                    "PS_INVOICE_ID" => $response->getBillOrderId(),
                    "PAY_VOUCHER_DATE" => new Date(),
                    "PS_RESPONSE_DATE" => new DateTime(),
                    'IS_RETURN' => 'N',
                );

                $order = Sale\Order::load($payment->getField('ORDER_ID'));
                $order->setField('STATUS_ID', 'P');
                $order->save();

                PayTranzzo::writeLog($fields, 'fields', 'callback');
                $result->setPsData($fields);
                $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            }
//            elseif ($response->getResponseCode() == 1002 && ($amount_payment >= $amount_order)) {
            elseif ($response->getStatusCode() == 1002 && ($amount_payment >= $amount_order)) {

                //__('The authorized amount is %1.', $formatedPrice)
                PayTranzzo::writeLog('status auth success', '', 'valid');

                $fields = array(
                    "PS_STATUS" => "Y",
//                    "PS_STATUS_CODE" => $response->getResponseCode(),
                    "PS_STATUS_CODE" => $response->getStatusCode(),
                    "PS_STATUS_MESSAGE" => $response->getStatus(),
                    //"PS_STATUS_DESCRIPTION" => $response->getStatusDescription(),
                    "PS_STATUS_DESCRIPTION" => $response->getResponseDescription(),
                    "PS_SUM" => $amount_payment,
                    "PS_CURRENCY" => $response->getCurrency(),
//                    "PS_INVOICE_ID" => $response->getOrderId(),
                    "PS_INVOICE_ID" => $response->getBillOrderId(),
                    "PAY_VOUCHER_DATE" => NULL,
                    "PS_RESPONSE_DATE" => new DateTime(),
                    'IS_RETURN' => 'N',
                );

                $order = Sale\Order::load($payment->getField('ORDER_ID'));
                $order->setField('STATUS_ID', 'HL');
                $order->save();
                PayTranzzo::writeLog($fields, 'fields', 'callback');

                $result->setPsData($fields);
                $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
            }
            elseif($response->getStatus() == 'failed') {

                PayTranzzo::writeLog('status fail', '', 'valid');

                $fields = array(
                    "PS_STATUS" => "N",
//                    "PS_STATUS_CODE" => $response->getResponseCode(),
                    "PS_STATUS_CODE" => $response->getStatusCode(),
                    "PS_STATUS_MESSAGE" => $response->getStatus(),
                    "PS_STATUS_DESCRIPTION" => $response->getResponseDescription(),
                    "PS_SUM" => $amount_payment,
                    "PS_CURRENCY" => $response->getCurrency(),
                    "PAY_VOUCHER_DATE" => new Date(),
                    "PS_RESPONSE_DATE" => new DateTime(),
                    'IS_RETURN' => 'N',
                );

                $result->setPsData($fields);
                $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
            }
        } else {
            $result->addError(new Error(Loc::getMessage('TRANZZO_SIGNATURE_NOT_VERIFIED')));
        }

        return $result;
    }

    public static function isMyResponse(Request $request, $paySystemId)
    {
        $response = PayTranzzo::parseDataResponse($request->get('data'));
        $signature = $request->get('signature');
        if (!empty($response) && !empty($signature)) {
            return true;
        } else
            return false;
    }

    public function getCurrencyList()
    {
        // TODO: Implement getCurrencyList() method.
        return array('USD', 'EUR', 'UAH', 'RUB');
    }

    public function getPaymentIdFromRequest(Request $request)
    {
        // TODO: Implement getPaymentIdFromRequest() method.
        PayTranzzo::writeLog('', 'getPaymentIdFromRequest', 'callback');

        $data_response = PayTranzzo::parseDataResponse($request->get('data'), 1);

//        $payment_id = (int)$data_response->getProvOrderId();
        $payment_id = (int)$data_response->getOrderId();
        PayTranzzo::writeLog($payment_id, '$payment_id', 'callback');

        return $payment_id;
    }

    public function getSettingsModule()
    {
        $config = \Bitrix\Main\Config\Option::getForModule(self::MODULE_ID);

        if(is_array($config)){
            foreach ($config as $item => $value) {
                $this->{$item} = trim($value);
            }
        }

        return $config;
    }
}