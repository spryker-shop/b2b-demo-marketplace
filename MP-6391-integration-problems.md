#MP-6392
##Marketplace Merchant feature integration

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
2 ) Verification: not `spy_glossary table`, but `spy_glossary_key` and `spy_glossary_translation` tables

3 ) Verification can't be done, because `MerchantProduct` is not integrated yet

Probably, more detailed checkout guide is needed (I had to update company role, add this role to customer and relogin to be able to add products to cart)

4 ) Verification: wrong url

   duplicate verification?

##Glue API
3 ) `MerchantProductOffersRestApiConfig` can't be used before `MerchantProductOffer` integration

   "Make sure that by sending the request GET http://glue.mysprykershop.com/orders?include=merchant, merchant attributes are returned in response." - merchant offers not integrated yet, so can't order items from merchants.


##Marketplace Merchant
4 ) requires MerchantPortal, that isn't integrated yet.
