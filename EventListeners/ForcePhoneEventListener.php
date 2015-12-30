<?php

namespace ForcePhone\EventListeners;

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
            $event->getForm()->getFormBuilder()
                ->remove('phone')
                ->add("phone", "text", array(
                    "label" => Translator::getInstance()->trans("Phone"),
                    "label_attr" => array(
                        "for" => "phone",
                    ),
                    "required" => true,
                ));
        }
    }
}