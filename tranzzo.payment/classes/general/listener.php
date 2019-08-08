<?php
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use \Tranzzo\Api as ApiTranzzo;
use \Tranzzo\Api\Payment as PayTranzzo;

class ListenerTranzzoPayment
{
    CONST MODULE_ID = "tranzzo.payment";
    CONST MODULE_NAME = "TRANZZO";

    public static function orderChanged($event) //event OnSaleOrderSaved
    {

//        $result['get_class_methods'] = get_class_methods($event);
//        $result['getParameters'] = $event->getParameters();
//        $result['get_object_vars'] = get_object_vars($event);
//
//        file_put_contents(__DIR__ . "/orderChanged.log", "\n" . date('Y-m-d H:i:s') . "\n" .
//            json_encode($result, JSON_PRETTY_PRINT), FILE_APPEND);
    }

    public static function orderPaid($event) //event OnSaleOrderPaid \Bitrix\Sale\Payment
    {
        $order = $event->getParameter("ENTITY");

        if($order instanceof Bitrix\Sale\Order) {

//        $data = get_object_vars($event);
//        $data = get_class_methods($event);

//        $data['event'] = get_class_methods($event);
//        $data['get_class_methods'] = get_class_methods($payment);
//        $data['get_object_vars'] = get_object_vars($payment);
//        $data['getAllFields'] = $payment->getAllFields();
//        $data['loadPaymentCollection'] = $payment->loadPaymentCollection();
//        $data['getPaymentCollection'] = $payment->getPaymentCollection();

//        $pay = \Bitrix\Sale\Payment::getList(array('select' => array('ORDER_ID' => (int)$payment->getId())));
//        $data['pay'] = $pay;

            $order_id = (int)$order->getId();


            $paymentId = intval($_POST['paymentId']);
            $payments = $order->getPaymentCollection();
            $payment = self::getInvoiceFromId($paymentId, $payments);

//            $payRow = \Bitrix\Sale\Payment::getList(array('filter' => array('ORDER_ID' => $order_id)));
            $payRow = \Bitrix\Sale\Payment::getList(array('filter' => array('ID' => $paymentId)));
            $dataPayOrder = $payRow->fetch();

//            PayTranzzo::writeLog([
//                'getAllFields' => $payment->getAllFields(),
//                'get_object_vars' => get_object_vars($payment),
//                'get_class_methods' => get_class_methods($payment),
//            ], '', 'log_OnSaleOrderPaid');


//            $PAID = $payment->getField('PAID');
//            $PAY_SYSTEM_NAME = $payment->getField('PAY_SYSTEM_NAME');
//            $PS_INVOICE_ID = $payment->getField('PS_INVOICE_ID');
//            $IS_RETURN = $payment->getField('IS_RETURN');

            $PAID = $dataPayOrder['PAID'];
            $PAY_SYSTEM_NAME = $dataPayOrder['PAY_SYSTEM_NAME'];
            $PS_INVOICE_ID = $dataPayOrder['PS_INVOICE_ID'];
            $IS_RETURN = $dataPayOrder['IS_RETURN'];

//            PayTranzzo::writeLog([
//                '$PAID' => $PAID,
//                '$PAY_SYSTEM_NAME' => $PAY_SYSTEM_NAME,
//                '$PS_INVOICE_ID' => $PS_INVOICE_ID,
//                '$IS_RETURN' => $IS_RETURN,
//            ], '', 'log_paid', 0);
//            if (0) {

            if ($PAY_SYSTEM_NAME == self::MODULE_NAME && $PAID == 'N' && $IS_RETURN != 'Y' && !empty($PS_INVOICE_ID)) {

                $tranzzo = self::getApiTranzzo();

                $tranzzo->setOrderId($PS_INVOICE_ID);
                $tranzzo->setOrderCurrency($payment->getField('PS_CURRENCY'));
                $tranzzo->setOrderAmount($payment->getField('PS_SUM'));

//                $scheme = \Cmain::isHTTPS()? 'https' : 'http';
//                $tranzzo->setServerUrl("{$scheme}://{$_SERVER['HTTP_HOST']}/bitrix/tools/sale_ps_result.php?refund");

                $result = $tranzzo->createRefund();

                $response = new ApiTranzzo\ResponseParams($result);

                $date = new Date();
                $PAY_RETURN_COMMENT = "Date refund - " . $date->toString() . "\n"
                    . "Order id of TRANZZO - " . $response->getOrderId() . "\n"
                    . "Status - " . $response->getStatus() . "\n"
                    . "Payment id - " . $response->getPaymentId();

                if($response->getStatus() == PayTranzzo::STATUS_SUCCESS) {
                    $payment->setField('IS_RETURN', 'Y');
                }
                $payment->setField('PAY_RETURN_DATE', new Date());
                $payment->setField('PAY_RETURN_COMMENT', $PAY_RETURN_COMMENT);
                $payment->setField('PS_STATUS_CODE', $response->getStatusCode());
                $payment->save();

                $order = Sale\Order::load($payment->getField('ORDER_ID'));
                $order->setField('STATUS_ID', 'CL');
                $order->setField('CANCELED', 'Y');
                $order->save();

                PayTranzzo::writeLog(['response' => $response->getData()], '', 'after_refund');

            }
        }
    }

    public static function OnAdminContextMenuShowHandler(&$items)
    {
        $itemsContext = [];
        if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/sale_order_view.php' && $_REQUEST['ID'] > 0)
        {
            $orderId = intval($_REQUEST["ID"]);
            $orderInfo = Sale\Order::load($orderId);

            if($orderInfo instanceof Sale\Order) {
                //var_dump($orderInfo->getField('PAY_SYSTEM'));
//                var_dump($orderInfo->getAllFields());

//                var_dump(self::getSettingsModule('ENDPOINTS_KEY'));

                //var_dump($paySysId = $orderInfo->getPaymentSystemId());
                $payments = $orderInfo->getPaymentCollection();

                if(isset($_GET["tranzzo_action"])) {
                    $tranzzo = self::getApiTranzzo();
                    $paymentId = intval($_GET['tranzzo_payment']);
                    $payment = self::getInvoiceFromId($paymentId, $payments);

                    $tranzzo->setOrderId($payment->getField('PS_INVOICE_ID'));
                    $tranzzo->setOrderCurrency($payment->getField('PS_CURRENCY'));
                    $tranzzo->setOrderAmount($payment->getField('PS_SUM'));

                    if ($_GET["tranzzo_action"] == 'void') {

                        $result = $tranzzo->createVoid();

                        ApiTranzzo\Payment::writeLog($result, '', 'after_void.log');

                        $response = new ApiTranzzo\ResponseParams($result);
                        if ($response->getStatus() != PayTranzzo::STATUS_SUCCESS) {
                            $message = 'TRANZZO voided failed!!! ' . $response->getMessage();

                            $msg['MESSAGE'] = Loc::getMessage('TRANZZO.msgError');
                            $msg['MODULE_ID'] = self::MODULE_ID;
                            $msg['NOTIFY_TYPE'] = 'E';
                            \CAdminNotify::Add($message);

                        } else {

                            $payment->setField('PAY_VOUCHER_DATE', new Date());
                            $payment->setField('PS_STATUS_CODE', $response->getStatusCode());
                            $payment->save();

                            $orderInfo->setField('STATUS_ID', 'CL');
                            $orderInfo->setField('CANCELED', 'Y');
                            $orderInfo->save();

                            $msg['MESSAGE'] = Loc::getMessage('TRANZZO.msgVoid');
                            $msg['MODULE_ID'] = self::MODULE_ID;
                            $msg['NOTIFY_TYPE'] = 'M';
                            \CAdminNotify::Add($msg);
                        }


                        unset($_GET['tranzzo_action'], $_GET['tranzzo_payment']);
                        header('Location: ' . $GLOBALS['APPLICATION']->GetCurPageParam());
                        exit;
                    } elseif ($_GET["tranzzo_action"] == 'capture') {
//                        $tranzzo->setServerUrl();

                        $result = $tranzzo->createCapture();

                        PayTranzzo::writeLog($result, '', 'after_capture.log');

                        $response = new ApiTranzzo\ResponseParams($result);
                        if ($response->getStatus() != PayTranzzo::STATUS_SUCCESS) {
                            $message = 'TRANZZO capture failed!!! ' . $response->getMessage() . ' Status - ' . $response->getStatus();

                            $msg['MESSAGE'] = Loc::getMessage('TRANZZO.msgError');
                            $msg['MODULE_ID'] = self::MODULE_ID;
                            $msg['NOTIFY_TYPE'] = 'E';
                            \CAdminNotify::Add($message);

                        } else {

                            $payment->setField('PAID', 'Y');
                            $payment->setField('PAY_VOUCHER_DATE', new Date());
                            $payment->setField('PS_STATUS_CODE', $response->getStatusCode());
                            $payment->save();

                            $orderInfo->setField('STATUS_ID', 'P');
                            $orderInfo->save();

                            $msg['MESSAGE'] = Loc::getMessage('TRANZZO.msgCapture');
                            $msg['MODULE_ID'] = self::MODULE_ID;
                            $msg['NOTIFY_TYPE'] = 'M';
                            \CAdminNotify::Add($msg);
                        }

                        unset($_GET['tranzzo_action'], $_GET['tranzzo_payment']);
                        header('Location: ' . $GLOBALS['APPLICATION']->GetCurPageParam());
                        exit;
                    }
                }

//                var_dump($payments);
                foreach ($payments as $payment) {
                    $paymentName = $payment->getField('PAY_SYSTEM_NAME');
                    if($paymentName == self::MODULE_NAME) {
                        $PAID = $payment->getField('PAID');
                        $PS_STATUS = $payment->getField('PS_STATUS');
                        $PAY_VOUCHER_DATE = $payment->getField('PAY_VOUCHER_DATE');
                        $PS_INVOICE_ID = $payment->getField('PS_INVOICE_ID');
                        $IS_RETURN = $payment->getField('IS_RETURN');
                        if($IS_RETURN == 'N' && $PAID == 'N' && $PS_STATUS == 'Y' && empty($PAY_VOUCHER_DATE) && !empty($PS_INVOICE_ID)) {
                            $itemsContext = array_merge($itemsContext, self::addCustomContextButton($payment->getId()));
                        }
                    }
                }


//            var_dump(Sale\OrderStatus::getAllStatuses());
//            var_dump(Sale\OrderStatus::getAllStatusesNames());

//            var_dump($GLOBALS['APPLICATION']->GetCurPageParam());
//            var_dump($GLOBALS['APPLICATION']->GetCurPageParam());

//            $post = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();



//                \CAdminMessage::ShowMessage([
//                    "MESSAGE" => Loc::getMessage('TRANZZO Error'),
//                    "TYPE" => "OK"
//                ]);
//                \CAdminMessage::ShowMessage([
//                        "MESSAGE" => 'TRANZZO Error',
//                        "TYPE" => "ERROR"
//                ]);

//                Main\Loader::includeModule(self::MODULE_ID);

                if(!empty($itemsContext)){
                    $items = self::addCustomMenuButton($items, $itemsContext);
                }
            }
        }
    }

    public static function addCustomContextButton($id = 0)
    {
        $newItem = [
            [
            'ICON' => 'tranzzo_payment_void',
            'TEXT' => Loc::getMessage('TRANZZO.btnVoid') . intval($id),
            'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('tranzzo_action=void&tranzzo_payment=' . intval($id)),
            ],
            [
            'ICON' => 'tranzzo_payment_capture',
            'TEXT' => Loc::getMessage('TRANZZO.btnCapture') . intval($id),
            'LINK' => $GLOBALS['APPLICATION']->GetCurPageParam('tranzzo_action=capture&tranzzo_payment=' . intval($id)),
            ]
        ];

        return $newItem;
    }

    public static function addCustomMenuButton($items, $context = [])
    {
        $newItems = [];

        foreach ($items as $key => $item) {
            if ($key == 1) {
                $newItem = [
                    'TEXT' => 'TRANZZO',
                    'TITLE' => 'TRANZZO',
                    'LINK' => '#',
                    'ICON' => 'btn_settings',
                    'MENU' => $context,
                ];

                $newItems[] = $newItem;
            }
            $newItems[] = $item;
        }

        return $newItems;
    }

    public static function getInvoiceFromId($id, $collection = [])
    {
        foreach ($collection as $item) {
            if($item instanceof Sale\Payment){
                if($item->getId() == $id)
                    return $item;
            }
        }

        return false;
    }

    public static function getSettingsModule($key = null)
    {
        if(is_null($key))
            $config = \Bitrix\Main\Config\Option::getForModule(self::MODULE_ID);
        else
            $config = \Bitrix\Main\Config\Option::get(self::MODULE_ID, $key);

        return $config;
    }

    public static function getApiTranzzo()
    {
        $config = self::getSettingsModule();
        return new ApiTranzzo\Payment(
            $config['POS_ID'],
            $config['API_KEY'],
            $config['API_SECRET'],
            $config['ENDPOINTS_KEY']
        );
    }
}