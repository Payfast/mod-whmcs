# payfast-common

## Payfast common class for modules

This is the Payfast common class for modules.

## Installation

You can install this module using composer:

```console
composer require payfast/payfast-common
```

## Module parameters for pfValidData()

Declare the relevant $moduleInfo values when using the **pfValidData()** method, for example:

```
$moduleInfo = [
    "pfSoftwareName"       => 'OpenCart',
    "pfSoftwareVer"        => '4.0.2.0',
    "pfSoftwareModuleName" => 'PF_OpenCart',
    "pfModuleVer"          => '2.3.1',
];

$pfValid = $payfastCommon->pfValidData($moduleInfo, $pfHost, $pfParamString);
```

### Debug Mode

Configure debug mode by passing true|false when instantiating the PayfastCommon class.

```
$payfastCommon = new PayfastCommon(true);
```

## Breaking Change since v1.1.0

We have migrated from static to instance methods.

For example, prior to v1.1.0 we used:

```
// Debug mode
define('PF_DEBUG', true);

// Module parameters for pfValidData
define('PF_SOFTWARE_NAME', 'GravityForms');
define('PF_SOFTWARE_VER', '2.8.7');
define('PF_MODULE_NAME', 'PayFast-GravityForms');
define('PF_MODULE_VER', '1.5.4');

// Calling methods on PayfastCommon
$pfData = PayfastCommon::pfGetData();
PayfastCommon::pflog('Verify data received');
```

But this has now become:

```
// Debug mode
$payfastCommon = new PayfastCommon(true);

// Module parameters for pfValidData
$moduleInfo = [
    "pfSoftwareName"       => 'GravityForms',
    "pfSoftwareVer"        => '2.8.7',
    "pfSoftwareModuleName" => 'PayFast-GravityForms',
    "pfModuleVer"          => '1.5.4',
];
$pfValid = $payfastCommon->pfValidData($moduleInfo, $pfHost, $pfParamString);

// Calling methods on PayfastCommon
$pfData = $payfastCommon->pfGetData();
$payfastCommon->pflog('Verify data received');
```
