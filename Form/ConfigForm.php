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

namespace ForcePhone\Form;

use ForcePhone\ForcePhone;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;

class ConfigForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'force_phone',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $this->translator->trans('Home phone number', [], ForcePhone::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'force_phone',
                    ]
                ]
            )
            ->add(
                'force_cellphone',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $this->translator->trans('Mobile phone number', [], ForcePhone::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'force_cellphone',
                    ]
                ]
            )
            ->add(
                'force_one',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $this->translator->trans('At least one phone number', [], ForcePhone::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'force_one',
                    ]
                ]
            )
            ->add(
                'validate_format',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $this->translator->trans('Check phone numbers format', [], ForcePhone::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'validate_format',
                    ]
                ]
            )
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName()
    {
        return 'forcephone_configuration';
    }
}
