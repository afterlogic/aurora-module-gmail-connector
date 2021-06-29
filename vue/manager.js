import _ from 'lodash'

import eventBus from 'src/event-bus'

export default {
  moduleName: 'GMailConnector',

  requiredModules: ['MailWebclient'],

  initSubscriptions (appData) {
    eventBus.$on('MailWebclient::GetOauthConnectorsData', params => {
      if (!_.isArray(params.oauthConnectorsData)) {
        params.oauthConnectorsData = []
      }
      params.oauthConnectorsData.push({
        name: 'Gmail',
        type: 'gmail',
        iconUrl: 'static/styles/images/modules/GMailConnector/logo_gmail.png'
      })
    })
  },
}
