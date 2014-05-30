<?php
/*
Plugin Name: 同步到豆瓣
Version: 0.1
Plugin URL:http://www.justintseng.com
Description: 发表日志和碎语时可选择是否拷备一份至豆瓣日记和广播。
Author: Justin Tseng
Author Email: justintseng@foxmail.com
Author URL: http://www.justintseng.com
*/
if(!defined('EMLOG_ROOT')) {exit('error!');}

//同步到豆瓣日记
function douban_hide()
{
?>
    <input type="checkbox" id="douban_note" value="1" name="douban_note" checked="checked" /><label for="douban_note">同步到豆瓣日记</label>
<?php
}
    addAction('adm_writelog_head','douban_hide');//挂载

function douban_publish()//发布日志
{	
  global $logData,$action,$blogid,$douban_hide;
  $douban_hide = isset($_POST['douban_note']) ? 'y' : 'n';
  
  if($action == 'add')
  {
	if($logData['password'] != '')
	{
	 // $logData['content'] ='此日志为加密日志，请<a href='.BLOG_URL.'?post='.$blogid.' target="_blank">点击此处</a>查看';
		$logData['hide'] ='y';
	}
	if($logData['hide'] != 'y' && $GLOBALS["douban_hide"] != 'n')
	{
		$title = $logData['title'];
		$content = str_replace(array('&nbsp;', ' '), array(),strip_tags(stripslashes($logData['content']))) . '
我在这里码字：' .Url::log($blogid);

		// 载入豆瓣Oauth类
		require_once 'DoubanOauth.php';
		require_once 'douban_config.php';
		require_once 'douban_token_conf.php';
		
		//获取本地存储的验证信息
		$douban_access_token = douban_access_token;
		$douban_refresh_token = douban_refresh_token;
		$douban_expires_in = douban_expires_in;
		$douban_user_id = douban_user_id;
		
		/* ------------实例化Oauth2--------------- */
		$appConfig = array(
					// 必选参数，豆瓣应用public key。
					'client_id' => douban_clientId,
					// 必选参数，豆瓣应用secret key。
					'secret' => douban_secret,
					// 必选参数，用户授权后的回调链接。
					'redirect_uri' => BLOG_URL . douban_callback2,
					// 可选参数（默认为douban_basic_common），授权范围。
					'scope' => douban_scope,
					// 可选参数（默认为false），是否在header中发送accessToken。
					'need_permission' => true
					);
		// 生成一个豆瓣Oauth类实例
		$douban = new DoubanOauth($appConfig);
		
		//获取更新的token
		$douban_time_now = time();
		$douban_time = douban_time;
		if($douban_time_now-$douban_time>=$douban_expires_in)
		{
			//传递更新验证参数
			$douban->setRefreshToken($douban_refresh_token);
			//更新
			$douban->refresh();
			$douban_access_token = $douban->getAccessToken();
			$douban_expires_in = $douban->getExpiresIn();
			$douban_refresh_token = $douban->getRefreshToken();
			$douban_user_id = $douban->getDoubanUserid();
			
			//存储Token信息
			$douban_profile = '../content/plugins/douban/douban_token_conf.php';
			$douban_time = time();

			if($douban_access_token!='' && $douban_expires_in!='' && $douban_refresh_token!='' && $douban_user_id!=''){			
				$douban_new_profile = "<?php\ndefine('douban_access_token','$douban_access_token');\ndefine('douban_expires_in','$douban_expires_in');\ndefine('douban_refresh_token','$douban_refresh_token');\ndefine('douban_user_id','$douban_user_id');\ndefine('douban_time','$douban_time');\n";
				
				$douban_fp = @fopen($douban_profile,'wb');
				fwrite($douban_fp,$douban_new_profile);
				fclose($douban_fp);
			}
		}
			//传递验证参数到Oauth
			$douban->setAccessToken($douban_access_token);

		
		/* ------------发送日志--------------- */
		// 通过豆瓣API发送一篇带图片的豆瓣日志
		$data = array(
				'title' => $title,
				'privacy' => 'public',
				'can_reply' => 'true',
				'content' => $content,
				'pids' => '',
				'layout_pid' => 'L',
				'desc_pid' => '',
				'image_pid' => '',
					  );
		
		$miniblog = $douban->api('Note.note.POST')->makeRequest($data);
	  }
	}
}
	addAction('save_log','douban_publish');//挂载


//同步到豆瓣广播
function douban_twitter_hide()
{
?>
    <input type="checkbox" id="douban_twitter" value="1" name="douban_twitter" checked="checked" /><label for="douban_twitter">同步到豆瓣广播</label>
<?php
}
	addAction('twitter_head','douban_twitter_hide');//挂载

function douban_twitter_hide2()
{
?>
    <input type="checkbox" id="douban_twitter" value="1" name="douban_twitter" checked="checked" /><label for="douban_twitter">同步到豆瓣广播</label>
<?php
}
	addAction('adm_twitter_head','douban_twitter_hide2');//挂载

function douban_twitter_publish()//发布碎语
{	
  global $t,$douban_hide;
  $douban_hide = isset($_POST['douban_twitter']) ? 'y' : 'n';
  
	if($GLOBALS["douban_hide"] != 'n')
	{
		$text = stripcslashes(subString($t, 0, 300));

		// 载入豆瓣Oauth类
		require_once 'DoubanOauth.php';
		require_once 'douban_config.php';
		require_once 'douban_token_conf.php';
		
		//获取本地存储的验证信息
		$douban_access_token = douban_access_token;
		$douban_refresh_token = douban_refresh_token;
		$douban_expires_in = douban_expires_in;
		$douban_user_id = douban_user_id;
		
		/* ------------实例化Oauth2--------------- */
		$appConfig = array(
					// 必选参数，豆瓣应用public key。
					'client_id' => douban_clientId,
					// 必选参数，豆瓣应用secret key。
					'secret' => douban_secret,
					// 必选参数，用户授权后的回调链接。
					'redirect_uri' => BLOG_URL . douban_callback2,
					// 可选参数（默认为douban_basic_common），授权范围。
					'scope' => douban_scope,
					// 可选参数（默认为false），是否在header中发送accessToken。
					'need_permission' => true
					);
		// 生成一个豆瓣Oauth类实例
		$douban = new DoubanOauth($appConfig);
		
		//获取更新的token
		$douban_time_now = time();
		$douban_time = douban_time;
		if($douban_time_now-$douban_time>=$douban_expires_in)
		{
			//传递更新验证参数
			$douban->setRefreshToken($douban_refresh_token);
			//更新
			$douban->refresh();
			$douban_access_token = $douban->getAccessToken();
			$douban_expires_in = $douban->getExpiresIn();
			$douban_refresh_token = $douban->getRefreshToken();
			$douban_user_id = $douban->getDoubanUserid();
			
			//存储Token信息
			$douban_profile = '../content/plugins/douban/douban_token_conf.php';
			$douban_time = time();

			if($douban_access_token!='' && $douban_expires_in!='' && $douban_refresh_token!='' && $douban_user_id!=''){			
				$douban_new_profile = "<?php\ndefine('douban_access_token','$douban_access_token');\ndefine('douban_expires_in','$douban_expires_in');\ndefine('douban_refresh_token','$douban_refresh_token');\ndefine('douban_user_id','$douban_user_id');\ndefine('douban_time','$douban_time');\n";
				
				$douban_fp = @fopen($douban_profile,'wb');
				fwrite($douban_fp,$douban_new_profile);
				fclose($douban_fp);
			}
		}
		//传递验证参数到Oauth
		$douban->setAccessToken($douban_access_token);
		
		/* ------------发送图片广播--------------- */
		// 通过豆瓣API发送一条带图片的豆瓣广播
		$data = array(
					'source' => $appConfig['client_id'], 
					'text' => $text, 
					'image' => ''
					);
		
		$miniblog = $douban->api('Miniblog.statuses.POST')->makeRequest($data);
	}
}
	addAction('post_twitter','douban_twitter_publish');//挂载


//管理菜单
function douban_menu()
{
	echo '<div class="sidebarsubmenu" id="douban"><a href="./plugin.php?plugin=douban">同步到豆瓣</a></div>';
}
	addAction('adm_sidebar_ext', 'douban_menu');//挂载
		
?>