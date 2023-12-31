<?php

/**
 * 结果对象
 * @author auto create
 */
class ImSummaryResponse
{
	
	/** 
	 * 活跃群数（当日）
	 **/
	public $active_group_count;
	
	/** 
	 * 单聊用户数
	 **/
	public $chat_user_count;
	
	/** 
	 * 群聊用户数
	 **/
	public $group_chat_user_count;
	
	/** 
	 * 总群数
	 **/
	public $group_count;
	
	/** 
	 * 消息数
	 **/
	public $message_total_count;
	
	/** 
	 * 聊天用户数
	 **/
	public $message_user_count;
	
	/** 
	 * 新增群数（当日）
	 **/
	public $new_group_count;	
}
?>