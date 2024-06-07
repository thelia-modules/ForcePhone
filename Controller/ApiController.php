<?php


namespace ForcePhone\Controller;


use ForcePhone\ForcePhone;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use OpenApi\Controller\Front\BaseFrontOpenApiController;
use OpenApi\Service\OpenApiService;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Model\CountryQuery;
use Thelia\Model\OrderQuery;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/open_api')]
class ApiController extends BaseFrontOpenApiController
{
    /**
     * @Route("/check/order-phone/{orderId}", name="check_order_phone", methods="GET")
     *
     * @OA\Get(
     *     path="/check/order-phone/{orderId}",
     *     tags={"force_phone"},
     *     summary="Check if the phone numbers of an order is correct",
     *     @OA\Parameter(
     *          name="orderId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     * )
     * @param $orderId
     * @return JsonResponse
     * @throws NumberParseException
     * @throws PropelException
     */
    public function checkPhoneOrder($orderId): JsonResponse
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

    /**
     * @Route("/format-phone/{phone}/{countryId}", name="format_phone_number", methods="GET")
     *
     * @OA\Get(
     *     path="/format-phone/{phone}/{countryId}",
     *     tags={"force_phone"},
     *     summary="Format a phone number to international standard",
     *     @OA\Parameter(
     *          name="phone",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="countryId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     * )
     * @throws NumberParseException
     */
    public function reformatPhoneNumber($phone, $countryId): JsonResponse
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

        return OpenApiService::jsonResponse($phone);
    }
}