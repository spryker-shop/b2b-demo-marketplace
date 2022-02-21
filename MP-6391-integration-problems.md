#General problems

Comments like `SomeEntity[]` should be replaced by `array<SomeEntity>`

------------------------------------------------------------------------------------------------------------------------

#MP-6392 Marketplace Merchant
##feature integration

2 ) `MerchantProfileCriteriaFilter` - `MerchantProfileCriteria`

4 ) missing ".php" in the name of file;

   "When you create a merchant using `MerchantFacade::updateMerchant()`, its profile also gets created." -  `MerchantFacade::createMerchant`;

   "When you deactivate a merchant in the Merchants section of the Back Office, its merchant users are deactivated in the Users section." - move to the next verification section; not working

5 ) Was already done.

6 ) Missing files' names

   `SearchElasticsearchConfig` changes should be moved higher than verification "Make sure that when merchant entities are created or updated through ORM, they are exported to Elastica accordingly."

6. and 7. can be combined in one

7 ) There is no `getMerchantUserFacade()` method in `src/Pyz/Zed/DataImport/Business/DataImportBusinessFactory.php`

###Install feature front end
2 ) Wrong filename, should be `data/import/common/common/glossary.csv`

Verification: not `spy_glossary table`, but `spy_glossary_key` and `spy_glossary_translation` tables

3 ) Verification can't be done, because `MerchantProduct` is not integrated yet

Probably, more detailed checkout guide is needed (I had to update company role, add this role to customer and relogin to be able to add products to cart)

4 ) Verification: wrong url

   duplicate verification?

##Glue API
3 ) `MerchantProductOffersRestApiConfig` can't be used before `MerchantProductOffer` integration

The following code can't be added to `GlueApplicationDependencyProvider::getResourceRelationshipPlugins()` before offers integration:

`$resourceRelationshipCollection->addRelationship(`

`MerchantProductOffersRestApiConfig::RESOURCE_PRODUCT_OFFERS,`

`new MerchantByMerchantReferenceResourceRelationshipPlugin()`

`);`

   "Make sure that by sending the request GET http://glue.mysprykershop.com/orders?include=merchant, merchant attributes are returned in response." - merchant offers not integrated yet, so can't order items from merchants.

------------------------------------------------------------------------------------------------------------------------

##Marketplace Merchant
4 ) requires MerchantPortal, that isn't integrated yet.

Verification: `config/Zed/navigation.xml` needs to be updated to see a new menu item in MP

    <merchant-portal-profile>

        <label>Profile</label>

        <title>Profile</title>

        <icon>profile</icon>

        <bundle>merchant-profile-merchant-portal-gui</bundle>

        <controller>profile</controller>

        <action>index</action>

    </merchant-portal-profile>

------------------------------------------------------------------------------------------------------------------------

#MP-6394 Marketplace Product

##Install feature core

4 ) Verification requires unnecessary additional coding. This can be verified later, by running data import.

5 ) Verification requires `Merchant Portal - Marketplace Product feature integration`, but it goes after current guide

Filename `src/Pyz/Client/ProductStorage/ProductStorageDependencyProvider.php` is wrong and should be replaced with `src/Pyz/Zed/ProductStorage/ProductStorageDependencyProvider.php`

If `spy_product_abstract_storage.merchant_reference` is null for all rows, run `cache:class-resolver:build` and then trigger storage update

##Install feature frontend

1 ) Class `SprykerShop\Yves\MerchantProductWidget\Widget\ProductSoldByMerchantWidget` doesn't exist, and the `SprykerShop\Yves\MerchantWidget\Widget\SoldByMerchantWidget` is already added.

2 ) Typo: `src/data/import/common/common/glossary.csv` should be `data/import/common/common/glossary.csv`

Verification: not `spy_glossary table`, but `spy_glossary_key` and `spy_glossary_translation` tables

3 ) Twig frontend changes are missing, see https://github.com/spryker/b2b-demo-shop-internal/commit/5a77aacb469a117b2b46cae948ae7c6f1ebe88ac

##Glue API

1 ) extra space after package name

##Marketplace Product + Cart

2 ) wrong filename, should be `src/Pyz/Zed/Cart/CartDependencyProvider.php`

Verification requires writing additional code for direct CartFacade call

##Other

`Marketplace Product + Marketplace Product Offer feature integration` guide should be moved to (or duplicated in) Marketplace Product Offer guide

Same with `Marketplace Product + Inventory Management feature integration`, `Merchant Portal - Marketplace Product feature integration` and `Merchant Portal - Marketplace Product + Tax feature integration`

------------------------------------------------------------------------------------------------------------------------

#MP-6393 Marketplace Product Offer

##Install feature core

4 ) Verification probably should be done after data import.

5 ) Import types should be added to full_EU.yml

6 ) Following verification steps are unclear and may require additional coding:

`Make sure that a default product offer is given when retrieving product concrete data.` (Possible solution is `Make sure that a default product offer is selected in Product Detail Page at first load`)

`Make sure that validity data is saved when saving a product offer.`

Offer edit page doesn't exist in backoffice, probably should be replaced with 'view page'.

##Install feature front end

2 ) wrong filename, should be `data/import/common/common/glossary.csv`

3 ) Offers widget can't be displayed properly without prices integration

##Marketplace Product + Marketplace Product Offer

Verification is unclear and probably can't be done before prices integration.

##Marketplace Product Offer + Cart

Feature core: Verification can't be done before prices integration.

Feature front end: wrong filename, should be `src/Pyz/Yves/CartPage/CartPageDependencyProvider.php`

##Marketplace Product Offer + Checkout

1 ) Install is already done.

------------------------------------------------------------------------------------------------------------------------

#MP-6397 Marketplace Product Offer Prices

6 ) In `src/Pyz/Service/PriceProduct/PriceProductDependencyProvider.php`: second use should be `use Spryker\Service\PriceProductOffer\Plugin\PriceProduct\PriceProductOfferPriceProductFilterPlugin;`

In `src/Pyz/Client/PriceProductOffer/PriceProductOfferDependencyProvider.php`: wrong filename, should be `src/Pyz/Zed/PriceProductOffer/PriceProductOfferDependencyProvider.php`

In `src/Pyz/Client/MerchantProductOfferStorage/MerchantProductOfferStorageDependencyProvider.php` additional use is needed:

`use Spryker\Client\MerchantProductOfferStorageExtension\Dependency\Plugin\ProductOfferStorageCollectionSorterPluginInterface;`

Some file changes are missing from guide, see https://github.com/spryker/b2b-demo-shop-internal/commit/54f0472dd10b77c0421369374accbb164ff37a1c

##Glue API: Marketplace Product Offer Prices

1 ) Old package version (also conflicts with requirements for `spryker/merchant-product-offer-storage`)

##Glue API: Marketplace Product Offer Volume Prices

Prerequisites: missing integration guide for Marketplace Product Offer Volume Prices

1 ) Wrong package name, should be `spryker/price-product-offer-volumes-rest-api`; wrong version

------------------------------------------------------------------------------------------------------------------------

#MP-6399 Marketplace Shipment

2 ) and 3 ) should go in the opposite order

2 ) Verification requires shipments with merchant_reference set, but the corresponding plugin isn't integrated yet (Marketplace Shipment + Cart)

##Marketplace Shipment + Customer

typo: `ssrc` instead of `src`

------------------------------------------------------------------------------------------------------------------------

#MP-6400 Marketplace promotions and discounts

5 ) wrong use, should be `Spryker\Zed\DiscountMerchantSalesOrder\Communication\Plugin\MerchantSalesOrder\DiscountMerchantOrderFilterPlugin;`

may require `console router:cache:warm-up` to start work

------------------------------------------------------------------------------------------------------------------------

#MP-6401 Marketplace Product Options

Feature walkthrough article doesn't have a link to integration guide

------------------------------------------------------------------------------------------------------------------------

#MP-6402 Marketplace Cart

Feature walkthrough article: broken formatting

------------------------------------------------------------------------------------------------------------------------

#MP-6404 Persistence ACL

1 ) typo: extra space, should be `spryker-feature/acl:"202108.0"`, not `spryker-feature/acl: "202108.0"`

2 ) AclEntityRule mentioned twice.

4 ) Files missing `namespace` and `use` parts

5 ) Verification requires additional coding

User transfer doesn't contain ACL groups (the corresponding plugin executed in other methods, than getting UserTransfer.
It can be checked on User Edit page in Backoffice).

------------------------------------------------------------------------------------------------------------------------

#MP-6407 Marketplace Merchant Portal Core
##Install feature core

2 ) If `src/Pyz/Zed/Security/SecurityDependencyProvider.php` already has UserSecurityPlugin, MerchantUserSecurityPlugin should go before it.

"Enable Merchant Portal infrastructural plugins." - wrong filename after, should be `src/Pyz/Zed/MerchantPortalApplication/MerchantPortalApplicationDependencyProvider.php`

config_default.php already has $config[AclConstants::ACL_DEFAULT_RULES], so adding this code at the end of file would break backoffice. Should be `$config[AclConstants::ACL_DEFAULT_RULES][] = [` instead

3 ) `MerchantDashboardCard` and `MerchantDashboardActionButton` - require dashboard-merchant-portal-gui, that is added in the next step

##Install feature front end

all `wget` URLs don't work, "202108.0" should be replaced with "master"

requires specific npm (<7) version (higher versions have problems with workspace)

and Node (<15) version (higher versions result in `function remove_cv_t() not found` error)

3 ) verification can't be done, because MP is not up yet

4 ) `wget -O .yarn/plugins/@yarnpkg/plugin-interactive-tools.cjs https://raw.githubusercontent.com/spryker-shop/suite/master/.yarn/plugins/%40yarnpkg/plugin-interactive-tools.cjs`

If you're getting `Missing write access to node_modules/mp-profile`, delete this **file** and make a **folder** with the same name

5 ) `test-setup.js` and `webpack.config.js` should both be `.ts`

6 ) `yves-isntall-dependencies and yves-isntall-dependencies` - should be `yves-install-dependencies and zed-isntall-dependencies`

file `docker.yml` should be specified

Also add this to `src/Pyz/Zed/Console/ConsoleDependencyProvider.php::getConsoleCommands()`:

`new MerchantPortalInstallDependenciesConsole(),`

`new MerchantPortalBuildFrontendConsole(),`

In case of problems with `fsevents` package, install it manually:
`npm install --force fsevents`

`rm -rf node_modules && yarn cache clean --all && npm cache clean --force && yarn install && yarn mp:build`

If frontend build is successful, but MP is not working and browser console has errors, you have to check that there is no `node_modules` folders except the one in the project root, and rebuild frontend.

##Adjust environment infrastructure

AclConfig file missing `RULE_TYPE_DENY` definition:

`protected const RULE_TYPE_DENY = 'deny';`

##Missed from guide

Some file updates needed for MP to work are missing, see https://github.com/spryker/b2b-demo-shop-internal/commit/334306a43055c74c5c0effc82632a3a8fc20dd7f
and https://github.com/spryker/b2b-demo-shop-internal/commit/2d192c7e43cd4e51b136bfc8259e9da8558c73ea

------------------------------------------------------------------------------------------------------------------------

#MP-6396 Marketplace Inventory Management

1 ) extra space before package version

5 ) wrong filenames

Typo: `Warehouses` instead of `Wherehouses`

If offer page doesn't work, check, if there is stock data for it and if the corresponding warehouse is connected to the store.

Verification can't be done, because offer page in zed doesn't open before `Marketplace Inventory Management + Order Management` feature integration

##Marketplace Inventory Management + Packaging Units

Verification: wrong table name, should be `spy_oms_product_offer_reservation`

Creating a product with both offer and packaging unit may require a guide because it is a non-trivial task.

##Other

`Marketplace Product + Inventory Management` guide may be unnecessary here, because it was already mentioned in
`Marketplace Product` and also because it doesn't require **Marketplace** `Inventory Managament`, only usual one.

-------------------------------------------------------------
#MP-6406 Merchant Opening Hours feature
##feature integration issues
1. Duplicated `console transfer:generate`.
2. Missing `console propel:install`.
3. Outdated demodata for merchant-opening-hours-date-schedule (Dates are in the past, schedule not displayed on yves, year should be updated).
4. Missing glossary translations for date schedule, should be copy-pasted from latest suite-nonsplit.
5. spryker/merchant-opening-hours-rest-api - should be ^1.0.0, instead of ^0.1.0.
6. MerchantOpeningHoursWeekdayScheduleWritePublisherPlugin and MerchantOpeningHoursWeekdayScheduleWritePublisherPlugin not mentioned in IG. (And not used in CORE, but should)
-------------------------------------------------------------
#MP-6405 Merchant Category
##feature integration issues
1. Specific modules mentioned in composer require step, instead of feature.
2. Zed should be replaced by `Backoffice`.
3. Typo in merchant_category.csv example.
4. CategoryWritePublisherPlugin missing in the plugins table.
#glue integration issues
1. 0.x version mentioned in the composer require step, instead of stable one.
2. In the example `http://glue.mysprykershop.com/merchants?categoryKeys[]={{some-category-key}}` - parameter categoryKeys should be category-keys.
-------------------------------------------------------------

#MP-6395 Merchant Order Management feature
##feature integration issues
1. Typo in data/import/common/common/marketplace/merchant_oms_process.csv example on the first line
2. Missing code sample for abstract class, but it's used in the other code samples https://github.com/spryker/suite-nonsplit/blob/master/src/Pyz/Zed/MerchantOms/Communication/Plugin/Oms/AbstractTriggerOmsEventCommandPlugin.php
3. Container is not used in src/Pyz/Zed/MerchantOms/MerchantOmsDependencyProvider.php, but registered in `use` section.
4. Typo (** at the end) in src/Pyz/Zed/Shipment/ShipmentDependencyProvider.php code sample name.
5. This IG is not related to Marketplace Order Management at all, but have it in the name https://docs.spryker.com/docs/marketplace/dev/feature-integration-guides/202108.0/marketplace-order-management-customer-account-management-feature-integration.html
6. Import types should be added to full_EU.yml
7. https://docs.spryker.com/docs/marketplace/dev/feature-integration-guides/202108.0/marketplace-dummy-payment-feature-integration.html is not mentioned in the dependencies, but without it is not possible to test the feature.

## Marketplace Dummy Payment
1. Invalid file name Pyz\Zed\Payment\PaymentDependencyProvider.php=>src/Pyz/Zed/Payment/PaymentDependencyProvider.php
2. Typo in data/import/payment_method.csv on the first line
3. Invalid file name Pyz\Yves\CheckoutPage\CheckoutPageDependencyProvider.php=>src/Pyz/Yves/CheckoutPage/CheckoutPageDependencyProvider.php
4. Code sample for CheckoutPageDependencyProvider.php is invalid, call of provided methods in provideDependencies() is missing.
5. MerchantSalesOrderDependencyProvider has no example of adding project level dependency to addSalesFacade.

## Marketplace Order Management + Order Threshold feature integration
1. Should be moved to standalone IG, because feature was introduced spryker-feature/marketplace-merchant-order-threshold
-------------------------------------------------------------
