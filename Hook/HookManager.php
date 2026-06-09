<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace ForcePhone\Hook;

use ForcePhone\Form\ConfigForm;
use ForcePhone\ForcePhone;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

class HookManager extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public function onModuleConfigure(HookRenderEvent $event): void
    {
        $data = [
            'force_phone'     => (bool) ForcePhone::getConfigValue('force_phone', false),
            'force_cellphone' => (bool) ForcePhone::getConfigValue('force_cellphone', false),
            'force_one'       => (bool) ForcePhone::getConfigValue('force_one', false),
            'validate_format' => (bool) ForcePhone::getConfigValue('validate_format', false),
        ];

        $form = $this->formFactory->createForm(ConfigForm::getName(), data: $data);

        $event->add(
            $this->render('ForcePhone/module-configuration.html.twig', ['form' => $form->createView()->getView()])
        );
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                [
                    'type'   => 'back',
                    'method' => 'onModuleConfigure',
                ],
            ],
        ];
    }
}
