<?php
/**
 * dingtalk API: dingtalk.oapi.rhino.dtech.machine.param.list request
 * 
 * @author auto create
 * @since 1.0, 2020.03.11
 */
class OapiRhinoDtechMachineParamListRequest
{
	/** 
	 * 工序bizid列表
	 **/
	private $bizIdProcessList;
	
	private $apiParas = array();
	
	public function setBizIdProcessList($bizIdProcessList)
	{
		$this->bizIdProcessList = $bizIdProcessList;
		$this->apiParas["biz_id_process_list"] = $bizIdProcessList;
	}

	public function getBizIdProcessList()
	{
		return $this->bizIdProcessList;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.rhino.dtech.machine.param.list";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->bizIdProcessList,"bizIdProcessList");
		RequestCheckUtil::checkMaxListSize($this->bizIdProcessList,20,"bizIdProcessList");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
