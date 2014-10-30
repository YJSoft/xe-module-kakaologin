<?php
/**
 * @class  naverloginAdminView
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  naverlogin module admin view class.
 */
class kakaologinAdminView extends kakaologin
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispKakaologinAdmin', '', $this->act)));
	}

	function dispKakaologinAdminConfig()
	{
		$oNaverloginModel = getModel('kakaologin');
		$module_config = $oNaverloginModel->getConfig();

		if(substr($module_config->def_url,-1)!='/')
		{
			$module_config->def_url .= '/';
		}

		Context::set('module_config', $module_config);
	}
}

/* End of file naverlogin.admin.view.php */
/* Location: ./modules/naverlogin/naverlogin.admin.view.php */
