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
        "TAB" 	  => Loc::getMessage("tranzzo.tab_settings"),
//        "TITLE"   => Loc::getMessage("tranzzo.tab_settings"),
    )
);

$OPTIONS = array(
    Loc::getMessage("tranzzo.settings"),
    array(
        "POS_ID",
        Loc::getMessage("tranzzo.POS_ID"),
        Option::get($MODULE_ID, 'POS_ID'),
        array("text", 100)
    ),
    array(
        "API_KEY",
        Loc::getMessage("tranzzo.API_KEY"),
        Option::get($MODULE_ID, 'API_KEY'),
        array("password", 100)
    ),
    array(
        "API_SECRET",
        Loc::getMessage("tranzzo.API_SECRET"),
        Option::get($MODULE_ID, 'API_SECRET'),
        array("password", 100)
    ),
    array(
        "ENDPOINTS_KEY",
        Loc::getMessage("tranzzo.ENDPOINTS_KEY"),
        Option::get($MODULE_ID, 'ENDPOINTS_KEY'),
        array("password", 100)
    ),
);


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

