<?php
if(!defined('EMLOG_ROOT')) {exit('error!');}
function plugin_setting_view()
{
	// 载入豆瓣Oauth
	require_once(EMLOG_ROOT.'/content/plugins/douban/DoubanOauth.php');
	require_once(EMLOG_ROOT.'/content/plugins/douban/douban_config.php');
	require_once(EMLOG_ROOT.'/content/plugins/douban/douban_token_conf.php');
		
	//获取本地存储的验证信息
	$douban_access_token = douban_access_token;
	$douban_time_now = time();
	$douban_time = douban_time;
	$douban_expires_in = douban_expires_in;
?>
<div class=containertitle><b>同步到豆瓣</b>
	<?php if(isset($_GET['setting'])):?><span class="actived">插件设置完成</span><?php endif;?>
</div>
<div class=line></div>
<div class="des">同步到豆瓣日记插件基于豆瓣API，可以在发布日志、碎语时选择是否自动同步到你的豆瓣日记、广播。<br />请先到<a href="http://developers.douban.com/apikey/">豆瓣开放平台</a>创建应用，并添加自己为测试用户，设置API权限为豆瓣公共、豆瓣广播和豆瓣社区，然后将API Key和Secret填入以下输入框保存，即可开始绑定帐号。
<br />注：加密日志将不被同步。</div>
<div>
<?php
//如果已授权且未过期	
if(isset($douban_access_token) && $douban_time_now-$douban_time<=$douban_expires_in){
?>
    <p><img src="http://img3.douban.com/icon/ul<?php echo douban_user_id; ?>.jpg" width="80" height="80" alt="" />
    <br />已成功绑定！</p>
<?php
}
else{
//未授权或已过期
	/* ------------实例化Oauth2--------------- */
	$appConfig = array(
				// 必选参数，豆瓣应用public key。
				'client_id' => douban_clientId,
				// 必选参数，豆瓣应用secret key。
				'secret' => douban_secret,
				// 必选参数，用户授权后的回调链接。
				'redirect_uri' => BLOG_URL . douban_callback,
				// 可选参数（默认为douban_basic_common），授权范围。
				'scope' => douban_scope,
				// 可选参数（默认为false），是否在header中发送accessToken。
				'need_permission' => true
				);
	//生成一个豆瓣Oauth类实例
	$douban = new DoubanOauth($appConfig);
	//获取授权链接
	$authorizationUrl = $douban->getAuthorizeUrl();
?>
    <p><a href="<?php echo $authorizationUrl; ?>" >绑定豆瓣帐号</a></p> 
<?php
	/* ------------请求用户授权--------------- */
	if ( isset($_GET['code'])) {
		// 设置authorizeCode
		$douban->setAuthorizeCode($_GET['code']);
		
		// 通过authorizeCode获取accessToken，至此完成用户授权
		$douban->requestAccessToken();
		$douban_access_token = $douban->getAccessToken();
		$douban_expires_in = $douban->getExpiresIn();
		$douban_refresh_token = $douban->getRefreshToken();
		$douban_user_id = $douban->getDoubanUserid();
		
		if($douban_access_token!='' && $douban_expires_in!='' && $douban_refresh_token!='' && $douban_user_id!=''){
		//存储Token信息
		$douban_profile = EMLOG_ROOT.'/content/plugins/douban/douban_token_conf.php';
		$douban_time = time();
		$douban_new_profile = "<?php\ndefine('douban_access_token','$douban_access_token');\ndefine('douban_expires_in','$douban_expires_in');\ndefine('douban_refresh_token','$douban_refresh_token');\ndefine('douban_user_id','$douban_user_id');\ndefine('douban_time','$douban_time');\n";
		$douban_fp = @fopen($douban_profile,'wb');
		if(!$douban_fp) {
			emMsg('操作失败，请确保插件目录(/content/plugins/douban/)可写');
		}
		fwrite($douban_fp,$douban_new_profile);
		fclose($douban_fp);
		}
	}
	}
?>
</div>
<div>
<form id="form1" name="form1" method="post" action="plugin.php?plugin=douban&action=setting">
<table width="540" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="90"><span style="width:300px;">API Key</span></td>
<td width="450"><input name="clientId" type="text" id="clientId" style="width:180px;" value="<?php echo douban_clientId;?>"/></td>
</tr>
<tr>
<td>Secret</td>
<td><input type="secret" name="secret" value="<?php echo douban_secret;?>" style="width:180px;"/></td>
</tr>
<tr>
<td height="30">&nbsp;</td>
<td><input name="Input" type="submit" value="提交" /> <input name="Input" type="reset" value="取消" /></td>
</tr>
</table>
</form>
<br/>
说明：请确认本插件目录下“douban_config.php”、“douban_token_conf”文件据有可读写权限。如有疑问，请访问<a href="http://www.justintseng.com/emlog-douban" target="_blank">我的博客</a>留言，将尽量解答。
</div>
<?php
}
function plugin_setting()
{
    include(EMLOG_ROOT.'/content/plugins/douban/douban_config.php');
	$douban_fso = fopen(EMLOG_ROOT.'/content/plugins/douban/douban_config.php','r');
	$douban_config = fread($douban_fso,filesize(EMLOG_ROOT.'/content/plugins/douban/douban_config.php'));
	fclose($douban_fso);

	$douban_clientId = htmlspecialchars($_POST['clientId'], ENT_QUOTES);
	$douban_secret = htmlspecialchars($_POST['secret'], ENT_QUOTES);
	$interval = is_numeric($_POST['interval'])&&$_POST['interval'] > 0 ? $_POST['interval'] : '0';
	$douban_patt = array("/define\('douban_clientId',(.*)\)/","/define\('douban_secret',(.*)\)/","/define\('INTERVAL',(.*)\)/");
	$douban_replace = array("define('douban_clientId','".$douban_clientId."')","define('douban_secret','".$douban_secret."')","define('INTERVAL','".$interval."')");
	$douban_new_config = preg_replace($douban_patt, $douban_replace, $douban_config);
	$douban_fso =@fopen(EMLOG_ROOT.'/content/plugins/douban/douban_config.php','w');
	if(!$douban_fso) emMsg('数据存取失败，请确认本插件目录下"douban_config.php"文件为可读写权限(777)！');
	fwrite($douban_fso,$douban_new_config);
	fclose($douban_fso);
}
?>