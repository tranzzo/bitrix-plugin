<?php
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class tranzzo_payment extends CModule{
    public function tranzzo_payment()
    {
        $arModuleVersion = array();
		include( dirname(__FILE__) . "/version.php" );
        $this->PARTNER_NAME = "TRANZZO";
		$this->PARTNER_URI = "https://tranzzo.com/";
        $this->MODULE_ID = 'tranzzo.payment';
        $this->MODULE_NAME = 'TRANZZO';
        $this->MODULE_DESCRIPTION = Loc::getMessage( 'USER_PM_MODULE_DESC' );
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_GROUP_RIGHTS = 'N';
    }

    public function DoInstall()
    {
        if( !$this->InstallDB() || !$this->InstallEvents() || !$this->InstallFiles() ) {
            return;
        }
 
        ModuleManager::RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        if(  !$this->UnInstallDB() || !$this->UnInstallEvents() ||  !$this->UnInstallFiles() ) {
            return;
        }
        Main\Config\Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
 
    public function InstallFiles()
    {
        CopyDirFiles( __DIR__ . '/engine/', Application::getDocumentRoot() . '/bitrix/modules/sale/handlers/paysystem', true, true);

        return true;
    }
 
    public function UnInstallFiles()
    {
        DeleteDirFilesEx('/bitrix/modules/sale/handlers/paysystem/tranzzo');

        return true;
    }

    function InstallDB($arParams = array())
    {
        global $DB, $APPLICATION;

        $dataStatus = [
            'ID' => 'HL',
            'LANG' => [
                [
                    'LID' => 'ru',
                    'NAME' => Loc::getMessage('TRANZZO.statusHold', null, 'ru'),
                    'DESCRIPTION' => '',
                ],
                [
                    'LID' => 'en',
                    'NAME' => 'Invoice Hold',
                    'DESCRIPTION' => '',
                ],
            ],
            'NOTIFY' => 'N',
        ];
        Main\Loader::includeModule('sale');
        Sale\OrderStatus::install($dataStatus);

        return true;
    }

    function UnInstallDB($arParams = array())
    {
        global $DB;

        return true;
    }

    public function InstallEvents()
    {
//        RegisterModuleDependences('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderChanged');

        EventManager::getInstance()
            ->registerEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderChanged');

        EventManager::getInstance()
            ->registerEventHandler('sale', 'OnSaleOrderPaid', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderPaid');

        EventManager::getInstance()
            ->registerEventHandler('main', 'OnAdminContextMenuShow', $this->MODULE_ID, 'ListenerTranzzoPayment', 'OnAdminContextMenuShowHandler');

        return true;
    }

    public function UnInstallEvents()
    {
//        UnRegisterModuleDependences('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderChanged');

        EventManager::getInstance()
            ->unRegisterEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderChanged' );

        EventManager::getInstance()
            ->unRegisterEventHandler('sale', 'OnSaleOrderPaid', $this->MODULE_ID, 'ListenerTranzzoPayment', 'orderPaid' );

        EventManager::getInstance()
            ->unRegisterEventHandler('main', 'OnAdminContextMenuShow', $this->MODULE_ID, 'ListenerTranzzoPayment', 'OnAdminContextMenuShowHandler');

        return true;
    }
}