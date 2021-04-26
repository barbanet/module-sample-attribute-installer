# Barbanet_SampleAttributeInstaller for Magento2

Create and edit Product attributes using CSV files.

## Installation

Use [composer](https://getcomposer.org/) to install Barbanet_SampleAttributeInstaller.

```
composer require barbanet/module-sample-attribute-installer
```

Then you'll need to activate the module.

```
bin/magento module:enable Barbanet_SampleAttributeInstaller
bin/magento setup:upgrade
bin/magento cache:clean
```

## Uninstall

```
bin/magento module:uninstall Barbanet_SampleAttributeInstaller
```

If you used Composer for installation Magento will remove the files.

## License
[GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html)