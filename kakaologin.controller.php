<?php
/**
 * @class  naverloginController
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  naverlogin module controller class.
 */

class kakaologinController extends kakaologin
{
	private $error_message;
	private $redirect_Url;

	function init()
	{
	}

	function triggerDisablePWChk($args)
	{
		$cond = new stdClass();
		$cond->srl = $args->member_srl;
		$output = executeQuery('kakaologin.getKakaologinMemberbySrl', $cond);
		if(isset($output->data->enc_id)) $_SESSION['rechecked_password_step'] = 'INPUT_DATA';
		return;
	}

	/**
	 * @brief 회원 탈퇴시 네이버 로그인 DB에서도 삭제
	 * @param $args->member_srl
	 * @return mixed
	 */
	function triggerDeleteNaverloginMember($args)
	{
		$cond = new stdClass();
		$cond->srl = $args->member_srl;
		$output = executeQuery('naverlogin.deleteKakaologinMember', $cond);

		return;
	}

	/**
	 * @brief 아무 것도 안함
	 * @param void
	 * @return void
	 */
	function triggerChkID($args)
	{
		return;
	}

	/**
	 * @brief 네이버로부터 인증코드를 받아와서 Auth키 발급후 회원가입여부 확인뒤 가입 혹은 로그인 처리
	 * @param void
	 * @return mixed
	 */
	function procKakaologinOAuth()
	{
		$this->redirect_Url='';

		$code = Context::get("code");
		if(Context::get("error")!="") return new Object(-1, Context::get("error_description"));
		//API 전솔 실패
		if(!$this->send($code))
		{
			return new Object(-1, $this->error_message);
		}
		else
		{
			if($this->error_message!='')
			{
				$msg = $this->error_message;
				$this->setMessage($msg);
				$this->setRedirectUrl($this->redirect_Url,new Object(-12, $msg));
			}
			else $this->setRedirectUrl($this->redirect_Url);
		}
	}

	/**
	 * @param $state
	 * @param $code
	 * @return bool
	 */
	function send($code) {
		//오류 메세지 변수 초기화
		$this->error_message = '';

		$oModuleModel = getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('kakaologin');

		$oMemberModel = getModel('member');
		$oMemberController = getController('member');

		//설정이 되어있지 않은 경우 리턴
		if(!$oModuleConfig->clientid || !$oModuleConfig->def_url)
		{
			//TODO 다국어화
			$this->error_message = '설정이 되어 있지 않습니다.';
			return false;
		}

		//ssl 연결을 지원하지 않는 경우 리턴(API 연결은 반드시 https 연결이여야 함)
		if(!$this->checkOpenSSLSupport())
		{
			//TODO 다국어화
			$this->error_message = 'OpenSSL 지원이 필요합니다.';
			return false;
		}

		if(substr($oModuleConfig->def_url,-1)!='/')
		{
			$oModuleConfig->def_url .= '/';
		}

		/*
		//API 서버에 code와 state값을 보내 인증키를 받아 온다
		$ping_url = '/oauth2.0/token';
		//?client_id=' . $oModuleConfig->clientid . '&client_secret=' . $oModuleConfig->clientkey . '&grant_type=authorization_code&code=' . $code;
		$ping_header = array();
		$ping_header['Host'] = 'kauth.kakao.com';
		$ping_header['Pragma'] = 'no-cache';
		$ping_header['Accept'] = '*d/*';
		$post_header = array('grant_type'=>'authorization_code','client_id'=>$oModuleConfig->clientid,'redirect_uri'=>$oModuleConfig->def_url . "index.php?act=procKakaologinOAuth",'code'=>$code);

		$request_config = array();
		$request_config['ssl_verify_peer'] = false;

		$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'POST', 'application/x-www-form-urlencoded', $ping_header, array(), $post_header, $request_config);
		*/

		$params = sprintf('grant_type=authorization_code&client_id=%s&redirect_uri=%s&code=%s', $oModuleConfig->clientid, urlencode($oModuleConfig->def_url . "index.php?act=procKakaologinOAuth"), $code);

		$opts = array(
			CURLOPT_URL => "https://kauth.kakao.com/oauth/token",
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false
		);

		$curlSession = curl_init();
		curl_setopt_array($curlSession, $opts);
		$accessTokenJson = curl_exec($curlSession);

		if (FALSE === $accessTokenJson)
		{
			$this->error_message = sprintf("getKey failed - %s(%d)<br>%s",curl_error($curlSession),curl_errno($curlSession),implode(",",curl_getinfo($curlSession)));
			return false;
		}

		curl_close($curlSession);

		$data = json_decode($accessTokenJson);

		/* 받아온 인증키로 바로 회원 정보를 얻어옴
		$ping_url = '/v1/user/me';
		$ping_header = array();
		$ping_header['Host'] = 'kapi.kakao.com';
		$ping_header['Pragma'] = 'no-cache';
		$ping_header['Accept'] = '*d/*';
		$ping_header['Authorization'] = sprintf("Bearer %s", $data->access_token);

		$request_config = array();
		$request_config['ssl_verify_peer'] = false;

		$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'POST', 'application/x-www-form-urlencoded', $ping_header, array(), array(), $request_config);

		//받아온 결과 파싱(XML)
		*/

		$opts = array(
			CURLOPT_URL => "https://kapi.kakao.com/v1/user/me",
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . $data->access_token
			)
		 );

		 $curlSession = curl_init();
		 curl_setopt_array($curlSession, $opts);
		 $accessTokenJson = curl_exec($curlSession);

		if (FALSE === $accessTokenJson)
		{
			$this->error_message = sprintf("getInfo failed - %s(%d)<br>%s",curl_error($curlSession),curl_errno($curlSession),implode(",",curl_getinfo($curlSession)));
			return false;
		}

		curl_close($curlSession);


		$xmlDoc=json_decode($accessTokenJson);

		//회원 설정 불러옴
		$config = $oMemberModel->getMemberConfig();

		if(!isset($xmlDoc->id))
		{
			if(!$accessTokenJson)
			{
				$this->error_message = 'Socket connection error. Check your Server Environment.';
			}
			else
			{
				$this->error_message = 'Unknown error occurred';
			}
			return false;
		}

		//enc_id로 회원이 있는지 조회
		$cond = new stdClass();
		$cond->enc_id=$xmlDoc->id;



		$output = executeQuery('kakaologin.getKakaologinMemberbyEncID', $cond);

		//srl이 있다면(로그인 시도)
		if(isset($output->data->srl))
		{
			$member_Info = $oMemberModel->getMemberInfoByMemberSrl($output->data->srl);
			if($config->identifier == 'email_address')
			{
				$oMemberController->doLogin($member_Info->email_address,'',false);
			}
			else
			{
				$oMemberController->doLogin($member_Info->user_id,'',false);
			}

			//회원정보 변경시 비밀번호 입력 없이 변경 가능하도록 수정
			$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

			if($config->after_login_url) $this->redirect_Url = $config->after_login_url;
			$this->redirect_Url = getUrl('');

			return true;
		}
		else
		{
			// call a trigger (before)
			$trigger_output = ModuleHandler::triggerCall ('member.procMemberInsert', 'before', $config);
			if(!$trigger_output->toBool ()) return $trigger_output;
			// Check if an administrator allows a membership
			if($config->enable_join != 'Y')
			{
				$this->error_message = 'msg_signup_disabled';
				return false;
			}

			$args = new stdClass();
			$args->email_id = "k" . $xmlDoc->id;
			$args->email_host = "kakao.com";
			$args->allow_mailing="N";
			$args->allow_message="Y";
			$args->email_address=substr($args->email_id,0,10) . '@' . $args->email_host;
			while($oMemberModel->getMemberSrlByEmailAddress($args->email_address)){
				$args->email_address=substr($args->email_id,0,5) . substr(md5($code . rand(0,9999)),0,5) . '@' . $args->email_host;
			}
			$args->find_account_answer=md5($code) . '@' . $args->email_host;
			$args->find_account_question="1";
			$args->nick_name=$xmlDoc->properties->nickname;
			while($oMemberModel->getMemberSrlByNickName($args->nick_name)){
				$args->nick_name=$xmlDoc->properties->nickname . substr(md5($code . rand(0,9999)),0,5);
			}
			$args->password=md5($code) . "a1#";
			$args->user_id=substr($args->email_id,0,20);
			while($oMemberModel->getMemberInfoByUserID($args->user_id)){
				$args->user_id=substr($args->email_id,0,10) . substr(md5($code . rand(0,9999)),0,10);
			}
			$args->user_name=$xmlDoc->properties->nickname;

			// remove whitespace
			$checkInfos = array('user_id', 'nick_name', 'email_address');
			$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
			foreach($checkInfos as $val)
			{
				if(isset($args->{$val}))
				{
					$args->{$val} = str_replace($replaceStr, '', $args->{$val});
				}
			}

			$output = $oMemberController->insertMember($args);
			if(!$output->toBool()){
				debugPrint($output);
				return $output;
			}

			$site_module_info = Context::get('site_module_info');
			if($site_module_info->site_srl > 0)
			{
				$columnList = array('site_srl', 'group_srl');
				$default_group = $oMemberModel->getDefaultGroup($site_module_info->site_srl, $columnList);
				if($default_group->group_srl)
				{
					$this->addMemberToGroup($args->member_srl, $default_group->group_srl, $site_module_info->site_srl);
				}

			}

			$naverloginmember = new stdClass();
			$naverloginmember->srl = $args->member_srl;
			$naverloginmember->enc_id = $xmlDoc->id;

			$output = executeQuery('kakaologin.insertKakaologinMember', $naverloginmember);
			if(!$output->toBool())
			{
				return false;
			}

			$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$args->email_id));
			if(!is_dir('./files/cache/tmp')) FileHandler::makeDir('./files/cache/tmp');

			$ch = curl_init($xmlDoc->properties->profile_image);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			$raw=curl_exec($ch);
			curl_close($ch);

			FileHandler::writeFile($tmp_file, $raw);

			if(file_exists($tmp_file))
			{
				$oMemberController->insertProfileImage($args->member_srl, $tmp_file);
			}

			if($config->identifier == 'email_address')
			{
				$oMemberController->doLogin($args->email_address);
			}
			else
			{
				$oMemberController->doLogin($args->user_id);
			}

			$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

			if($config->redirect_url) $this->redirect_Url = $config->redirect_url;
			else
			{
				$this->redirect_Url = getUrl('', 'act', 'dispMemberModifyEmailAddress');
				$this->error_message = '이메일 주소 변경이 필요합니다.';
			}

			FileHandler::removeFile($tmp_file);

			return true;
		}
	}
}

/* End of file naverlogin.controller.php */
/* Location: ./modules/naverlogin/naverlogin.controller.php */
