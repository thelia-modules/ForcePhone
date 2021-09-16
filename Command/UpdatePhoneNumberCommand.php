<?php


namespace ForcePhone\Command;



use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Model\Base\AddressQuery;
use Thelia\Log\Tlog;
use Thelia\Model\Customer;

class UpdatePhoneNumberCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("module:ForcePhone:update")
            ->setDescription("Update phone number of all addresses");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $addresses = AddressQuery::create()->find();

        foreach ($addresses as $address){
            if ($phone = $address->getPhone()){
                $phone = $this->updatePhone($phone, $address->getCountry()->getIsoalpha2(), $address->getCustomer(), $output);
                if ($phone !== null){
                    $address
                        ->setPhone($phone)
                        ->save();
                }
            }
            if ($cellphone = $address->getCellphone()){
                $cellphone = $this->updatePhone($cellphone, $address->getCountry()->getIsoalpha2(), $address->getCustomer(), $output);
                if ($cellphone !== null) {
                    $address
                        ->setCellphone($cellphone)
                        ->save();
                }
            }
        }

        $output->writeln('Success');

        return 0;
    }

    protected function updatePhone($phoneNumber, $isoalpha2, Customer $customer, OutputInterface $output)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumberProto = $phoneUtil->parse($phoneNumber, $isoalpha2);
        }catch (\Exception $e){
            Tlog::getInstance()->error('Phone number '.$phoneNumber.' for customer '.$customer->getRef().' is invalid');
            $output->writeln('Error : Phone number '.$phoneNumber.' for customer '.$customer->getRef().' is invalid');
            return null;
        }

        if ($phoneUtil->isValidNumber($phoneNumberProto)) {
            return $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
        }
        return $phoneNumber;
    }
}