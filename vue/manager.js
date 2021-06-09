import _ from 'lodash'

import eventBus from 'src/event-bus'
import modulesManager from 'src/modules-manager'

export default {
	name: 'GMailConnector',
	init (appData) {
		if (modulesManager.isModuleAvailable('MailWebclient')) {
			eventBus.$on('MailWebclient::GetOauthConnectorsData', params => {
				if (!_.isArray(params.oauthConnectorsData))
				{
					params.oauthConnectorsData = [];
				}
				params.oauthConnectorsData.push({
					name: 'Gmail',
					type: 'gmail',
					iconUrl: 'static/styles/images/modules/GMailConnector/logo_gmail.png'
				})
			})
		}
	},
}
