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

namespace ForcePhone\Controller;

use ForcePhone\ForcePhone;
use ForcePhone\Form\ConfigForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Thelia;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;
use Thelia\Tools\Version\Version;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/module/ForcePhone', name: 'forcephone_config')]
class ConfigureController extends BaseAdminController
{
    #[Route('/configure', name: '_save', methods: ['POST'])]
    public function configure(RequestStack $requestStack)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'forcephone', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(ConfigForm::getName());

        try {
            $form = $this->validateForm($configurationForm);

            // Get the form field values
            $data = $form->getData();

            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                ForcePhone::setConfigValue($name, $value);
            }

            // Log configuration modification
            $this->adminLogAppend(
                "forcephone.configuration.message",
                AccessManager::UPDATE,
                "ForcePhone configuration updated"
            );

            // Redirect to the success URL,
            if ($requestStack->getCurrentRequest()?->get('save_mode') === 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $url = '/admin/module/ForcePhone';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $url = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url));
        } catch (FormValidationException $ex) {
            $message = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $message = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("ForcePhone configuration", [], ForcePhone::DOMAIN_NAME),
            $message,
            $configurationForm,
            $ex
        );

        // Before 2.2, the errored form is not stored in session
        if (Version::test(Thelia::THELIA_VERSION, '2.2', false, "<")) {
            return $this->render('module-configure', [ 'module_code' => 'ForcePhone' ]);
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ForcePhone'));
    }
}
