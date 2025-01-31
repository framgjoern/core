<?php

namespace Stu\Module\ShipModule;

use Stu\Component\Ship\ShipModuleTypeEnum;

class ModuleTypeDescriptionMapper
{

    static function getDescription($type): string
    {
        switch ($type) {
            case ShipModuleTypeEnum::MODULE_TYPE_HULL:
                return _("Hülle");
            case ShipModuleTypeEnum::MODULE_TYPE_SHIELDS:
                return _("Schilde");
            case ShipModuleTypeEnum::MODULE_TYPE_EPS:
                return _("EPS-Leitungen");
            case ShipModuleTypeEnum::MODULE_TYPE_IMPULSEDRIVE:
                return _("Antrieb");
            case ShipModuleTypeEnum::MODULE_TYPE_WARPCORE:
                return _("Reaktor");
            case ShipModuleTypeEnum::MODULE_TYPE_COMPUTER:
                return _("Computer");
            case ShipModuleTypeEnum::MODULE_TYPE_PHASER:
                return _("Energiewaffe");
            case ShipModuleTypeEnum::MODULE_TYPE_TORPEDO:
                return _("Torpedobank");
            case ShipModuleTypeEnum::MODULE_TYPE_SPECIAL:
                return _("Spezial");
        }
        return '';
    }
}
