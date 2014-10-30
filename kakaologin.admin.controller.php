<?php
/**
 * @class  naverloginAdminController
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  naverlogin module admin controller class.
 */

class kakaologinAdminController extends kakaologin
{
	function init()
	{
	}

	function procKakaologinAdminInsertConfig()
	{
		$oModuleController = getController('module');
		$oNaverloginModel = getModel('kakaologin');

		$vars = Context::getRequestVars();
		$section = $vars->_config_section;

		$config = $oNaverloginModel->getConfig();
		$config->clientid = $vars->clientid;
		$config->def_url = $vars->def_url;

		$oModuleController->updateModuleConfig('kakaologin', $config);


		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispKakaologinAdminConfig'));
	}
}

/* End of file naverlogin.admin.controller.php */
/* Location: ./modules/naverlogin/naverlogin.admin.controller.php */
