import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';

/**
 * Tab added to the community Customer form. The inputs are Customer custom columns,
 * so they are saved together with the customer.
 */
const TabCustomerHlthcrData = () => <div className='grid grid-cols-1 md:grid-cols-2 gap-2'>
  <Input field='id_hlthcr_client_type' />
  <Input field='hlthcr_employee_count' />
  <Input field='hlthcr_audit_interval_months' />
</div>;

export default TabCustomerHlthcrData;
