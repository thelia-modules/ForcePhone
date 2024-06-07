<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */

/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */

namespace ForcePhone\EventListeners;

use Exception;
use ForcePhone\Constraints\AtLeastOnePhone;
use ForcePhone\Constraints\CheckPhoneFormat;
use ForcePhone\ForcePhone;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use OpenApi\Events\ModelValidationEvent;
use OpenApi\Model\Api\Address;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\CountryQuery;
use Thelia\Model\Event\AddressEvent;
use Thelia\Model\Event\CustomerEvent;

/**
 * Class ForcePhoneEventListener.
 *
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ForcePhoneEventListener implements EventSubscriberInterface
{
    protected ?Request $request;

    /**
     * ForcePhoneEventListener constructor.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_customer_create' => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_customer_update' => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_address_update' => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_address_creation' => ['forcePhoneInput', 128],
            CustomerEvent::POST_INSERT => ['customerPhoneUpdate', 125],
            CustomerEvent::POST_UPDATE => ['customerPhoneUpdate', 125],
            AddressEvent::PRE_INSERT => ['addressPhoneUpdate', 125],
            AddressEvent::PRE_UPDATE => ['addressPhoneUpdate', 125],
            ModelValidationEvent::MODEL_VALIDATION_EVENT_PREFIX.'address' => ['validateOpenApiAddress', 125],
        ];
    }

    public function forcePhoneInput(TheliaFormEvent $event): void
    {
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
                    TextType::class,
                    [
                        'constraints' => $forcePhone ? array_merge([new NotBlank()], $constraints) : $constraints,
                        'label' => Translator::getInstance()->trans('Phone'),
                        'label_attr' => ['for' => 'phone'],
                        'required' => $forcePhone,
                    ]
                );
        }

        $forceCellPhone = ForcePhone::getConfigValue('force_cellphone', false);

        if (!empty($constraints) || $forceCellPhone) {
            $event->getForm()->getFormBuilder()
                ->remove('cellphone')
                ->add(
                    'cellphone',
                    TextType::class,
                    [
                        'constraints' => $forceCellPhone ? array_merge([new NotBlank()], $constraints) : $constraints,
                        'label' => Translator::getInstance()->trans('Cellphone'),
                        'label_attr' => ['for' => 'cellphone'],
                        'required' => $forceCellPhone,
                    ]
                );
        }
    }

    public function addressPhoneUpdate(AddressEvent $addressEvent): void
    {
        $validateFormat = ForcePhone::getConfigValue('validate_format', false);

        if ($validateFormat) {
            $address = $addressEvent->getModel();

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
            } catch (Exception) {
                Tlog::getInstance()->warning('Error on update phone format');
            }
        }
    }

    public function customerPhoneUpdate(CustomerEvent $customerEvent): void
    {
        $validateFormat = ForcePhone::getConfigValue('validate_format', false);

        if ($validateFormat) {
            $address = $customerEvent->getModel()->getDefaultAddress();

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
            } catch (Exception) {
                Tlog::getInstance()->warning('Error on update phone format');
            }
        }
    }

    public function validateOpenApiAddress(ModelValidationEvent $event): void
    {
        if ($event->getGroups() === 'read') {
            return;
        }

        /** @var Address $address */
        $address = $event->getModel();
        $country = CountryQuery::create()->filterById($address->getCountryId())->findOne();
        $violations = $event->getViolations();

        $phoneUtil = PhoneNumberUtil::getInstance();

        if (!empty($address->getPhone())) {
            try {
                $phoneNumberProto = $phoneUtil->parse($address->getPhone(), $country?->getIsoalpha2());

                $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                if (!$isValid) {
                    throw new RuntimeException('Invalid phone number');
                }

                $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
                $address->setPhone($phone);
            } catch (Exception $exception) {
                $violations['phone'] = $event->getModelFactory()->buildModel('SchemaViolation', ['message' => $exception->getMessage()]);
            }
        }

        if (!empty($address->getCellphone())) {
            try {
                $phoneNumberProto = $phoneUtil->parse($address->getCellphone(), $country?->getIsoalpha2());

                $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

                if (!$isValid) {
                    throw new RuntimeException('Invalid cellphone number');
                }

                $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);
                $address->setCellphone($phone);
            } catch (Exception $exception) {
                $violations['cellphone'] = $event->getModelFactory()->buildModel('SchemaViolation', ['message' => $exception->getMessage()]);
            }
        }

        $event->setModel($address);
        $event->setViolations($violations);
    }
}
