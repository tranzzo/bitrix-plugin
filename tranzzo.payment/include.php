<?
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);
$MODULE_ID = "tranzzo.payment";
CModule::AddAutoloadClasses(
	$MODULE_ID,
	array(
			'ListenerTranzzoPayment' => 'classes/general/listener.php',
			'\Tranzzo\Api\Payment' => 'lib/api/Payment.php',
			'\Tranzzo\Api\InfoProduct' => 'lib/api/InfoProduct.php',
			'\Tranzzo\Api\ResponseParams' => 'lib/api/ResponseParams.php',
	)
);
?>
