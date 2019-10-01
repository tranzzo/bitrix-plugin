<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

use \Bitrix\Main\Localization\Loc;
use \Tranzzo\Api;

Loc::loadMessages(__FILE__);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/tranzzo.payment/lib/api/Payment.php';

$data = array(
    'NAME' => 'TRANZZO',
    'SORT' => 100,
    'CODES' => array(
        //new
        "POS_ID" => array(
            "SORT" => 101,
            "NAME" => Loc::getMessage("TRANZZO.POS_ID"),
            "DESCR" => "",
            "VALUE" => "",
            "TYPE" => ""
        ),
        "API_KEY" => array(
            "SORT" => 102,
            "NAME" => Loc::getMessage("TRANZZO.API_KEY"),
            "DESCR" => "",
            "VALUE" => "",
            "TYPE" => ""
        ),
        "API_SECRET" => array(
            "SORT" => 103,
            "NAME" => Loc::getMessage("TRANZZO.API_SECRET"),
            "DESCR" => "",
            "VALUE" => "",
            "TYPE" => ""
        ),
        "ENDPOINTS_KEY" => array(
            "SORT" => 104,
            "NAME" => Loc::getMessage("TRANZZO.ENDPOINTS_KEY"),
            "DESCR" => "",
            "VALUE" => "",
            "TYPE" => ""
        ),
        "PAYMENT_ACTION" => array(
            "NAME" => Loc::getMessage("TRANZZO.PAYMENT_ACTION"),
            "SORT" => 105,
            "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                    Api\Payment::P_METHOD_PURCHASE => Loc::getMessage("TRANZZO.ACTION_PURCHASE"),
                    Api\Payment::P_METHOD_AUTH => Loc::getMessage("TRANZZO.ACTION_AUTH")
                )
            ),
        ),
        "PAYMENT_USER_INFO" => array(
            "NAME" => Loc::getMessage("TRANZZO.PAYMENT_USER_INFO"),
            "SORT" => 105,
            "INPUT" => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => array(
                    Api\Payment::P_USER_INFO_ON => Loc::getMessage("TRANZZO.USER_INFO_ON"),
                    Api\Payment::P_USER_INFO_OFF => Loc::getMessage("TRANZZO.USER_INFO_OFF")
                )
            ),
        ),
        //new
        'ORDER_ID' => array(
            'SORT' => 110,
            'NAME' => Loc::getMessage('TRANZZO_ORDER_ID'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'ORDER',
                'PROVIDER_VALUE' => 'ID'
            )
        ),
        'PAYMENT_ID' => array(
            'SORT' => 110,
            'NAME' => Loc::getMessage('TRANZZO_PAYMENT_ID'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'ID'
            )
        ),
        'AMOUNT' => array(
            'SORT' => 111,
            'NAME' => Loc::getMessage('TRANZZO_AMOUNT'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'SUM'
            )
        ),
        'CURRENCY' => array(
            'SORT' => 112,
            'NAME' => Loc::getMessage('TRANZZO_CURRENCY'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'PAYMENT',
                'PROVIDER_VALUE' => 'CURRENCY',
            )
        ),
        'USER_ID' => array(
            'SORT' => 211,
            'NAME' => Loc::getMessage('TRANZZO_USER_ID'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'USER',
                'PROVIDER_VALUE' => 'ID',
            )
        ),
        'USER_FNAME' => array(
            'SORT' => 212,
            'NAME' => Loc::getMessage('TRANZZO_USER_FNAME'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'USER',
                'PROVIDER_VALUE' => 'NAME',
            )
        ),
        'USER_LNAME' => array(
            'SORT' => 213,
            'NAME' => Loc::getMessage('TRANZZO_USER_LNAME'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'USER',
                'PROVIDER_VALUE' => 'LAST_NAME',
            )
        ),
    )
);