<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  naverlogin
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  Naver Login module high class.
 */

class kakaologin extends ModuleObject
{
	//$output = ModuleHandler::triggerCall('member.updateMember', 'before', $args);
	private $triggers = array(
		array('member.deleteMember', 'kakaologin', 'controller', 'triggerDeleteNaverloginMember', 'after'),
		array('member.procMemberModifyInfo', 'kakaologin', 'controller', 'triggerDisablePWChk', 'after')
	);

	function moduleInstall()
	{
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new Object(0, 'success_updated');
	}

	function moduleUninstall()
	{
		return new Object();
	}

	function recompileCache()
	{
		return new Object();
	}

	function checkOpenSSLSupport()
	{
		if(!in_array('ssl', stream_get_transports())) {
			return FALSE;
		}
		return TRUE;
	}
}

/* End of file naverlogin.class.php */
/* Location: ./modules/naverlogin/naverlogin.class.php */