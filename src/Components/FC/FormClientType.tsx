import Translator from '@hubleto/react-ui/core/Translator';
import Form from '@hubleto/react-ui/components/fc/Form';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';

const componentName = 'FormClientType';
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrClients';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

const FormClientType = (props: FormProps) => <Form
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/ClientType'}
  urlSlug='hlthcr-clients/client-types'
  title={{field: 'name', sub: T.translate('Client type')}}
  tabs={{default: {content: () => <div className='grid grid-cols-1 gap-2'>
    <Input field='name' />
  </div>}}}
  {...props}
/>

export default FormClientType;
