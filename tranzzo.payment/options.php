<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

$MODULE_ID = 'tranzzo.payment';

Loader::includeModule($MODULE_ID);

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$aTabs = array(
    array(
        "DIV" 	  => "edit",
        "TAB" 	  => Loc::getMessage("TRANZZO.tab_settings"),
//        "TITLE"   => Loc::getMessage("TRANZZO.tab_settings"),
    )
);

$OPTIONS = [
    Loc::getMessage("TRANZZO.settings"),
    [
        "POS_ID",
        Loc::getMessage("TRANZZO.POS_ID"),
        Option::get($MODULE_ID, 'POS_ID'),
        ["text", 100]
    ],
    [
        "API_KEY",
        Loc::getMessage("TRANZZO.API_KEY"),
        Option::get($MODULE_ID, 'API_KEY'),
        ["password", 100]
    ],
    [
        "API_SECRET",
        Loc::getMessage("TRANZZO.API_SECRET"),
        Option::get($MODULE_ID, 'API_SECRET'),
        ["password", 100]
    ],
    [
        "ENDPOINTS_KEY",
        Loc::getMessage("TRANZZO.ENDPOINTS_KEY"),
        Option::get($MODULE_ID, 'ENDPOINTS_KEY'),
        ["password", 100]
    ],
    [
        "PAYMENT_ACTION",
        Loc::getMessage("TRANZZO.PAYMENT_ACTION"),
        Option::get($MODULE_ID, 'PAYMENT_ACTION'),
        [
            "selectbox",
            [
                Tranzzo\Api\Payment::P_METHOD_PURCHASE => Loc::getMessage('TRANZZO.ACTION_PURCHASE'),
                Tranzzo\Api\Payment::P_METHOD_AUTH => Loc::getMessage('TRANZZO.ACTION_AUTH'),
            ]
        ]
    ],
];


if($request->isPost() && check_bitrix_sessid()){

    foreach($OPTIONS as $arOption){
        if(!is_array($arOption)){continue;}

        if($request["save"]){
            $optionValue = $request->getPost($arOption[0]);

            Option::set($MODULE_ID, $arOption[0], $optionValue);
        }
    }

    LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . $MODULE_ID . "&lang=" . LANG);
}


$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->begin();
?>
<form action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($MODULE_ID); ?>&lang=<? echo(LANG); ?>" method="POST">
<?
    $tabControl->BeginNextTab();

    __AdmSettingsDrawList($MODULE_ID, $OPTIONS);

    $tabControl->Buttons();
?>
    <input type="submit" name="save" value="<? echo(Loc::getMessage("MAIN_SAVE")); ?>" class="adm-btn-save" />
    <?=bitrix_sessid_post();?>
</form>
<?
$tabControl->End();
?>

