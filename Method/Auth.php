<?php
namespace GDO\Instagram\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Hook;
use GDO\Facebook\GDO_OAuthToken;
use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\Instagram\GDT_IGAuthButton;
use GDO\Instagram\Module_Instagram;
use GDO\Login\Method\Form;
use GDO\Net\HTTP;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;
use GDO\Util\Common;
use GDO\Util\Strings;

/**
 * OAuth connector.
 *
 * @version 6.08
 * @author gizmore
 */
class Auth extends MethodForm
{

	public function isUserRequired(): bool { return false; }

	public function getUserType(): ?string { return 'ghost'; }

	public function createForm(GDT_Form $form): void
	{
		$form->addFields(
			GDT_IGAuthButton::make(),
		);
	}

	public function execute(): GDT
	{
		if ($code = Common::getRequestString('code'))
		{
			return $this->getAccessToken($code);
		}
		return parent::execute();
	}

	private function getAccessToken($code)
	{
		$url = 'https://api.instagram.com/oauth/access_token';
		$module = Module_Instagram::instance();
		$data = [
			'client_id' => $module->cfgClientID(),
			'client_secret' => $module->cfgSecret(),
			'redirect_uri' => Strings::rsubstrTo(url('Instagram', 'Auth'), '&me='),
			'code' => $code,
			'grant_type' => 'authorization_code',
		];
		if (!($result = HTTP::post($url, $data)))
		{
			return $this->error('err_instagram_access_token', [t('err_instagram_no_response')]);
		}
		if (!$data = @json_decode($result))
		{
			return $this->error('err_instagram_access_token', [$result]);
		}
		if (!@$data->access_token)
		{
			return $this->error('err_instagram_access_token', [print_r($data, true)]);
		}
		return $this->gotAccessTokenAndData($data->access_token, $data->user);
	}

	public function gotAccessTokenAndData($accessToken, $data)
	{
		$fbData = [
			'id' => $data->id,
			'name' => $data->full_name,
			'email' => null,
		];

		$user = GDO_OAuthToken::refresh($accessToken, $fbData, 'IG');

		GDO_User::setCurrent($user);
		GDO_Session::instance()->saveVar('sess_user', $user->getID());

		$activated = $user->tempGet('justActivated');

		# Temp was in activation state?
		if ($activated)
		{
			GDT_Hook::callWithIPC('UserActivated', $user, null);
			GDT_Hook::callWithIPC('IGUserActivated', $user, $accessToken, $data);
		}

		GDT_Hook::callWithIPC('IGUserAuthenticated', $user, $accessToken, $data);

		return Form::make()->loginSuccess($user);
	}

	public function gotAccessToken($accessToken)
	{
		$url = 'https://api.instagram.com/v1/users/self/?access_token=' . $accessToken;
		$result = HTTP::getFromURL($url);
		$data = json_decode($result);
		return $this->gotAccessTokenAndData($accessToken, $data->data);
	}

}
