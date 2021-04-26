<?php
/**
 * Barbanet_SampleAttributeInstaller
 *
 * @copyright Copyright (c) 2021 Damián Culotta. (https://www.damianculotta.com.ar/)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Barbanet_SampleAttributeInstaller',
    __DIR__
);
