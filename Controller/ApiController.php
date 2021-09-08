<?php


namespace ForcePhone\Controller;


use ForcePhone\ForcePhone;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\CountryQuery;
use Thelia\Model\OrderQuery;


class ApiController extends BaseFrontController
{
    public function checkPhoneOrder($orderId)
    {
        $order = OrderQuery::create()->filterById($orderId)->findOne();

        if (null === $order) {
            return new JsonResponse('Order not found', 400);
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        $address = $order->getOrderAddressRelatedByDeliveryOrderAddressId();

        if (!empty($address->getPhone())) {

            $phoneNumberProto = $phoneUtil->parse($address->getPhone(), $address->getCountry()->getIsoalpha2());

            $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

            if (!$isValid) {
                return new JsonResponse('Wrong phone number', 400);
            }
        }

        if (!empty($address->getCellphone())) {

            $phoneNumberProto = $phoneUtil->parse($address->getCellphone(), $address->getCountry()->getIsoalpha2());

            $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

            if (!$isValid) {
                return new JsonResponse('Wrong cellphone number', 400);
            }
        }

        if (
            ForcePhone::getConfigValue('force_one', false) &&
            empty($address->getCellphone()) && empty($address->getPhone())
        ) {
            return new JsonResponse('No phone number found', 400);
        }

        return new JsonResponse('Success', 200);
    }

    public function reformatPhoneNumber($phone, $countryId)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $country = CountryQuery::create()->filterById($countryId)->findOne();

        if (!$country){
            return new JsonResponse('Invalid country id', 400);
        }

        $phoneNumberProto = $phoneUtil->parse($phone, $country->getIsoalpha2());

        $isValid = $phoneUtil->isValidNumber($phoneNumberProto);

        if (!$isValid) {
            return new JsonResponse('Invalid phone number', 400);
        }
        $phone = $phoneUtil->format($phoneNumberProto, PhoneNumberFormat::INTERNATIONAL);

        return new JsonResponse($phone, 200);
    }

}