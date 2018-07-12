<?php
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

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
        $this->MODULE_VERSION_DATE   = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_GROUP_RIGHTS   = 'N';
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
        CopyDirFiles( __DIR__ . "/engine/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/paysystem", true, true);

        return true;
    }
 
    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/modules/sale/handlers/paysystem/tranzzo");

        return true;
    }

    function InstallDB($arParams = array())
    {
        global $DB, $APPLICATION;

        return true;
    }

    function UnInstallDB($arParams = array())
    {
        global $DB;

        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UnInstallEvents()
    {
        return true;
    }
}