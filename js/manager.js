'use strict';

module.exports = function (oAppData) {
	var
		_ = require('underscore'),

		App = require('%PathToCoreWebclientModule%/js/App.js'),

		bAdminUser = App.getUserRole() === Enums.UserRole.SuperAdmin
	;

	if (bAdminUser)
	{
		return {
			start: function (ModulesManager) {
				App.subscribeEvent('MailWebclient::GetOauthConnectorsData', _.bind(function (oParams) {
					if (!_.isArray(oParams.aOauthConnectorsData))
					{
						oParams.aOauthConnectorsData = [];
					}
					oParams.aOauthConnectorsData.push({
						Name: 'Gmail',
						Type: 'gmail',
						IconUrl: 'static/styles/images/modules/GMailConnector/logo_gmail.png'
					})
				}, this));
			}
		};
	}

	return null;
};
