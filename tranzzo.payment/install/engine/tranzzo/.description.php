<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$data = array(
    'NAME' => 'TRANZZO',
    'SORT' => 100,
    'CODES' => array(
        'ORDER_ID' => array(
            'SORT' => 110,
            'NAME' => Loc::getMessage('TRANZZO_ORDER_ID'),
            'DEFAULT' => array(
                'PROVIDER_KEY' => 'ORDER',
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