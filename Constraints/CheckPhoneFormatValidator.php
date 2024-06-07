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

use Exception;
use ForcePhone\ForcePhone;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\CountryQuery;

class CheckPhoneFormatValidator extends ConstraintValidator
{
    /**
     * Checks if a phone format corresponds on country
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $data = $this->context->getRoot()->getData();

        if (!empty($value)) {
            try {
                if (empty($data['country'])) {
                    throw new RuntimeException('No country ID for checking phone format');
                }

                $country = CountryQuery::create()->findOneById($data['country']);

                if (!$country) {
                    throw new RuntimeException('Country not found for checking phone format');
                }

                $phoneUtil = PhoneNumberUtil::getInstance();

                $phoneNumberProto = $phoneUtil->parse($value, $country->getIsoalpha2());

                $isValid = $phoneUtil->isValidNumber($phoneNumberProto);
            } catch (Exception $exception) {
                $isValid = false;

                Tlog::getInstance()->warning($exception->getMessage());
            }

            if (!$isValid) {
                $this->context->addViolation(
                    Translator::getInstance()->trans(
                        'Please enter a valid phone number',
                        [],
                        ForcePhone::DOMAIN_NAME
                    )
                );
            }
        }
    }
}
