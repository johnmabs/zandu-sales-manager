<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\BoolConfig;
use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $catalogContract = Layer::withName('CatalogContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Catalog\\Application\\Contract\\.*',
        ),
    );

    $catalog = Layer::withName('Catalog')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Catalog\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Catalog\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $pricingContract = Layer::withName('PricingContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Pricing\\Application\\Contract\\.*',
        ),
    );

    $pricing = Layer::withName('Pricing')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Pricing\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Pricing\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $salesContract = Layer::withName('SalesContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Sales\\Application\\Contract\\.*',
        ),
    );

    $inventoryContract = Layer::withName('InventoryContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Inventory\\Application\\Contract\\.*',
        ),
    );

    $inventoryCostingContract = Layer::withName('InventoryCostingContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\InventoryCosting\\Application\\Contract\\.*',
        ),
    );

    $cashManagementContract = Layer::withName('CashManagementContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\CashManagement\\Application\\Contract\\.*',
        ),
    );

    $purchasingContract = Layer::withName('PurchasingContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Purchasing\\Application\\Contract\\.*',
        ),
    );

    $sales = Layer::withName('Sales')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Sales\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Sales\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $inventory = Layer::withName('Inventory')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Inventory\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Inventory\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $inventoryCosting = Layer::withName('InventoryCosting')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\InventoryCosting\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\InventoryCosting\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $cashManagement = Layer::withName('CashManagement')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\CashManagement\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\CashManagement\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $purchasing = Layer::withName('Purchasing')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Purchasing\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Purchasing\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $payments = Layer::withName('Payments')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Payments\\.*',
        ),
    );

    $identityAccessContract = Layer::withName('IdentityAccessContract')->collectors(
        ClassLikeConfig::create('.*Zandu\\Modules\\IdentityAccess\\Application\\Contract\\.*'),
    );

    $identityAccess = Layer::withName('IdentityAccess')->collectors(
        BoolConfig::create(
            must: [ClassLikeConfig::create('.*Zandu\\Modules\\IdentityAccess\\.*')],
            mustNot: [ClassLikeConfig::create('.*Zandu\\Modules\\IdentityAccess\\Application\\Contract\\.*')],
        ),
    );

    $operations = Layer::withName('Operations')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Operations\\.*',
        ),
    );

    $organizationContract = Layer::withName('OrganizationContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Organization\\Application\\Contract\\.*',
        ),
    );

    $organization = Layer::withName('Organization')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Organization\\.*',
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Organization\\Application\\Contract\\.*',
                ),
            ],
        ),
    );

    $sharedKernel = Layer::withName('SharedKernel')->collectors(
        ClassLikeConfig::create('.*Zandu\\SharedKernel\\.*'),
    );

    $platform = Layer::withName('Platform')->collectors(
        ClassLikeConfig::create('.*Zandu\\Platform\\.*'),
    );

    $framework = Layer::withName('Framework')->collectors(
        ClassLikeConfig::create('.*(Doctrine|Symfony|ApiPlatform|Brick\\Math|OpenTelemetry|Monolog)\\.*'),
    );

    $config
        ->paths('./src')
        ->layers(
            $catalog,
            $catalogContract,
            $pricing,
            $pricingContract,
            $sales,
            $salesContract,
            $inventory,
            $inventoryContract,
            $inventoryCosting,
            $inventoryCostingContract,
            $cashManagement,
            $cashManagementContract,
            $purchasing,
            $purchasingContract,
            $payments,
            $identityAccess,
            $identityAccessContract,
            $operations,
            $organization,
            $organizationContract,
            $sharedKernel,
            $platform,
            $framework,
        )
        ->rulesets(
            Ruleset::forLayer($catalog)
                ->accesses(
                    $catalogContract,
                    $identityAccessContract,
                    $organizationContract,
                    $sharedKernel,
                    $platform,
                    $framework,
                ),
            Ruleset::forLayer($catalogContract)->accesses($sharedKernel),
            Ruleset::forLayer($pricing)
                ->accesses(
                    $pricingContract,
                    $catalogContract,
                    $identityAccessContract,
                    $organizationContract,
                    $sharedKernel,
                    $platform,
                    $framework,
                ),
            Ruleset::forLayer($pricingContract)->accesses($sharedKernel),
            Ruleset::forLayer($sales)
                ->accesses(
                    $salesContract,
                    $pricingContract,
                    $catalogContract,
                    $inventoryContract,
                    $inventoryCostingContract,
                    $cashManagementContract,
                    $identityAccessContract,
                    $organizationContract,
                    $sharedKernel,
                    $platform,
                    $framework,
                ),
            Ruleset::forLayer($inventory)
                ->accesses($catalogContract, $inventoryContract, $inventoryCostingContract, $identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($inventoryCosting)
                ->accesses($inventoryCostingContract, $inventoryContract, $identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($cashManagement)
                ->accesses($cashManagementContract, $identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($purchasing)
                ->accesses($purchasingContract, $catalogContract, $inventoryContract, $inventoryCostingContract, $identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($payments)
                ->accesses($salesContract, $cashManagementContract, $identityAccessContract, $organizationContract, $sharedKernel, $framework),
            Ruleset::forLayer($salesContract)->accesses($sharedKernel),
            Ruleset::forLayer($inventoryContract)->accesses($sharedKernel),
            Ruleset::forLayer($inventoryCostingContract)->accesses($sharedKernel),
            Ruleset::forLayer($cashManagementContract)->accesses($sharedKernel),
            Ruleset::forLayer($purchasingContract)->accesses($sharedKernel),
            Ruleset::forLayer($identityAccess)
                ->accesses($identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($identityAccessContract)->accesses($sharedKernel),
            Ruleset::forLayer($operations)->accesses($sharedKernel, $framework),
            Ruleset::forLayer($organization)
                ->accesses($identityAccessContract, $organizationContract, $sharedKernel, $platform, $framework),
            Ruleset::forLayer($organizationContract)->accesses($sharedKernel),
            Ruleset::forLayer($sharedKernel),
            Ruleset::forLayer($platform)->accesses($sharedKernel, $framework),
            Ruleset::forLayer($framework),
        );
};
