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
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

require_once 'api/Payment.php';
require_once 'api/InfoProduct.php';

class TranzzoHandler extends PaySystem\ServiceHandler
{
    CONST MODULE_ID = "tranzzo.payment";

    public function initiatePay(Payment $payment, Request $request = null)
    {
        global $APPLICATION, $USER;
        // TODO: Implement initiatePay() method.

        $config = $this->getSettingsModule();
        $tranzzo = new \Tranzzo\Api\Payment(
            $config['POS_ID'],
            $config['API_KEY'],
            $config['API_SECRET'],
            $config['ENDPOINTS_KEY']
        );

        $order_id = (int)$this->getBusinessValue($payment, 'ORDER_ID');
        $tranzzo->setOrderId($order_id);
        $tranzzo->setAmount($this->getBusinessValue($payment, 'AMOUNT'));
        $tranzzo->setCurrency($this->getBusinessValue($payment, 'CURRENCY'));
        $tranzzo->setDescription("#{$order_id}");

        $tranzzo->setServerUrl("http://{$_SERVER['HTTP_HOST']}/bitrix/tools/sale_ps_result.php");
        $tranzzo->setResultUrl("http://{$_SERVER['HTTP_HOST']}/personal/orders/{$order_id}");


        $order = Sale\Order::load($order_id);

        $USER_ID = (int)$order->getField('USER_ID');
        $tranzzo->setCustomerId($USER_ID);

        if($USER->IsAuthorized()) {
            $tranzzo->setCustomerFirstName($USER->GetFirstName());
            $tranzzo->setCustomerLastName($USER->GetLastName());

            $props = $order->loadPropertyCollection();
            $tranzzo->setCustomerEmail($props->getUserEmail()->getValue());
            $tranzzo->setCustomerPhone($props->getPhone()->getValue());
        }

        $tranzzo->setProducts();
        $obBasket = Sale\Basket::getList(array('filter' => array('ORDER_ID' => $order_id )));
        while($orderItem = $obBasket->Fetch()){
            $infoProduct = new \Tranzzo\Api\InfoProduct;

            $infoProduct->setProductId($orderItem['PRODUCT_ID']);
            $NAME = $APPLICATION->ConvertCharset($orderItem['NAME'], SITE_CHARSET, "utf-8");
            $infoProduct->setProductName($NAME);
            $infoProduct->setCurrency($orderItem['CURRENCY']);
            $infoProduct->setAmount($orderItem['PRICE'] * $orderItem['QUANTITY']);
            $infoProduct->setQuantity($orderItem['QUANTITY']);

            $infoProduct->setProductURL("http://{$_SERVER['HTTP_HOST']}{$orderItem['DETAIL_PAGE_URL']}");

            $tranzzo->addProduct($infoProduct->get());
        }

        $response = $tranzzo->createPaymentHosted();

        if(!empty($response['redirect_url'])) {
            $this->setExtraParams(array(
                'redirect' => $response['redirect_url'],
                'text_btn' => Loc::getMessage('TRANZZO_BTN_PAY')
            ));
        }
        else{
            $this->setExtraParams(array(
                'msg' => Loc::getMessage('TRANZZO_CREATE_PAYMENT_ERROR'),
                'error' => $response['message'] . ' - ' . implode(', ', $response['args'])
            ));
        }

        return $this->showTemplate($payment, 'template');
    }

    public function processRequest(Payment $payment, Request $request)
    {
        // TODO: Implement processRequest() method.

        $data = $request->get('data');
        $signature = $request->get('signature');

        if(empty($data) && empty($signature)) die('LOL! Bad Request!!!');

        $data_response = \Tranzzo\Api\Payment::parseDataResponse($data);
        $order_id = (int)$data_response[\Tranzzo\Api\Payment::P_RES_PROV_ORDER];

        $config = $this->getSettingsModule();
        $tranzzo = new \Tranzzo\Api\Payment(
            $config['POS_ID'],
            $config['API_KEY'],
            $config['API_SECRET'],
            $config['ENDPOINTS_KEY']
        );

        $result = new PaySystem\ServiceResult();
        if($tranzzo -> validateSignature($data, $signature)) {
            $order = Sale\Order::load($order_id);

            $amount_payment = \Tranzzo\Api\Payment::amountToDouble($data_response[\Tranzzo\Api\Payment::P_RES_AMOUNT]);
            $amount_order = \Tranzzo\Api\Payment::amountToDouble($order->getField('PRICE'));

            if ($data_response[\Tranzzo\Api\Payment::P_RES_RESP_CODE] == 1000 && ($amount_payment >= $amount_order)) {
                    $fields = array(
                        "PS_STATUS" => "Y",
                        "PS_STATUS_CODE" => $data_response[\Tranzzo\Api\Payment::P_RES_RESP_CODE],
                        "PS_STATUS_MESSAGE" => $data_response[\Tranzzo\Api\Payment::P_RES_STATUS],
                        "PS_STATUS_DESCRIPTION" => $data_response[\Tranzzo\Api\Payment::P_RES_RESP_DESC],
                        "PS_SUM" => $amount_payment,
                        "PS_CURRENCY" => $data_response[\Tranzzo\Api\Payment::P_RES_CURRENCY],
                        "PAY_VOUCHER_DATE" => new Date(),
                        "PS_RESPONSE_DATE" => new DateTime(),
                    );

//                    $order->setField('STATUS_ID', 'P');
//                    $order->save();

                    $result->setPsData($fields);
                    $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            }
            elseif($data_response[\Tranzzo\Api\Payment::P_RES_STATUS] == 'failed') {
                $fields = array(
                    "PS_STATUS" => "N",
                    "PS_STATUS_CODE" => $data_response[\Tranzzo\Api\Payment::P_RES_RESP_CODE],
                    "PS_STATUS_MESSAGE" => $data_response[\Tranzzo\Api\Payment::P_RES_STATUS],
                    "PS_STATUS_DESCRIPTION" => $data_response[\Tranzzo\Api\Payment::P_RES_RESP_DESC],
                    "PS_SUM" => $amount_payment,
                    "PS_CURRENCY" => $data_response[\Tranzzo\Api\Payment::P_RES_CURRENCY],
                    "PAY_VOUCHER_DATE" => new Date(),
                    "PS_RESPONSE_DATE" => new DateTime(),
                );

//                    $order->setField('STATUS_ID', 'N');
//                    $order->save();

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
        $data_response = \Tranzzo\Api\Payment::parseDataResponse($request->get('data'));
        $signature = $request->get('signature');
        if (!empty($data_response) && !empty($signature))
            return true;
        else
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

        $data_response = \Tranzzo\Api\Payment::parseDataResponse($request->get('data'));
       
        $order_id = (int)$data_response[\Tranzzo\Api\Payment::P_RES_ORDER];
        
        return $order_id;
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