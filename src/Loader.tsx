import App from '@hubleto/react-ui/core/App'
import Translator from '@hubleto/react-ui/core/Translator';
import FormCustomizer from '@hubleto/react-ui/core/FormCustomizer';
import TableClientTypes from './Components/FC/TableClientTypes'
import TabCustomerHlthcrData from './Components/FC/TabCustomerHlthcrData'

const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrClients';
const T = new Translator(parentApp + '/Loader', 'Loader');

// Tab added to the customer form (community Customers app).
function mountClientDataTab() {
  return {title: T.translate('Occupational health'), content: () => <TabCustomerHlthcrData />};
}

class HlthcrClientsApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('HlthcrClientsTableClientTypes', TableClientTypes);
    FormCustomizer.addTab('FormCustomer', 'hlthcr-client-data', mountClientDataTab);
  }
}

globalThis.hubleto.registerApp(parentApp, new HlthcrClientsApp());
