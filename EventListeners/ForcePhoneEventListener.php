<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ForcePhone\EventListeners;

use ForcePhone\Constraints\AtLeastOnePhone;
use ForcePhone\Constraints\CheckPhoneFormat;
use ForcePhone\ForcePhone;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use OpenApi\Events\ModelValidationEvent;
use OpenApi\Model\Api\Address;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Event\Address\AddressEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\CountryQuery;
use function Complex\add;

/**
 * Class ForcePhoneEventListener
 * @package ForcePhone\EventListeners
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ForcePhoneEventListener implements EventSubscriberInterface
{
    protected $request;

    /**
     * ForcePhoneEventListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_customer_create'  => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_customer_update'  => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_address_update'   => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_address_creation' => ['forcePhoneInput', 128],
            TheliaEvents::AFTER_CREATECUSTOMER                          => ['customerPhoneUpdate', 125],
            TheliaEvents::AFTER_UPDATECUSTOMER                          => ['customerPhoneUpdate', 125],
            TheliaEvents::BEFORE_UPDATEADDRESS                          => ['addressPhoneUpdate', 125],
            TheliaEvents::BEFORE_CREATEADDRESS                          => ['addressPhoneUpdate', 125],
            'open_api_model_validation_address' => ['validateOpenApiAddress', 125]
        ];
    }

    /**
     * @param TheliaFormEvent $event
     */
    public function forcePhoneInput(TheliaFormEvent $event)
    {
        if ($this->request->fromApi() === false) {
            $constraints = [];

            if (ForcePhone::getConfigValue('force_one', false)) {
                $constraints[] = new AtLeastOnePhone();
            }

            $validateFormat = ForcePhone::getConfigValue('validate_format', false);

            if ($validateFormat) {
                $constraints[] = new CheckPhoneFormat();
            }

            $forcePhone = ForcePhone::getConfigValue('force_phone', false);

            if (!empty($constraints) || $forcePhone) {
                $event->getForm()->getFormBuilder()
                    ->remove('phone')
                    ->add(
                        'phone',
                        'text',
                        [
                            'constraints' => $forcePhone ? array_merge([new NotBlank()], $constraints) : $constraints,
                            'label'       => Translator::getInstance()->trans('Phone'),
                            'label_attr'  => ['for' => 'phone'],
                            'required'    => $forcePhone,
                        ]
                    );
            }

            $forceCellPhone = ForcePhone::getConfigValue('force_cellphone', false);

            if (!empty($constraints) || $forceCellPhone) {
                $event->getForm()->getFormBuilder()
                    ->remove('cellphone')
                    ->add(
                        'cellphone',
                        'text',
                        [
                            'constraints' => $forceCellPhone ? array_merge([new NotBlank()], $constraints) : $constraints,
                            'label'       => Translator::getInstance()->trans('Cellphone'),
                            'label_attr'  => ['for' => 'cellphone'],
                            'required'    => $forceCellPhone,
                        ]
                    );
            }
        }
    }

    /**
     * @param AddressEvent $addressEvent
     */
    public function addressPhoneUpdate(AddressEvent $addressEvent)
    {
        $validateFormat = ForcePhone::getConfigValue('validate_format', false);

        if ($this->request->fromApi() === false && $validateFormat) {
            $address = $addressEvent->getAddress();

            try {
                $phoneUtil = PhoneNumberUtil::getInstance();

                if (!empty($address->getPhone())) {
                    $phoneNumberProto = $phoneUtil->parse($address->getPhone(), $address->getCountry()->getIsoalpha2());

                    $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                    if ($isValid) {
                        $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);

                        $address->setPhone($phone);
                    }
                }

                if (!empty($address->getCellphone())) {
                    $phoneNumberProto = $phoneUtil->parse($address->getCellphone(), $address->getCountry()->getIsoalpha2());

                    $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                    if ($isValid) {
                        $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);

                        $address->setCellphone($phone);
                    }
                }

            } catch (\Exception $exception) {
                Tlog::getInstance()->warning('Error on update phone format');
            }
        }
    }

    /**
     * @param CustomerEvent $customerEvent
     */
    public function customerPhoneUpdate(CustomerEvent $customerEvent)
    {
        $validateFormat = ForcePhone::getConfigValue('validate_format', false);

        if ($this->request->fromApi() === false && $validateFormat) {
            $address = $customerEvent->getCustomer()->getDefaultAddress();

            try {
                $phoneUtil = PhoneNumberUtil::getInstance();

                if (!empty($address->getPhone())) {
                    $phoneNumberProto = $phoneUtil->parse($address->getPhone(), $address->getCountry()->getIsoalpha2());

                    $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                    if ($isValid) {
                        $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);

                        $address->setPhone($phone)->save();
                    }
                }

                if (!empty($address->getCellphone())) {
                    $phoneNumberProto = $phoneUtil->parse($address->getCellphone(), $address->getCountry()->getIsoalpha2());

                    $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                    if ($isValid) {
                        $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);

                        $address->setCellphone($phone)->save();
                    }
                }
            } catch (\Exception $exception) {
                Tlog::getInstance()->warning('Error on update phone format');
            }
        }
    }

    public function validateOpenApiAddress(ModelValidationEvent $event)
    {
        
        if ($event->getGroups() === 'read' ) {
            return;
        }

        /** @var Address $address */
        $address = $event->getModel();
        $country = CountryQuery::create()->filterById($address->getCountryId())->findOne();
        $violations = $event->getViolations();

        $phoneUtil = PhoneNumberUtil::getInstance();

        if (!empty($address->getPhone())) {
            try {
                $phoneNumberProto = $phoneUtil->parse($address->getPhone(), $country->getIsoalpha2());

                $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                if (!$isValid) {
                    throw new \Exception('Invalid phone number');
                }

                $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
                $address->setPhone($phone);
            }catch (\Exception $exception){
                $violations['phoneNumber'] = $event->getModelFactory()->buildModel('SchemaViolation', ['message' => $message]);
            }
        }

        if (!empty($address->getCellphone())) {
            try {
                $phoneNumberProto = $phoneUtil->parse($address->getCellphone(), $country->getIsoalpha2());

                $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                if (!$isValid) {
                    throw new \Exception('Invalid cellphone number');
                }

                $cellphone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
                $address->setCellphone($cellphone);
            }catch (\Exception $exception){
                $message =                     Translator::getInstance()->trans(
                    'Please enter a valid phone number',
                    [],
                    ForcePhone::DOMAIN_NAME
                );
                $violations['cellphoneNumber'] = $event->getModelFactory()->buildModel('SchemaViolation', ['message' => $message]);
            }
        }

        $event->setModel($address);

        $event->setViolations($violations);
    }
}
