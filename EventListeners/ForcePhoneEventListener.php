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
use ForcePhone\ForcePhone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ExecutionContextInterface;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Translation\Translator;

/**
 * Class ForcePhoneEventListener
 * @package ForcePhone\EventListeners
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ForcePhoneEventListener implements EventSubscriberInterface
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_customer_create' => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_address_update' => ['forcePhoneInput', 128],
            TheliaEvents::FORM_AFTER_BUILD . '.thelia_address_creation' => ['forcePhoneInput', 128]
        ];
    }

    public function forcePhoneInput(TheliaFormEvent $event)
    {
        if ($this->request->fromApi() === false) {
            if (ForcePhone::getConfigValue('force_one', false)) {
                $constraints = [
                    new AtLeastOnePhone(),
                ];
            } else {
                $constraints = [];
            }

            $forcePhone = ForcePhone::getConfigValue('force_phone', false);

            if (! empty($constraints) || $forcePhone) {
                $event->getForm()->getFormBuilder()
                    ->remove('phone')
                    ->add(
                        "phone",
                        "text",
                        [
                            "constraints" => $forcePhone ? [ new NotBlank() ] : $constraints,
                            "label"       => Translator::getInstance()->trans("Phone"),
                            "label_attr"  => [ "for" => "phone" ],
                            "required"    => $forcePhone,
                        ]
                    )
                ;
            }

            $forceCellPhone = ForcePhone::getConfigValue('force_cellphone', false);

            if (! empty($constraints) || $forceCellPhone) {
                $event->getForm()->getFormBuilder()
                    ->remove('cellphone')
                    ->add(
                        "cellphone",
                        "text",
                        [
                            "constraints" => $forceCellPhone ? [ new NotBlank() ] : $constraints,
                            "label"       => Translator::getInstance()->trans("Cellphone"),
                            "label_attr"  => [ "for" => "cellphone" ],
                            "required"    => $forceCellPhone,
                        ]
                    )
                ;
            }
        }
    }

    public function checkAtLeastOnePhoneNumberIsDefined($value, ExecutionContextInterface $context)
    {
        $data = $context->getRoot()->getData();

        if (empty($data["phone"]) && empty($data["cellphone"])) {
            $context->addViolationAt(
                "phone",
                Translator::getInstance()->trans("Please enter a home or mobile phone number")
            );
        }
    }
}
