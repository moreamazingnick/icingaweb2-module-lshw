$computerSystem   = Get-CimInstance Win32_ComputerSystem;
$cpuData          = Get-CimInstance Win32_Processor;
$cpuCacheData     = Get-CimInstance Win32_CacheMemory;
$memoryData       = Get-CimInstance Win32_PhysicalMemory;
$audioDevices     = Get-CimInstance Win32_SoundDevice;
$networkAdapters  = Get-CimInstance Win32_NetworkAdapter;
$graphicsCards    = Get-CimInstance Win32_VideoController;
$mainboardData    = Get-WmiObject win32_baseboard;

$hardwareInfo = [PSCustomObject]@{
    id       = $computerSystem.Name;
    class    = "system";
    vendor   = $computerSystem.Manufacturer;
    product  = $computerSystem.Model;
    children = New-Object System.Collections.ArrayList;
};

$mainboard = [PSCustomObject]@{
    id          = "core";
    name        = $mainboardData.Name;
    description = "Motherboard";
    class       = "bus";
    vendor      = $mainboardData.Manufacturer;
    product     = $mainboardData.Product;
    children    = New-Object System.Collections.ArrayList;
};
$hardwareInfo.children.Add($mainboard) | Out-Null;

foreach ($cache in $cpuCacheData) {
    $curCache = [PSCustomObject]@{
        id          = "cache:" + ($cache.Level - 2);
        class       = "memory";
        description = "L" + ($cache.Level - 2) + " cache";
        size        = $cache.InstalledSize * 1024;
    };
    $mainboard.children.Add($curCache) | Out-Null;
};

$memory = [PSCustomObject]@{
    class       = "memory";
    id          = "memory";
    description = "System Memory";
    size        = $computerSystem.TotalPhysicalMemory;
    children    = New-Object System.Collections.ArrayList;
};

foreach ($mem in $memoryData) {
    $curMemory = [PSCustomObject]@{
        id          = $mem.BankLabel;
        class       = "memory";
        description = $mem.Name + "@" + $mem.Speed;
        size        = $mem.Capacity;
        vendor      = $mem.Manufacturer;
        product     = $mem.PartNumber;
        clock       = $mem.Speed;
    };
    $memory.children.Add($curMemory) | Out-Null;
};
$mainboard.children.Add($memory) | Out-Null;

$cpu = [PSCustomObject]@{
    id            = "cpu";
    class         = "processor";
    vendor        = $cpuData.Manufacturer;
    product       = $cpuData.Name;
    configuration = [PSCustomObject]@{
        cores   = $cpuData.NumberOfCores;
        threads = $cpuData.NumberOfLogicalProcessors;
    };
};
$mainboard.children.Add($cpu) | Out-Null;

$scsiControllerDevices = Get-CimInstance Win32_SCSIControllerDevice;
$controllerDisks       = New-Object System.Collections.ArrayList;

foreach ($scsiControllerDevice in $scsiControllerDevices) {
    $controllerDeviceId = $scsiControllerDevice.Antecedent.DeviceID;
    $scsiController     = Get-CimInstance Win32_SCSIController | Where-Object { $_.DeviceID -eq $controllerDeviceId };
    $curController      = [PSCustomObject]@{
        id       = $scsiController.Name;
        class    = "storage";
        children = New-Object System.Collections.ArrayList;
    };
    $mainboard.children.Add($curController) | Out-Null;

    $diskDrives = Get-CimInstance Win32_DiskDrive | Where-Object { $_.PNPDeviceID -eq $scsiControllerDevice.Dependent.DeviceID };
    foreach ($diskDrive in $diskDrives) {
        $Id    = $diskDrive.PNPDeviceID.ToLower() -replace '\\', '#';
        $disk  = Get-Disk | Where-Object { $_.Path -like "*$Id*" };
        $controllerDisks.Add($disk.Path) | Out-Null;
        $curDisk = [PSCustomObject]@{
            id       = "disk";
            class    = "disk";
            product  = $disk.FriendlyName;
            size     = $disk.Size;
            children = New-Object System.Collections.ArrayList;
        };
        $curController.children.Add($curDisk) | Out-Null;

        try { 
            $partitions = (Get-Partition -DiskNumber $disk.DiskNumber -ErrorAction SilentlyContinue);
        }
        catch { 
            $partitions = @();
        };
        
        if ($partitions.count -gt 0) {
            foreach ($part in $partitions) {
                $mountpath = "";
                if ($part.Accesspaths.Count -gt 0) {
                    $mountpath = $part.Accesspaths[0];
                };

                $curPart = [PSCustomObject]@{
                    id          = "volume" + $part.PartitionNumber;
                    class       = "volume";
                    description = $mountpath;
                    size        = $part.Size;
                };
                $curDisk.children.Add($curPart) | Out-Null;
            };
        };
    };
};

$otherDisks = Get-Disk | Where-Object { $_.Path -notin $controllerDisks };
$curController = [PSCustomObject]@{
    id       = "other disks";
    class    = "storage";
    children = New-Object System.Collections.ArrayList;
};
$mainboard.children.Add($curController) | Out-Null;

foreach ($disk in $otherDisks) {
    $curDisk = [PSCustomObject]@{
        id       = "disk";
        class    = "disk";
        product  = $disk.FriendlyName;
        size     = $disk.Size;
        children = New-Object System.Collections.ArrayList;
    };
    $curController.children.Add($curDisk) | Out-Null;

    try { 
        $partitions = (Get-Partition -DiskNumber $disk.DiskNumber -ErrorAction SilentlyContinue);
    }
    catch { 
        $partitions = @();
    };

    if ($partitions.count -gt 0) {
        foreach ($part in $partitions) {
            $mountpath = "";
            if ($part.Accesspaths.Count -gt 0) {
                $mountpath = $part.Accesspaths[0];
            };

            $curPart = [PSCustomObject]@{
                id          = "volume" + $part.PartitionNumber;
                class       = "volume";
                description = $mountpath;
                size        = $part.Size;
            };
            $curDisk.children.Add($curPart) | Out-Null;
        };
    };
};

foreach ($audioDevice in $audioDevices) {
    $audio = [PSCustomObject]@{
        class       = "multimedia";
        id          = "multimedia";
        description = "Audio Device";
        product     = $audioDevice.Name;
        vendor      = $audioDevice.Manufacturer;
        children    = New-Object System.Collections.ArrayList;
    };
    $mainboard.children.Add($audio) | Out-Null;
};

foreach ($networkAdapter in $networkAdapters) {
    $mac = "";
    if ($networkAdapter.MACAddress -ne $null) {
        $mac = $networkAdapter.MACAddress;
    };
    $network = [PSCustomObject]@{
        class       = "network";
        id          = "network";
        description = $networkAdapter.Description;
        product     = $networkAdapter.Name;
        vendor      = $networkAdapter.Manufacturer;
        serial      = $mac;
    };
    $mainboard.children.Add($network) | Out-Null;
};

foreach ($graphicsCard in $graphicsCards) {
    $gpu = [PSCustomObject]@{
        class       = "display";
        id          = "display";
        description = $graphicsCard.Description;
        product     = $graphicsCard.Name;
        vendor      = "";
    };
    $mainboard.children.Add($gpu) | Out-Null;
};

$usbControllerDevices = Get-CimInstance Win32_USBControllerDevice;
$usbControllers       = @{}; 

foreach ($association in $usbControllerDevices) {
    $usbDevice     = Get-CimInstance Win32_PnPEntity | Where-Object { $_.DeviceID -eq $association.Dependent.DeviceID };
    $usbController = Get-CimInstance Win32_PnPEntity | Where-Object { $_.DeviceID -eq $association.Antecedent.DeviceID };
    $counter       = 0;

    if ($usbControllers.ContainsKey($association.Antecedent.DeviceID)) {
        $curDevice = [PSCustomObject]@{
            id          = $usbDevice.FriendlyName;
            class       = "usb";
            description = $usbDevice.Description;
        };

        $usbControllers[$association.Antecedent.DeviceID].USBController.children.Add($curDevice) | Out-Null;
    }
    else {
        $usbControllers[$association.Antecedent.DeviceID] = @{
            'USBController' = [PSCustomObject]@{
                'class'       = "bus";
                'id'          = "usb:$counter";
                'product'     = $usbController.FriendlyName;
                'description' = $usbController.Description;
                'children'    = New-Object System.Collections.ArrayList;
            };
        };
        $counter += 1;
        
        $curDevice = [PSCustomObject]@{
            id          = $usbDevice.FriendlyName;
            class       = "usb";
            description = $usbDevice.Description;
        };

        $usbControllers[$association.Antecedent.DeviceID].USBController.children.Add($curDevice) | Out-Null;
    };
};

foreach ($key in $usbControllers.Keys) {
    $mainboard.children.Add($usbControllers[$key].USBController) | Out-Null;
};

$jsonOutput = $hardwareInfo | ConvertTo-Json -Depth 34;
Write-Output "[$jsonOutput]";