<?php
/*
 * File: SMSController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:16:24 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;

header("Content-type:text/html; charset=UTF-8");


class SMSController 
{
    //拉取上行数量
    const COUNT = "20";

	//发送短信的接口
	const API_SEND_URL='http://intapi.253.com/send/json?';

	//查询余额的接口
	const API_BALANCE_QUERY_URL='http://intapi.253.com/balance/json?';

	//拉取上行短信明细的接口
	const API_PULL_MO_URL='http://intapi.253.com/pull/mo?';

	//拉取上行短信状态的接口
	const API_PULL_REPORT_URL='http://intapi.253.com/pull/report?';

	const API_ACCOUNT='I2854121';//Get SMS Account  from  https://zz.253.com/site/login.html

	const API_PASSWORD='hk1lUJcga';//Get SMS Password  from https://zz.253.com/site/login.html
	
	/**
	 * 发送短信
	 * @param string $mobile 		手机号码
	 * @param string $msg 			短信内容
	 */
	public function sendInternational( $mobile, $msg) {

		//创蓝接口参数
		$postArr = array (
			'account'  =>  self::API_ACCOUNT,
			'password' => self::API_PASSWORD,
			'msg' => $msg,
			'mobile' => $mobile
        );

		$result = $this->curlPost( self::API_SEND_URL , $postArr);
		return $result;
	}




	/**
	 * 查询额度
	 *
	 *  查询地址
	 */
	public function queryBalance() {
		//查询参数
		$postArr = array (
		    'account' => self::API_ACCOUNT,
		    'password' => self::API_PASSWORD,
		);
		$result = $this->curlPost(self::API_BALANCE_QUERY_URL, $postArr);
		return $result;
	}

	/**
	 * 拉取上行短信
	 *
	 *
	 */
	public function pullMo() {
		//查询参数
		$postArr = array (
		    'account' => self::API_ACCOUNT,
		    'password' => self::API_PASSWORD,
		    'count' => self::COUNT
		);
		$result = $this->curlPost(self::API_PULL_MO_URL, $postArr);
		return $result;
	}

	/**
	 * 拉取上行状态
	 *
	 *
	 */
	public function pullReport() {
		//查询参数
		$postArr = array (
		    'account' => self::API_ACCOUNT,
		    'password' => self::API_PASSWORD,
		    'count' => self::COUNT
		);
		$result = $this->curlPost(self::API_PULL_REPORT_URL, $postArr);
		return $result;
	}

	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数
	 * @return mixed
	 */
	private function curlPost($url,$postFields){
		$postFields = json_encode($postFields);
		$ch = curl_init ();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8'
			)
		);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt( $ch, CURLOPT_TIMEOUT,10);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		$ret = curl_exec ( $ch );
        if (false == $ret) {
            $result = curl_error(  $ch);
        } else {
            $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
            if (200 !== $rsp) {
                $result = "请求状态 ". $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
		curl_close ( $ch );
		return $result;
	}

}
