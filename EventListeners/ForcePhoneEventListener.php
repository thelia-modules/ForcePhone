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

use ForcePhone\ForcePhone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
            if (ForcePhone::getConfigValue('force_phone', false)) {
                $event->getForm()->getFormBuilder()
                    ->remove('phone')
                    ->add(
                        "phone",
                        "text",
                        [
                            "label"      => Translator::getInstance()->trans("Phone"),
                            "label_attr" => [ "for" => "phone" ],
                            "required"   => true,
                        ]
                    )
                ;
            }

            if (ForcePhone::getConfigValue('force_cellphone', false)) {
                $event->getForm()->getFormBuilder()
                    ->remove('cellphone')
                    ->add(
                        "cellphone",
                        "text",
                        [
                            "label"      => Translator::getInstance()->trans("Cellphone"),
                            "label_attr" => [ "for" => "cellphone" ],
                            "required"   => true,
                        ]
                    )
                ;
            }
        }
    }
}
