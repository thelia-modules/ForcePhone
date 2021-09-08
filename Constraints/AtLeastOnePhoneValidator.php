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

namespace ForcePhone\Constraints;

use ForcePhone\ForcePhone;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Thelia\Core\Translation\Translator;

class AtLeastOnePhoneValidator extends ConstraintValidator
{
    /**
     * Checks if at least one phone number is provided
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        $data = $this->context->getRoot()->getData();

        if (empty($data["phone"]) && empty($data["cellphone"])) {
            $this->context->buildViolation(Translator::getInstance()->trans("Please enter at least one phone number.", [], ForcePhone::DOMAIN_NAME))
                ->atPath('phone')
                ->addViolation();
        }
    }
}
