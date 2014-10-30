<?php

class kakaologinModel extends kakaologin
{
	private $config;

	function init()
	{
	}

	/**
	 * @brief 모듈 설정 반환
	 */
	function getConfig()
	{
		if(!$this->config)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('kakaologin');

			$this->config = $config;
		}

		return $this->config;
	}

	function generate_state() {
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand);
	}
}

/* End of file profiler.model.php */
/* Location: ./modules/profiler/profiler.model.php */
