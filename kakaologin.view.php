<?php
/**
 * @class  kakaologinView
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief kakaologin view class of the module
 */
class kakaologinView extends kakaologin
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispKakaologin', '', $this->act)));
	}

	/**
	 * @brief General request output
	 */
	function dispKakaologinOAuth()
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
/* End of file kakaologin.view.php */
/* Location: ./modules/kakaologin/kakaologin.view.php */
