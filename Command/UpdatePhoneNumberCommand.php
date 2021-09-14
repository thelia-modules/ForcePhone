<?php


namespace ForcePhone\Command;



use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Model\Base\AddressQuery;

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
                $address
                    ->setPhone($this->updatePhone($phone, $address->getCountry()->getIsoalpha2()))
                    ->save();
            }
            if ($cellphone = $address->getCellphone()){
                $address
                    ->setCellphone($this->updatePhone($cellphone, $address->getCountry()->getIsoalpha2()))
                    ->save();
            }
        }

        $output->writeln('Success');
    }

    protected function updatePhone($phoneNumber, $isoalpha2)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumberProto = $phoneUtil->parse($phoneNumber, $isoalpha2);
        }catch (\Exception $e){
            return null;
        }

        if ($phoneUtil->isValidNumber($phoneNumberProto)) {
            return $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
        }
        return $phoneNumber;
    }
}