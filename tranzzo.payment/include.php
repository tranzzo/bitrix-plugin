<?
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
$MODULE_ID = "tranzzo.payment";
CModule::AddAutoloadClasses(
	$MODULE_ID,
	array(	)
);
?>
