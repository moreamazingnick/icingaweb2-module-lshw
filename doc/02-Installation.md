# Installation <a id="module-lshw-installation"></a>

## Requirements <a id="module-lshw-installation-requirements"></a>

* Icinga Web 2 (&gt;= 2.10.3)
* Icinga Director (&gt;= 1.9.1)
* PHP (&gt;= 7.3)
* php-json

The Icinga Web 2 `monitoring` or `icingadb` module needs to be configured and enabled.

## Installation from .tar.gz <a id="module-lshw-installation-manual"></a>

Download the latest version and extract it to a folder named `lshw`
in one of your Icinga Web 2 module path directories.

## Enable the newly installed module <a id="module-lshw-installation-enable"></a>

Enable the `lshw` module either on the CLI by running

```sh
icingacli module enable lshw
```

Or go to your Icinga Web 2 frontend, choose `Configuration` -&gt; `Modules`, chose the `lshw` module and `enable` it.

It might afterward be necessary to refresh your web browser to be sure that
newly provided styling is loaded.