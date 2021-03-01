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
		$this->subscribeEvent('GoogleAuthWebclient::PopulateScopes', array($this, 'onPopulateScopes'));

		$this->subscribeEvent('Google::GetSettings', array($this, 'onGetSettings'));
		$this->subscribeEvent('Google::UpdateSettings::after', array($this, 'onAfterUpdateSettings'));
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
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
	public function onGetSettings($aArgs, &$mResult)
	{
		$oUser = \Aurora\System\Api::getAuthenticatedUser();

		if (!empty($oUser))
		{
			$aScope = array(
				'Name' => 'mail',
				'Description' => $this->i18N('SCOPE_MAIL'),
				'Value' => false
			);
			if ($oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)
			{
				$aScope['Value'] = $this->issetScope('mail');
				$mResult['Scopes'][] = $aScope;
			}
			if ($oUser->isNormalOrTenant())
			{
				if ($aArgs['OAuthAccount'] instanceof \Aurora\Modules\OAuthIntegratorWebclient\Classes\Account)
				{
					$aScope['Value'] = $aArgs['OAuthAccount']->issetScope('mail');
				}
				if ($this->issetScope('mail'))
				{
					$mResult['Scopes'][] = $aScope;
				}
			}
		}
	}

	public function onAfterUpdateSettings($aArgs, &$mResult)
	{
		$sScope = '';
		if (isset($aArgs['Scopes']) && is_array($aArgs['Scopes']))
		{
			foreach($aArgs['Scopes'] as $aScope)
			{
				if ($aScope['Name'] === 'mail')
				{
					if ($aScope['Value'])
					{
						$sScope = 'mail';
						break;
					}
				}
			}
		}
		$this->setConfig('Scopes', $sScope);
		$this->saveModuleConfig();
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
		if ($aArgs['Service'] === 'gmail')
		{
			$aArgs['Service'] = 'google';
			$this->broadcastEvent(
				'RevokeAccessToken',
				$aArgs,
				$mResult
			);
			$mResult = false;
			setcookie('oauth-redirect', 'connect');
			\Aurora\System\Api::Location2('./?oauth=google');
			return true;
		}
	}
}
