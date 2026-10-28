import Table from '@hubleto/react-ui/components/fc/Table'
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces'
import FormClientType from './FormClientType'

const componentName = 'TableClientTypes'
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrClients'

const TableClientTypes = (props: TableProps) => <Table
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/ClientType'}
  baseUrlSlug='hlthcr-clients/client-types'
  renderForm={(table: TableMeta) => <FormClientType {...table.getDefaultFormProps()} />}
  {...props}
/>;

export default TableClientTypes;
