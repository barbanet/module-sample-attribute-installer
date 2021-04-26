<?php
/**
 * Barbanet_SampleAttributeInstaller
 *
 * @copyright Copyright (c) 2021 Damián Culotta. (https://www.damianculotta.com.ar/)
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare(strict_types=1);

namespace Barbanet\SampleAttributeInstaller\Setup\Patch\Data;

use Barbanet\SampleAttributeInstaller\Setup\InstallerSampleAttributes;
use Magento\Framework\Setup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SampleData\Executor;

/**
 * Class InstallCatalogSampleData
 */
class InstallSampleAttributesData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var InstallerSampleAttributes
     */
    protected $installer;

    /**
     * @param Executor $executor
     * @param InstallerSampleAttributes $installer
     */
    public function __construct(
        Executor $executor,
        InstallerSampleAttributes $installer
    ) {
        $this->executor = $executor;
        $this->installer = $installer;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->executor->exec($this->installer);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
