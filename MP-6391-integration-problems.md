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

#MP-6399 Marketplace Shipment

2 ) and 3 ) should go in the opposite order

2 ) Verification requires shipments with merchant_reference set, but the corresponding plugin isn't integrated yet (Marketplace Shipment + Cart)

#MP-6401 Marketplace Product Options

Feature walkthrough article doesn't have a link to integration guide

#MP-6402 Marketplace Cart

Feature walkthrough article: broken formatting

#MP-6404 Persistence ACL

1 ) typo: extra space, should be `spryker-feature/acl:"202108.0"`, not `spryker-feature/acl: "202108.0"`

4 ) Files missing `namespace` and `use` parts

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

5 ) to run `npm run mp:build` ZedUi module is needed (both as a composer dependency and on Pyz level)

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
