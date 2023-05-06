<?php
declare(strict_types=1);
namespace GDO\Instagram;

use GDO\Core\GDT_Template;
use GDO\UI\GDT_Button;
use GDO\Util\Strings;

/**
 * Login with Instagram button.
 *
 * @author gizmore
 * @version 7.0.3
 */
final class GDT_IGAuthButton extends GDT_Button
{

	protected function __construct()
	{
		parent::__construct();
		$this->name('btn_instagram');
		$this->href($this->instagramURL());
	}

	public function instagramURL()
	{
		$module = Module_Instagram::instance();
		$clientId = $module->cfgClientID();
		$redirectURL = Strings::rsubstrTo(url('Instagram', 'Auth'), '&me=');
		return "https://instagram.com/oauth/authorize/?client_id=$clientId&redirect_uri=$redirectURL&response_type=code";
	}

	public function renderHTML(): string
	{
		return GDT_Template::php('Instagram', 'igauthbutton.php', [
			'field' => $this]);
	}

}
