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

    $cashManagementContract = Layer::withName('CashManagementContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\CashManagement\\Application\\Contract\\.*',
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
            $cashManagement,
            $cashManagementContract,
            $identityAccess,
            $identityAccessContract,
            $operations,
            $organization,
            $organizationContract,
        )
        ->rulesets(
            Ruleset::forLayer($catalog)
                ->accesses(
                    $catalogContract,
                    $identityAccessContract,
                    $organizationContract,
                ),
            Ruleset::forLayer($catalogContract),
            Ruleset::forLayer($pricing)
                ->accesses(
                    $pricingContract,
                    $catalogContract,
                    $identityAccessContract,
                    $organizationContract,
                ),
            Ruleset::forLayer($pricingContract),
            Ruleset::forLayer($sales)
                ->accesses(
                    $inventoryContract,
                    $cashManagementContract,
                ),
            Ruleset::forLayer($inventory)
                ->accesses($catalogContract, $inventoryContract, $organizationContract),
            Ruleset::forLayer($cashManagement)
                ->accesses($organizationContract),
            Ruleset::forLayer($salesContract),
            Ruleset::forLayer($inventoryContract),
            Ruleset::forLayer($cashManagementContract),
            Ruleset::forLayer($identityAccess)
                ->accesses($identityAccessContract, $organizationContract),
            Ruleset::forLayer($identityAccessContract),
            Ruleset::forLayer($operations),
            Ruleset::forLayer($organization)
                ->accesses($identityAccessContract, $organizationContract),
            Ruleset::forLayer($organizationContract),
        );
};
