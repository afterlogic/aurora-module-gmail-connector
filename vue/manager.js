import _ from 'lodash'

import eventBus from 'src/event-bus'

const _getOauthConnectorsData = params => {
  if (!_.isArray(params.oauthConnectorsData)) {
    params.oauthConnectorsData = []
  }
  params.oauthConnectorsData.push({
    name: 'Gmail',
    type: 'gmail',
    iconUrl: 'static/styles/images/modules/GMailConnector/logo_gmail.png'
  })
}

export default {
  moduleName: 'GMailConnector',

  requiredModules: ['MailWebclient'],

  initSubscriptions (appData) {
    eventBus.$off('MailWebclient::GetOauthConnectorsData', _getOauthConnectorsData)
    eventBus.$on('MailWebclient::GetOauthConnectorsData', _getOauthConnectorsData)
  },
}
