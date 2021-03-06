<?php

namespace Advanced_Mailer;

/**
 * @file woorimail.class.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Transport: Woorimail
 */
class Woorimail extends Base
{
	public static $_error_codes = array(
		'me_001' => '@ 없는 이메일 주소가 있습니다.',
		'me_002' => '이메일 주소가 존재하지 않습니다.',
		'me_003' => '닉네임이 존재하지 않습니다.',
		'me_004' => '등록일이 존재하지 않습니다.',
		'me_005' => '이메일과 닉네임 갯수가 다릅니다.',
		'me_006' => '닉네임과 등록일 갯수가 다릅니다.',
		'me_007' => '이메일과 등록일 갯수가 다릅니다.',
		'me_008' => '이메일 갯수가 2,000개 넘습니다.',
		'me_009' => 'type이 api가 아닙니다.',
		'me_010' => '인증키가 없습니다.',	
		'me_011' => '인증키가 부정확합니다.',
		'me_012' => '포인트가 부족합니다.',
		'me_013' => '전용채널에 도메인이 등록되어 있지 않습니다.',
	);
	
	public $assembleMessage = false;
	
	public function send()
	{
		$data = array(
			'title' => $this->message->getSubject(),
			'content' => $this->content,
			'sender_email' => '',
			'sender_nickname' => '',
			'receiver_email' => array(),
			'receiver_nickname' => array(),
			'member_regdate' => date('YmdHis'),
			'domain' => self::$config->woorimail_domain,
			'authkey' => self::$config->woorimail_api_key,
			'wms_domain' => 'woorimail.com',
			'wms_nick' => 'NOREPLY',
			'type' => 'api',
			'mid' => 'auth_woorimail',
			'act' => 'dispWwapimanagerMailApi',
			'callback' => '',
			'is_sendok' => 'W',
		);
		
		$from = $this->message->getFrom();
		foreach($from as $email => $name)
		{
			$data['sender_email'] = $email;
			$data['sender_nickname'] = $name;
		}
		
		if(self::$config->woorimail_account_type === 'paid')
		{
			$sender_email = explode('@', $data['sender_email']);
			if(count($sender_email) === 2)
			{
				$data['wms_nick'] = $sender_email[0];
				$data['wms_domain'] = $sender_email[1];
			}
		}
		else
		{
			/*
			$replyTo = $this->message->getReplyTo();
			if(count($replyTo))
			{
				reset($replyTo);
				$data['sender_email'] = key($replyTo);
			}
			*/
		}
		$to = $this->message->getTo();
		foreach($to as $email => $name)
		{
			$data['receiver_email'][] = $email;
			$data['receiver_nickname'][] = str_replace(',', '', $name);
		}
		$cc = $this->message->getCc();
		foreach($cc as $email => $name)
		{
			$data['receiver_email'][] = $email;
			$data['receiver_nickname'][] = str_replace(',', '', $name);
		}
		$bcc = $this->message->getBcc();
		foreach($bcc as $email => $name)
		{
			$data['receiver_email'][] = $email;
			$data['receiver_nickname'][] = str_replace(',', '', $name);
		}
		
		$data['receiver_email'] = implode(',', $data['receiver_email']);
		$data['receiver_nickname'] = implode(',', $data['receiver_nickname']);
		
		$url = 'https://woorimail.com:20080/index.php';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . '/tpl/cacert/cacert.pem');
		$result = curl_exec($ch);
		curl_close($ch);
		
		if($result !== false && ($result = @json_decode($result, true)) && $result['result'] === 'OK')
		{
			return true;
		}
		else
		{
			if(isset($result['error_msg']))
			{
				if(isset(self::$_error_codes[$result['error_msg']]))
				{
					$result['error_msg'] .= ' ' . self::$_error_codes[$result['error_msg']];
				}
				$this->errors = array('Woorimail: ' . $result['error_msg']);
			}
			return false;
		}
	}
}
