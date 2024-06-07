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

namespace ForcePhone;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Module\BaseModule;

/**
 * Class ForcePhone
 * @package ForcePhone
 * @author Etienne Perriere <eperriere@openstudio.fr>
 */
class ForcePhone extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'forcephone';


    public function postActivation(ConnectionInterface $con = null): void
    {
        // Define default values
        if (null === self::getConfigValue('force_phone')) {
            self::setConfigValue('force_phone', 1);
        }
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire()
            ->autoconfigure();
    }
}
