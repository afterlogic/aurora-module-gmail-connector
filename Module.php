<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\GMailConnector;

/**
 * Adds ability to work with GMail inside Mail module.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{

	protected $sService = 'gmail';

	protected $aRequireModules = array(
		'OAuthIntegratorWebclient',
		'GoogleAuthWebclient'
	);

	protected function issetScope($sScope)
	{
		return \in_array($sScope, \explode(' ', $this->getConfig('Scopes')));
	}

	public function init()
	{
		$this->subscribeEvent('PopulateScopes', array($this, 'onPopulateScopes'));

		$this->subscribeEvent('Mail::BeforeDeleteAccount', array($this, 'onBeforeDeleteAccount'));
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
		$this->subscribeEvent('ResetAccessToken', array($this, 'onResetAccessToken'));
		$this->subscribeEvent('GetAccessToken', array($this, 'onGetAccessToken'));
	}

		/**
	 * Deletes cPanel account, its aliases, forward, autoresponder and filters.
	 * @param array $aArgs
	 * @param mixed $mResult
	 */
	public function onBeforeDeleteAccount($aArgs, &$mResult)
	{
		$oAccount = $aArgs['Account'];
		if ($oAccount instanceof \Aurora\Modules\Mail\Classes\Account)
		{
			\Aurora\Modules\OAuthIntegratorWebclient\Module::Decorator()->DeleteAccount(
				$oAccount->XOAuth,
				$oAccount->Email
			);
		}
	}


	public function onPopulateScopes($sScope, &$aResult)
	{
		$aScopes = \explode('|', $sScope);
		foreach ($aScopes as $sScope)
		{
			if ($sScope === 'mail')
			{
				$aResult[] = 'https://mail.google.com/';
			}
		}
	}

	/**
	 * Passes data to connect to service.
	 *
	 * @ignore
	 * @param string $aArgs Service type to verify if data should be passed.
	 * @param boolean|array $mResult variable passed by reference to take the result.
	 */
	public function onOAuthIntegratorAction($aArgs, &$mResult)
	{
		if ($aArgs['Service'] === $this->sService)
		{
			$sOAuthScopes = isset($_COOKIE['oauth-scopes']) ? $_COOKIE['oauth-scopes'] : '';
			$aGoogleScopes = [
				'https://www.googleapis.com/auth/userinfo.email',
				'https://www.googleapis.com/auth/userinfo.profile'
			];
			$this->broadcastEvent('PopulateScopes', $sOAuthScopes, $aGoogleScopes);

			$mResult = false;
			$oConnector = new Classes\Connector($this);
			if ($oConnector)
			{
				$oGoogleModule = \Aurora\System\Api::GetModule('Google');
				if ($oGoogleModule)
				{
					$sId = $oGoogleModule->getConfig('Id');
					$sSecret = $oGoogleModule->getConfig('Secret');

					$mResult = $oConnector->Init(
						$sId,
						$sSecret,
						[$sOAuthScopes, \implode(' ', $aGoogleScopes)]
					);
					if (isset($mResult['type']))
					{
						$mResult['type'] = $this->sService;

						$oAccount = \Aurora\Modules\OAuthIntegratorWebclient\Module::Decorator()->GetAccount($mResult['type'], $mResult['email']);
						if ($oAccount instanceof \Aurora\Modules\OAuthIntegratorWebclient\Classes\Account)
						{
							$mResult = false;
						}
					}
				}
			}
			return true;
		}
	}

	public function onResetAccessToken($aArgs)
	{
		if ($aArgs['Service'] === $this->sService)
		{
			$oConnector = new Classes\Connector($this);
			if ($oConnector)
			{
				$oGoogleModule = \Aurora\System\Api::GetModule('Google');
				if ($oGoogleModule)
				{
					$mResult = $oConnector->ResetAccessToken(
						$oGoogleModule->getConfig('Id'),
						$oGoogleModule->getConfig('Secret')
					);
				}
			}
		}
	}

	public function onGetAccessToken($aArgs, &$mResult)
	{
		if ($aArgs['Service'] === $this->sService && isset($aArgs['Account']))
		{
			$mResult = false;
			$oAccount = $aArgs['Account'];
			$oTokenData = \json_decode($oAccount->AccessToken);
			if ($oTokenData)
			{
				$iCreated = (int) $oTokenData->created;
				$iExpiresIn = (int) $oTokenData->expires_in;
				if (time() > ($iCreated + $iExpiresIn) && isset($oAccount->RefreshToken))
				{
					$oGoogleModule = \Aurora\System\Api::GetModule('Google');
					if ($oGoogleModule)
					{
						$oConnector = new Classes\Connector($this);
						$aResult = $oConnector->RefreshAccessToken(
							$oGoogleModule->getConfig('Id'),
							$oGoogleModule->getConfig('Secret'),
							$oAccount->RefreshToken
						);
						if (isset($aResult['access_token']))
						{
							$oTokenData->access_token = $aResult['access_token'];
							$oTokenData->created = time();
							$oTokenData->expires_in = $aResult['expires_in'];

							$mResult = $oTokenData->access_token;

							$oAccount->AccessToken = \json_encode($oTokenData);
							$oAccount->Save();
						}
					}
				}
				else
				{
					$mResult = $oTokenData->access_token;
				}
			}

			return true;
		}
	}
}
