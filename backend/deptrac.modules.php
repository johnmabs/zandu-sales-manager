<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\BoolConfig;
use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $salesContract = Layer::withName('SalesContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Sales\\Application\\Contract\\.*'
        ),
    );

    $inventoryContract = Layer::withName('InventoryContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Inventory\\Application\\Contract\\.*'
        ),
    );

    $cashManagementContract = Layer::withName('CashManagementContract')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\CashManagement\\Application\\Contract\\.*'
        ),
    );

    $sales = Layer::withName('Sales')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Sales\\.*'
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Sales\\Application\\Contract\\.*'
                ),
            ],
        ),
    );

    $inventory = Layer::withName('Inventory')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Inventory\\.*'
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\Inventory\\Application\\Contract\\.*'
                ),
            ],
        ),
    );

    $cashManagement = Layer::withName('CashManagement')->collectors(
        BoolConfig::create(
            must: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\CashManagement\\.*'
                ),
            ],
            mustNot: [
                ClassLikeConfig::create(
                    '.*Zandu\\Modules\\CashManagement\\Application\\Contract\\.*'
                ),
            ],
        ),
    );

    $identityAccess = Layer::withName('IdentityAccess')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\IdentityAccess\\.*'
        ),
    );

    $operations = Layer::withName('Operations')->collectors(
        ClassLikeConfig::create(
            '.*Zandu\\Modules\\Operations\\.*'
        ),
    );

    $config
        ->paths('./src')
        ->layers(
            $sales,
            $salesContract,
            $inventory,
            $inventoryContract,
            $cashManagement,
            $cashManagementContract,
            $identityAccess,
            $operations,
        )
        ->rulesets(
            Ruleset::forLayer($sales)
                ->accesses(
                    $inventoryContract,
                    $cashManagementContract,
                ),

            Ruleset::forLayer($inventory),

            Ruleset::forLayer($cashManagement),

            Ruleset::forLayer($salesContract),

            Ruleset::forLayer($inventoryContract),

            Ruleset::forLayer($cashManagementContract),

            Ruleset::forLayer($identityAccess),

            Ruleset::forLayer($operations),
        );
};
