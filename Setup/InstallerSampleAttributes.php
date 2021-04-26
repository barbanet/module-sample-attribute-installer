<?php
/**
 * Barbanet_SampleAttributeInstaller
 *
 * @copyright Copyright (c) 2021 Damián Culotta. (https://www.damianculotta.com.ar/)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare(strict_types=1);

namespace Barbanet\SampleAttributeInstaller\Setup;

use Barbanet\SampleAttributeInstaller\Model\Attribute;
use Magento\Framework\Setup;

class InstallerSampleAttributes implements Setup\SampleData\InstallerInterface
{
    /**
     * Setup class for product attributes
     *
     * @var Attribute
     */
    protected $attributeSetup;

    /**
     * @param Attribute $attributeSetup
     */
    public function __construct(
        Attribute $attributeSetup
    ) {
        $this->attributeSetup = $attributeSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->attributeSetup->install(['Barbanet_SampleAttributeInstaller::fixtures/sample_attributes.csv']);
    }
}
