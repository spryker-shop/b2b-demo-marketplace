<?php

declare(strict_types=1);

$startTime = microtime(true);

// ============================================================
// CONFIGURATION — edit ONLY this block
// ============================================================
// envInt: 0-safe env read (plain `getenv() ?: default` treats "0" as falsy).
function envInt(string $key, int $default): int {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : (int)$v;
}
$config = [
    // Store map: store name (must exist in spy_store) => locales + currencies
    'stores' => [
        'DE' => ['locales' => ['de_DE', 'en_US'], 'currencies' => ['EUR']],
        'AT' => ['locales' => ['de_DE', 'en_US'], 'currencies' => ['EUR']],
    ],

    // Products
    'product_count'          => envInt('GEN_PRODUCT_COUNT', 100),    // number of abstract products
    'concretes_per_abstract' => '1-3',   // fixed int OR range string (used when variant_distribution=uniform)

    // Variant distribution:
    //   'uniform' = every abstract gets concretes_per_abstract (range).
    //   'skewed'  = ~skewed_multi_pct% of abstracts get 2..skewed_multi_max concretes, the rest exactly 1.
    //               (Realistic catalog: e.g. 10% multi-variant up to 100, 90% single.)
    'variant_distribution' => getenv('GEN_VARIANT_DIST') ?: 'uniform',
    'skewed_multi_pct'     => envInt('GEN_SKEW_PCT', 10),
    'skewed_multi_max'     => envInt('GEN_SKEW_MAX', 100),

    // Categories
    'category_count' => 0,               // 0 = use only existing categories; >0 = generate this many (3 levels)

    // Merchants
    'merchants' => null,                 // null = use existing merchants; int = generate this many

    // Product Offers
    'offers_per_merchant' => envInt('GEN_OFFERS_PER_MERCHANT', 50),  // offers/merchant across concretes (0 = none; regular products stay purchasable via price+stock+availability)

    // Customers & Companies
    'customer_count'          => envInt('GEN_CUSTOMER_COUNT', 50),   // total customers (0 = skip; keep separate from k6 buyer import)
    'customers_per_company'   => 10,     // determines company count: ceil(customer_count / customers_per_company)

    // Customer-Specific Prices (B2B discounts via merchant relationships)
    'customer_specific_prices_count' => 20,

    // Technical
    'batch_size' => 1000,
    'index_offset' => envInt('GEN_INDEX_OFFSET', 0),
];
$idxBase = (int)($config['index_offset'] ?? 0);

// ============================================================
// BOOTSTRAP
// ============================================================
// Resolve the app's vendor/autoload.php regardless of where this script lives
// (app root, scripts/, or tools/<x>/ — e.g. b2b repo scripts/ vs sa-toolkit).
$autoload = null;
foreach ([
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if ($autoload === null) {
    fwrite(STDERR, "ERROR: vendor/autoload.php not found. Run from the Spryker app root or scripts/.\n");
    exit(1);
}
require $autoload;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

$out = new ConsoleOutput();
ProgressBar::setFormatDefinition(
    'gen',
    ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%  %message%'
);

function mkBar(ConsoleOutput $out, int $max, string $msg): ProgressBar
{
    $bar = new ProgressBar($out, max(1, $max));
    $bar->setFormat('gen');
    $bar->setMessage($msg);
    $bar->start();
    return $bar;
}

function section(ConsoleOutput $out, string $s): void
{
    $out->writeln('');
    $out->writeln("<info>$s</info>");
}

function fail(ConsoleOutput $out, string $msg): never
{
    $out->writeln("<error>ERROR: $msg</error>");
    exit(1);
}

function nowSql(): string
{
    return date('Y-m-d H:i:s');
}

function uuid4(): string
{
    $d    = random_bytes(16);
    $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
    $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

function randBetween(int $min, int $max): int
{
    return random_int($min, $max);
}

/** Parse '2' or '1-5' into [min, max]. */
function parseConcretesRange(string|int $cfg): array
{
    if (is_int($cfg)) {
        return [$cfg, $cfg];
    }
    if (str_contains((string)$cfg, '-')) {
        [$a, $b] = explode('-', $cfg, 2);
        return [(int)$a, (int)$b];
    }
    $n = (int)$cfg;
    return [$n, $n];
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$dbHost = getenv('SPRYKER_DB_HOST')     ?: 'database';
$dbPort = getenv('SPRYKER_DB_PORT')     ?: '3306';
$dbName = getenv('SPRYKER_DB_DATABASE') ?: 'eu-docker';
$dbUser = getenv('SPRYKER_DB_USERNAME') ?: 'spryker';
$dbPass = getenv('SPRYKER_DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);
} catch (PDOException $e) {
    fail($out, 'DB connection failed: ' . $e->getMessage());
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('SET UNIQUE_CHECKS=0');
$pdo->exec("SET SESSION sql_mode=''");

// ============================================================
// BULK INSERT HELPERS
// ============================================================

/**
 * Bulk-insert rows. Returns the first auto-increment ID of the first batch.
 *
 * @param array<int, array<mixed>> $rows
 */
function bulkInsert(PDO $pdo, string $table, array $columns, array $rows, int $batchSize): int
{
    if (!$rows) {
        return 0;
    }
    $colList = implode(', ', array_map(static fn($c) => "`$c`", $columns));
    $ph      = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $firstId = 0;

    foreach (array_chunk($rows, $batchSize) as $batch) {
        $sql  = "INSERT INTO `$table` ($colList) VALUES " . implode(', ', array_fill(0, count($batch), $ph));
        $stmt = $pdo->prepare($sql);
        $flat = array_merge(...array_map('array_values', $batch));
        $stmt->execute($flat);
        if ($firstId === 0) {
            $firstId = (int)$pdo->lastInsertId();
        }
    }

    return $firstId;
}

/**
 * Like bulkInsert but returns all generated IDs (firstId … firstId+n-1 per batch).
 *
 * @param array<int, array<mixed>> $rows
 * @return int[]
 */
function bulkInsertIds(PDO $pdo, string $table, array $columns, array $rows, int $batchSize): array
{
    if (!$rows) {
        return [];
    }
    $colList = implode(', ', array_map(static fn($c) => "`$c`", $columns));
    $ph      = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $ids     = [];

    foreach (array_chunk($rows, $batchSize) as $batch) {
        $sql  = "INSERT INTO `$table` ($colList) VALUES " . implode(', ', array_fill(0, count($batch), $ph));
        $stmt = $pdo->prepare($sql);
        $flat = array_merge(...array_map('array_values', $batch));
        $stmt->execute($flat);
        $first = (int)$pdo->lastInsertId();
        for ($i = 0; $i < count($batch); $i++) {
            $ids[] = $first + $i;
        }
    }

    return $ids;
}

// ============================================================
// RANDOM DATA POOLS  (pre-generated — not called per-row)
// ============================================================
$COLORS     = ['red', 'blue', 'green', 'black', 'white', 'yellow', 'purple', 'orange', 'grey', 'pink'];
$SIZES      = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
$MATERIALS  = ['cotton', 'polyester', 'leather', 'silk', 'wool', 'denim', 'linen'];
$BRANDS     = ['AlphaGear', 'BetaTech', 'GammaStyle', 'DeltaHome', 'EpsilonSport'];
$DESCS      = [
    'A high-quality product with excellent durability and modern design.',
    'Built for performance and comfort, this product exceeds expectations.',
    'Premium materials ensure long-lasting use and customer satisfaction.',
    'Designed with the modern consumer in mind, combining style and function.',
    'The perfect balance of quality, affordability, and innovative design.',
    'Engineered for everyday use, offering reliability and superior finish.',
    'A versatile product that adapts to your lifestyle and needs.',
];
$FIRST_NAMES = ['Alice', 'Bob', 'Charlie', 'Diana', 'Edward', 'Fiona', 'George', 'Helen', 'Ivan', 'Julia',
    'Kevin', 'Laura', 'Mike', 'Nina', 'Oscar', 'Paula', 'Quinn', 'Rosa', 'Steve', 'Tina'];
$LAST_NAMES  = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Taylor',
    'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Moore', 'Young', 'Lee'];

$BCRYPT_PASS = password_hash('change123', PASSWORD_BCRYPT, ['cost' => 10]);

$now = nowSql();

// ============================================================
// REFERENCE DATA LOOKUP + VALIDATION
// ============================================================
section($out, 'Loading & validating reference data...');

/** @var array<string,int>  name→id */
$dbStores = $pdo->query('SELECT name, id_store FROM spy_store')->fetchAll(PDO::FETCH_KEY_PAIR);
/** @var array<string,int>  locale_name→id */
$dbLocales = $pdo->query('SELECT locale_name, id_locale FROM spy_locale')->fetchAll(PDO::FETCH_KEY_PAIR);
/** @var array<string,int>  code→id */
$dbCurrencies = $pdo->query('SELECT code, id_currency FROM spy_currency')->fetchAll(PDO::FETCH_KEY_PAIR);

// Validate and resolve configured stores / locales / currencies
$resolvedStores = [];   // storeName => ['id' => int, 'locale_ids' => [name=>id], 'currency_ids' => [code=>id]]
foreach ($config['stores'] as $storeName => $storeCfg) {
    if (!isset($dbStores[$storeName])) {
        fail($out, "Store '$storeName' not found in spy_store. Available: " . implode(', ', array_keys($dbStores)));
    }
    $storeId      = $dbStores[$storeName];
    $localeIds    = [];
    foreach ($storeCfg['locales'] as $localeName) {
        if (!isset($dbLocales[$localeName])) {
            fail($out, "Locale '$localeName' (store $storeName) not found in spy_locale.");
        }
        $localeIds[$localeName] = $dbLocales[$localeName];
    }
    $currencyIds  = [];
    foreach ($storeCfg['currencies'] as $currencyCode) {
        if (!isset($dbCurrencies[$currencyCode])) {
            fail($out, "Currency '$currencyCode' (store $storeName) not found in spy_currency.");
        }
        $currencyIds[$currencyCode] = $dbCurrencies[$currencyCode];
    }
    $resolvedStores[$storeName] = [
        'id'           => $storeId,
        'locale_ids'   => $localeIds,
        'currency_ids' => $currencyIds,
    ];
}

// All unique locales across all stores
$allLocaleIds = [];
foreach ($resolvedStores as $s) {
    $allLocaleIds += $s['locale_ids'];
}

/** @var int[] */
$taxSetIds = $pdo->query('SELECT id_tax_set FROM spy_tax_set')->fetchAll(PDO::FETCH_COLUMN);
if (!$taxSetIds) {
    fail($out, 'No tax sets found in spy_tax_set.');
}

/** @var array<string,int> name→id */
$priceTypes = $pdo->query('SELECT name, id_price_type FROM spy_price_type')->fetchAll(PDO::FETCH_KEY_PAIR);
$defaultPriceTypeId  = $priceTypes['DEFAULT'] ?? fail($out, 'Price type DEFAULT not found.');
$originalPriceTypeId = $priceTypes['ORIGINAL'] ?? null;

/** @var int[] */
$warehouseIds = $pdo->query(
    "SELECT id_stock FROM spy_stock WHERE name IN ('Warehouse1','Warehouse2','Warehouse3')"
)->fetchAll(PDO::FETCH_COLUMN);
if (!$warehouseIds) {
    // Fall back to any active stock
    $warehouseIds = $pdo->query('SELECT id_stock FROM spy_stock WHERE is_active=1 LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
}

$categoryTemplateId = (int)$pdo->query(
    "SELECT id_category_template FROM spy_category_template WHERE name='Catalog (default)' LIMIT 1"
)->fetchColumn();
if (!$categoryTemplateId) {
    $categoryTemplateId = (int)$pdo->query('SELECT id_category_template FROM spy_category_template LIMIT 1')->fetchColumn();
}

$rootNodeId = (int)$pdo->query(
    'SELECT id_category_node FROM spy_category_node WHERE is_root=1 LIMIT 1'
)->fetchColumn();

$countryDe = (int)$pdo->query("SELECT id_country FROM spy_country WHERE iso2_code='DE' LIMIT 1")->fetchColumn();

$out->writeln(sprintf(
    '  stores=%d  locales=%d  warehouses=%d  taxSets=%d  priceTypes=%d',
    count($resolvedStores), count($allLocaleIds), count($warehouseIds), count($taxSetIds), count($priceTypes)
));

// ============================================================
// STEP 1 — CATEGORIES
// ============================================================
// All category IDs available for product assignment
$allCategoryIds = $pdo->query('SELECT id_category FROM spy_category WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);

if ($config['category_count'] > 0) {
    section($out, 'Step 1: Generating categories...');

    $total = $config['category_count'];
    $numL1 = max(1, (int)round($total * 0.2));
    $numL2 = max(1, (int)round($total * 0.4));
    $numL3 = max(1, $total - $numL1 - $numL2);

    $l1Names = ['Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Toys', 'Books', 'Health', 'Automotive'];
    $l2Names = ['Laptops', 'Smartphones', 'T-Shirts', 'Shoes', 'Furniture', 'Lighting', 'Bikes', 'Cameras'];
    $l3Names = ['Gaming Laptops', 'Business Laptops', 'Polo Shirts', 'Running Shoes', 'Office Chairs', 'LED Lights'];

    $pdo->beginTransaction();

    $bar = mkBar($out, $numL1 + $numL2 + $numL3, 'categories');

    // L1
    $l1CatRows = [];
    for ($i = 0; $i < $numL1; $i++) {
        $l1CatRows[] = ['gen-l1-' . $i, 1, 1, 1, 1, $categoryTemplateId];
    }
    $l1CatFirst = bulkInsert($pdo, 'spy_category',
        ['category_key','is_active','is_in_menu','is_clickable','is_searchable','fk_category_template'],
        $l1CatRows, $config['batch_size']);
    $l1CatIds = range($l1CatFirst, $l1CatFirst + $numL1 - 1);

    $l1NodeRows = [];
    foreach ($l1CatIds as $i => $catId) {
        $l1NodeRows[] = [$catId, $rootNodeId ?: null, 0, 1, $i];
    }
    $l1NodeFirst = bulkInsert($pdo, 'spy_category_node',
        ['fk_category','fk_parent_category_node','is_root','is_main','node_order'], $l1NodeRows, $config['batch_size']);
    $l1NodeIds = range($l1NodeFirst, $l1NodeFirst + $numL1 - 1);

    $closureRows = [];
    $attrRows    = [];
    $storeRows   = [];
    foreach ($l1NodeIds as $i => $nodeId) {
        $closureRows[] = [$nodeId, $nodeId, 0];
        if ($rootNodeId) {
            $closureRows[] = [$rootNodeId, $nodeId, 1];
        }
        $catId = $l1CatIds[$i];
        foreach ($allLocaleIds as $localeName => $localeId) {
            $name = ($l1Names[$i % count($l1Names)] ?? "Category L1 $i") . " ($localeName)";
            $attrRows[] = [$catId, $localeId, $name, $now, $now];
        }
        foreach ($resolvedStores as $s) {
            $storeRows[] = [$catId, $s['id']];
        }
    }
    bulkInsert($pdo, 'spy_category_closure_table', ['fk_category_node','fk_category_node_descendant','depth'], $closureRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_attribute', ['fk_category','fk_locale','name','created_at','updated_at'], $attrRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_store', ['fk_category','fk_store'], $storeRows, $config['batch_size']);
    $bar->advance($numL1);

    // L2
    $l2CatRows = [];
    for ($i = 0; $i < $numL2; $i++) {
        $l2CatRows[] = ['gen-l2-' . $i, 1, 1, 1, 1, $categoryTemplateId];
    }
    $l2CatFirst = bulkInsert($pdo, 'spy_category',
        ['category_key','is_active','is_in_menu','is_clickable','is_searchable','fk_category_template'],
        $l2CatRows, $config['batch_size']);
    $l2CatIds  = range($l2CatFirst, $l2CatFirst + $numL2 - 1);

    $l2NodeRows = [];
    foreach ($l2CatIds as $i => $catId) {
        $parentNodeId = $l1NodeIds[$i % $numL1];
        $l2NodeRows[] = [$catId, $parentNodeId, 0, 1, $i];
    }
    $l2NodeFirst = bulkInsert($pdo, 'spy_category_node',
        ['fk_category','fk_parent_category_node','is_root','is_main','node_order'], $l2NodeRows, $config['batch_size']);
    $l2NodeIds = range($l2NodeFirst, $l2NodeFirst + $numL2 - 1);

    $closureRows = [];
    $attrRows    = [];
    $storeRows   = [];
    foreach ($l2NodeIds as $i => $nodeId) {
        $parentNodeId  = $l1NodeIds[$i % $numL1];
        $closureRows[] = [$nodeId, $nodeId, 0];
        $closureRows[] = [$parentNodeId, $nodeId, 1];
        if ($rootNodeId) {
            $closureRows[] = [$rootNodeId, $nodeId, 2];
        }
        $catId = $l2CatIds[$i];
        foreach ($allLocaleIds as $localeName => $localeId) {
            $name = ($l2Names[$i % count($l2Names)] ?? "Category L2 $i") . " ($localeName)";
            $attrRows[] = [$catId, $localeId, $name, $now, $now];
        }
        foreach ($resolvedStores as $s) {
            $storeRows[] = [$catId, $s['id']];
        }
    }
    bulkInsert($pdo, 'spy_category_closure_table', ['fk_category_node','fk_category_node_descendant','depth'], $closureRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_attribute', ['fk_category','fk_locale','name','created_at','updated_at'], $attrRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_store', ['fk_category','fk_store'], $storeRows, $config['batch_size']);
    $bar->advance($numL2);

    // L3
    $l3CatRows = [];
    for ($i = 0; $i < $numL3; $i++) {
        $l3CatRows[] = ['gen-l3-' . $i, 1, 1, 1, 1, $categoryTemplateId];
    }
    $l3CatFirst = bulkInsert($pdo, 'spy_category',
        ['category_key','is_active','is_in_menu','is_clickable','is_searchable','fk_category_template'],
        $l3CatRows, $config['batch_size']);
    $l3CatIds  = range($l3CatFirst, $l3CatFirst + $numL3 - 1);

    $l3NodeRows = [];
    foreach ($l3CatIds as $i => $catId) {
        $parentNodeId = $l2NodeIds[$i % $numL2];
        $l3NodeRows[] = [$catId, $parentNodeId, 0, 1, $i];
    }
    $l3NodeFirst = bulkInsert($pdo, 'spy_category_node',
        ['fk_category','fk_parent_category_node','is_root','is_main','node_order'], $l3NodeRows, $config['batch_size']);
    $l3NodeIds = range($l3NodeFirst, $l3NodeFirst + $numL3 - 1);

    $closureRows = [];
    $attrRows    = [];
    $storeRows   = [];
    foreach ($l3NodeIds as $i => $nodeId) {
        $l2NodeId      = $l2NodeIds[$i % $numL2];
        $l1NodeId      = $l1NodeIds[($i % $numL2) % $numL1];
        $closureRows[] = [$nodeId, $nodeId, 0];
        $closureRows[] = [$l2NodeId, $nodeId, 1];
        $closureRows[] = [$l1NodeId, $nodeId, 2];
        if ($rootNodeId) {
            $closureRows[] = [$rootNodeId, $nodeId, 3];
        }
        $catId = $l3CatIds[$i];
        foreach ($allLocaleIds as $localeName => $localeId) {
            $name = ($l3Names[$i % count($l3Names)] ?? "Category L3 $i") . " ($localeName)";
            $attrRows[] = [$catId, $localeId, $name, $now, $now];
        }
        foreach ($resolvedStores as $s) {
            $storeRows[] = [$catId, $s['id']];
        }
    }
    bulkInsert($pdo, 'spy_category_closure_table', ['fk_category_node','fk_category_node_descendant','depth'], $closureRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_attribute', ['fk_category','fk_locale','name','created_at','updated_at'], $attrRows, $config['batch_size']);
    bulkInsert($pdo, 'spy_category_store', ['fk_category','fk_store'], $storeRows, $config['batch_size']);
    $bar->advance($numL3);

    $pdo->commit();
    $bar->finish();

    $newCatIds      = array_merge($l1CatIds, $l2CatIds, $l3CatIds);
    $allCategoryIds = array_merge($allCategoryIds, $newCatIds);
    $out->writeln(sprintf('  Generated %d L1 + %d L2 + %d L3 categories', $numL1, $numL2, $numL3));
} else {
    $out->writeln('');
    $out->writeln('<info>Step 1: Categories — skipped (using existing ' . count($allCategoryIds) . ' categories)</info>');
}

if (!$allCategoryIds) {
    fail($out, 'No categories available. Set category_count > 0 or seed categories first.');
}
$numCategories = count($allCategoryIds);

// ============================================================
// STEP 2 — MERCHANTS
// ============================================================
/** @var array<int, array{id_merchant: int, merchant_reference: string}> */
$merchantList = $pdo->query(
    'SELECT id_merchant, merchant_reference FROM spy_merchant WHERE is_active=1'
)->fetchAll();

if ($config['merchants'] !== null && (int)$config['merchants'] > 0) {
    section($out, 'Step 2: Generating merchants...');
    $pdo->beginTransaction();

    $numNew  = (int)$config['merchants'];
    $mRows   = [];
    $mpRows  = [];  // merchant profile rows

    for ($i = 0; $i < $numNew; $i++) {
        $ref     = sprintf('MER-GEN-%04d', $i);
        $mRows[] = ["Merchant-$i", "gen-merchant-$i@example.com", 'approved', $ref, 1, $now, $now];
    }
    $mFirst = bulkInsert($pdo, 'spy_merchant',
        ['name','email','status','merchant_reference','is_active','created_at','updated_at'],
        $mRows, $config['batch_size']);
    $newMerchantIds = range($mFirst, $mFirst + $numNew - 1);

    // merchant profiles
    foreach ($newMerchantIds as $mId) {
        $mpRows[] = [$mId];
    }
    bulkInsert($pdo, 'spy_merchant_profile', ['fk_merchant'], $mpRows, $config['batch_size']);

    // merchant → store
    $msRows = [];
    foreach ($newMerchantIds as $mId) {
        foreach ($resolvedStores as $s) {
            $msRows[] = [$mId, $s['id']];
        }
    }
    bulkInsert($pdo, 'spy_merchant_store', ['fk_merchant','fk_store'], $msRows, $config['batch_size']);

    // merchant → stock
    if ($warehouseIds) {
        $mstRows = [];
        foreach ($newMerchantIds as $idx => $mId) {
            $mstRows[] = [$mId, $warehouseIds[$idx % count($warehouseIds)], 1];
        }
        bulkInsert($pdo, 'spy_merchant_stock', ['fk_merchant','fk_stock','is_default'], $mstRows, $config['batch_size']);
    }

    $pdo->commit();

    foreach ($newMerchantIds as $idx => $mId) {
        $merchantList[] = ['id_merchant' => $mId, 'merchant_reference' => sprintf('MER-GEN-%04d', $idx)];
    }
    $out->writeln("  Generated $numNew merchants");
} else {
    $out->writeln('');
    $out->writeln('<info>Step 2: Merchants — using existing (' . count($merchantList) . ')</info>');
}

if (!$merchantList && $config['offers_per_merchant'] > 0) {
    $out->writeln('<comment>  Warning: no merchants found, offer generation will be skipped.</comment>');
}

// ============================================================
// STEP 3 — ABSTRACT PRODUCTS
// ============================================================
section($out, 'Step 3: Generating abstract products...');

[$concMin, $concMax] = parseConcretesRange($config['concretes_per_abstract']);
$productCount        = $config['product_count'];

// Pre-generate per-product random pools to avoid calling random() in tight insert loops
$productAttrs = [];
for ($i = 0; $i < $productCount; $i++) {
    $productAttrs[$i] = [
        'color'      => $COLORS[$i % count($COLORS)],
        'material'   => $MATERIALS[$i % count($MATERIALS)],
        'size'       => $SIZES[$i % count($SIZES)],
        'brand'      => $BRANDS[$i % count($BRANDS)],
        'weight_g'   => 50 + ($i * 97 % 4951),  // pseudo-random 50-5000, deterministic for speed
        'desc_idx'   => $i % count($DESCS),
        'taxset_idx' => $i % count($taxSetIds),
    ];
}

$pdo->beginTransaction();

$bar          = mkBar($out, $productCount, 'spy_product_abstract');
$absCols      = ['sku','attributes','fk_tax_set','approval_status','created_at','updated_at'];
$abstractRows = [];
$abstractFirst = 0;

for ($i = 0; $i < $productCount; $i++) {
    $a   = $productAttrs[$i];
    $sku = sprintf('GEN-ABSTRACT-%07d', $idxBase + $i);
    $abstractRows[] = [
        $sku,
        json_encode(['color' => $a['color'], 'size' => $a['size'], 'material' => $a['material'], 'weight_g' => $a['weight_g'], 'brand' => $a['brand']]),
        $taxSetIds[$a['taxset_idx']], 'approved', $now, $now,
    ];

    if (($i + 1) % $config['batch_size'] === 0) {
        $fid = bulkInsert($pdo, 'spy_product_abstract', $absCols, $abstractRows, $config['batch_size']);
        if ($abstractFirst === 0) {
            $abstractFirst = $fid;
        }
        $abstractRows = [];
        $bar->advance($config['batch_size']);
    }
}
if ($abstractRows) {
    $fid = bulkInsert($pdo, 'spy_product_abstract', $absCols, $abstractRows, $config['batch_size']);
    if ($abstractFirst === 0) {
        $abstractFirst = $fid;
    }
    $abstractRows = [];
}
$abstractIds = range($abstractFirst, $abstractFirst + $productCount - 1);
$bar->finish();
$out->writeln('');

// Localized attributes — flush every batch_size products to cap memory
$laCols = ['fk_product_abstract','fk_locale','name','description','meta_description','meta_keywords','meta_title','attributes','created_at','updated_at'];
$laRows = [];
$bar    = mkBar($out, $productCount * count($allLocaleIds), 'abstract localized attrs');
foreach ($abstractIds as $idx => $absId) {
    $a   = $productAttrs[$idx];
    $sku = sprintf('GEN-ABSTRACT-%07d', $idxBase + $idx);
    foreach ($allLocaleIds as $localeName => $localeId) {
        $name     = sprintf('Product %s - %s %s', $sku, $a['color'], $a['material']);
        $laRows[] = [$absId, $localeId, $name, $DESCS[$a['desc_idx']], "Buy $name online", $name . ', ' . $a['brand'], $name, '{}', $now, $now];
    }
    if (($idx + 1) % $config['batch_size'] === 0) {
        bulkInsert($pdo, 'spy_product_abstract_localized_attributes', $laCols, $laRows, $config['batch_size']);
        $laRows = [];
        $bar->advance($config['batch_size'] * count($allLocaleIds));
    }
}
if ($laRows) {
    bulkInsert($pdo, 'spy_product_abstract_localized_attributes', $laCols, $laRows, $config['batch_size']);
    $laRows = [];
}
$bar->finish();
$out->writeln('');

// Abstract → store
$asRows = [];
foreach ($abstractIds as $absId) {
    foreach ($resolvedStores as $s) {
        $asRows[] = [$absId, $s['id']];
    }
    if (count($asRows) >= $config['batch_size']) {
        bulkInsert($pdo, 'spy_product_abstract_store', ['fk_product_abstract','fk_store'], $asRows, $config['batch_size']);
        $asRows = [];
    }
}
if ($asRows) {
    bulkInsert($pdo, 'spy_product_abstract_store', ['fk_product_abstract','fk_store'], $asRows, $config['batch_size']);
    $asRows = [];
}

// Abstract → 1-3 random categories (deterministic, no duplicates per product)
$pcRows = [];
$pcCount = 0;
foreach ($abstractIds as $idx => $absId) {
    $numCats  = 1 + ($idx % 3);
    $assigned = [];
    for ($c = 0; $c < $numCats; $c++) {
        $catId = $allCategoryIds[($idx * 7 + $c * 31) % $numCategories];
        if (!in_array($catId, $assigned, true)) {
            $assigned[] = $catId;
            $pcRows[]   = [$absId, $catId, $c];
        }
    }
    if (count($pcRows) >= $config['batch_size']) {
        bulkInsert($pdo, 'spy_product_category', ['fk_product_abstract','fk_category','product_order'], $pcRows, $config['batch_size']);
        $pcCount += count($pcRows);
        $pcRows   = [];
    }
}
if ($pcRows) {
    bulkInsert($pdo, 'spy_product_category', ['fk_product_abstract','fk_category','product_order'], $pcRows, $config['batch_size']);
    $pcCount += count($pcRows);
    $pcRows   = [];
}

// Product URLs — one per (abstract × locale); required by ProductPageSearch publisher
$urlRows  = [];
$urlCount = 0;
foreach ($abstractIds as $idx => $absId) {
    $slug = sprintf('gen-abstract-%07d', $idxBase + $idx);
    foreach ($allLocaleIds as $localeName => $localeId) {
        $langCode  = strtolower(explode('_', $localeName)[0]);
        $urlRows[] = [$localeId, $absId, sprintf('/%s/%s', $langCode, $slug)];
    }
    if (count($urlRows) >= $config['batch_size']) {
        bulkInsert($pdo, 'spy_url', ['fk_locale','fk_resource_product_abstract','url'], $urlRows, $config['batch_size']);
        $urlCount += count($urlRows);
        $urlRows   = [];
    }
}
if ($urlRows) {
    bulkInsert($pdo, 'spy_url', ['fk_locale','fk_resource_product_abstract','url'], $urlRows, $config['batch_size']);
    $urlCount += count($urlRows);
    $urlRows   = [];
}

$pdo->commit();
$out->writeln("  Created $productCount abstract products");

// ============================================================
// STEP 4 — CONCRETE PRODUCTS
// ============================================================
section($out, 'Step 4: Generating concrete products...');

// Pre-calculate concretes count per abstract (deterministic)
$concretesPerAbstract = [];
$totalConcretes = 0;
$dist    = $config['variant_distribution'] ?? 'uniform';
$skewPct = max(0, min(100, $config['skewed_multi_pct'] ?? 10));
$skewMax = max(2, $config['skewed_multi_max'] ?? 100);
for ($i = 0; $i < $productCount; $i++) {
    if ($dist === 'skewed') {
        // ~skewPct% of abstracts are multi-variant (2..skewMax concretes); the rest get exactly 1.
        $isMulti = (($i * 31) % 100) < $skewPct;
        $n = $isMulti ? (2 + (($i * 7919) % ($skewMax - 1))) : 1;
    } else {
        $n = ($concMin === $concMax) ? $concMin : $concMin + ($i % ($concMax - $concMin + 1));
    }
    $concretesPerAbstract[$i] = $n;
    $totalConcretes += $n;
}
$out->writeln(sprintf('  distribution=%s → %d abstracts, %d concretes (avg %.1f/abstract)',
    $dist, $productCount, $totalConcretes, $productCount ? $totalConcretes / $productCount : 0));

$pdo->beginTransaction();

// Concrete products — stream-insert in batches; IDs are contiguous so range() still works
$concCols     = ['sku','fk_product_abstract','attributes','is_active','is_quantity_splittable','created_at','updated_at'];
$concreteRows = [];
$concreteFirst = 0;
$bar           = mkBar($out, $totalConcretes, 'spy_product');

foreach ($abstractIds as $absIdx => $absId) {
    $numConc  = $concretesPerAbstract[$absIdx];
    $absColor = $productAttrs[$absIdx]['color'];
    for ($v = 0; $v < $numConc; $v++) {
        $sku            = sprintf('GEN-CONCRETE-%07d-%d', $idxBase + $absIdx, $v);
        $size           = $SIZES[($absIdx + $v) % count($SIZES)];
        $concreteRows[] = [$sku, $absId, json_encode(['color' => $absColor, 'size' => $size]), 1, 1, $now, $now];
    }
    if (count($concreteRows) >= $config['batch_size']) {
        $fid = bulkInsert($pdo, 'spy_product', $concCols, $concreteRows, $config['batch_size']);
        if ($concreteFirst === 0) {
            $concreteFirst = $fid;
        }
        $bar->advance(count($concreteRows));
        $concreteRows = [];
    }
}
if ($concreteRows) {
    $fid = bulkInsert($pdo, 'spy_product', $concCols, $concreteRows, $config['batch_size']);
    if ($concreteFirst === 0) {
        $concreteFirst = $fid;
    }
    $bar->advance(count($concreteRows));
    $concreteRows = [];
}
$concreteIds = range($concreteFirst, $concreteFirst + $totalConcretes - 1);
$bar->finish();
$out->writeln('');

// Concrete localized attributes — flush every batch_size concretes
$claCols  = ['fk_product','fk_locale','name','description','attributes','created_at','updated_at'];
$claRows  = [];
$concrIdx = 0;
$bar      = mkBar($out, $totalConcretes * count($allLocaleIds), 'concrete localized attrs');
foreach ($abstractIds as $absIdx => $absId) {
    $numConc = $concretesPerAbstract[$absIdx];
    for ($v = 0; $v < $numConc; $v++) {
        $cId  = $concreteIds[$concrIdx++];
        $sku  = sprintf('GEN-CONCRETE-%07d-%d', $idxBase + $absIdx, $v);
        $size = $SIZES[($absIdx + $v) % count($SIZES)];
        foreach ($allLocaleIds as $localeName => $localeId) {
            $claRows[] = [$cId, $localeId, sprintf('Product %s - %s %s', $sku, $size, $productAttrs[$absIdx]['color']), $DESCS[$productAttrs[$absIdx]['desc_idx']], '{}', $now, $now];
        }
        if (count($claRows) >= $config['batch_size'] * count($allLocaleIds)) {
            bulkInsert($pdo, 'spy_product_localized_attributes', $claCols, $claRows, $config['batch_size']);
            $bar->advance(count($claRows));
            $claRows = [];
        }
    }
}
if ($claRows) {
    bulkInsert($pdo, 'spy_product_localized_attributes', $claCols, $claRows, $config['batch_size']);
    $bar->advance(count($claRows));
    $claRows = [];
}
$bar->finish();
$out->writeln('');
unset($productAttrs);

// Build concrete SKU list for offer assignment
$concreteSkus = [];
$concrIdx     = 0;
foreach ($abstractIds as $absIdx => $absId) {
    for ($v = 0; $v < $concretesPerAbstract[$absIdx]; $v++) {
        $concreteSkus[$concrIdx++] = sprintf('GEN-CONCRETE-%07d-%d', $idxBase + $absIdx, $v);
    }
}

// spy_product_search — flush per batch
$psCount = 0;
$psRows  = [];
foreach ($concreteIds as $cId) {
    foreach ($allLocaleIds as $localeId) {
        $psRows[] = [$cId, $localeId, 1];
    }
    if (count($psRows) >= $config['batch_size'] * count($allLocaleIds)) {
        bulkInsert($pdo, 'spy_product_search', ['fk_product','fk_locale','is_searchable'], $psRows, $config['batch_size']);
        $psCount += count($psRows);
        $psRows   = [];
    }
}
if ($psRows) {
    bulkInsert($pdo, 'spy_product_search', ['fk_product','fk_locale','is_searchable'], $psRows, $config['batch_size']);
    $psCount += count($psRows);
    $psRows   = [];
}

$pdo->commit();
$out->writeln("  Created $totalConcretes concrete products");

// ============================================================
// STEP 5 — PRICES  (per concrete, per store × currency)
// ============================================================
section($out, 'Step 5: Generating prices...');

$pdo->beginTransaction();

// Build (store_id → [currency_id, ...]) map
$storeCurrencyPairs = [];
foreach ($resolvedStores as $storeName => $s) {
    foreach ($s['currency_ids'] as $code => $currId) {
        $storeCurrencyPairs[] = ['store_id' => $s['id'], 'currency_id' => $currId];
    }
}

// Process prices in mini-batches: insert pp rows → compute pps → insert pps → compute ppd → insert ppd.
// This keeps only one batch in memory at a time instead of accumulating 600k+ rows.
$ppCols      = ['fk_product_abstract','fk_product','fk_price_type'];
$ppsCols     = ['fk_price_product','fk_currency','fk_store','gross_price','net_price'];
$ppsPerPp    = count($storeCurrencyPairs);
$ppTypeCount = $originalPriceTypeId !== null ? 2 : 1;
$ppBatchSize = $config['batch_size'] * $ppTypeCount;

$ppTotalCount  = 0;
$ppsTotalCount = 0;
$ppdTotalCount = 0;

$totalPpExpected = ($productCount + $totalConcretes) * $ppTypeCount;
$bar = mkBar($out, $totalPpExpected, 'spy_price_product');

// Flush one pp batch: insert pp, pps, ppd — keeps peak RAM to O(batch) not O(all)
$flushPpBatch = static function (array $ppBatch, array $ppMetas) use (
    $pdo, $ppCols, $ppsCols, $storeCurrencyPairs, $defaultPriceTypeId, $ppsPerPp, $config,
    &$ppTotalCount, &$ppsTotalCount, &$ppdTotalCount
): void {
    if (!$ppBatch) {
        return;
    }
    $ppFirst = bulkInsert($pdo, 'spy_price_product', $ppCols, $ppBatch, $config['batch_size']);
    $ppTotalCount += count($ppBatch);

    $ppsRows = [];
    foreach ($ppBatch as $bIdx => $pp) {
        $meta       = $ppMetas[$bIdx];
        // Keep prices low (€1.00–€50.99) so heavy carts stay under the shop's cart-value
        // limit (e.g. €10,000) — otherwise checkout blocks the order.
        $baseGross  = 100 + ($meta['idx'] % 5000);
        $grossPrice = $meta['isDefault'] ? $baseGross : (int)($baseGross * 1.25);
        $netPrice   = (int)($grossPrice * 0.8);
        $ppId       = $ppFirst + $bIdx;
        foreach ($storeCurrencyPairs as $sc) {
            $ppsRows[] = [$ppId, $sc['currency_id'], $sc['store_id'], $grossPrice, $netPrice];
        }
    }
    $ppsFirst = bulkInsert($pdo, 'spy_price_product_store', $ppsCols, $ppsRows, $config['batch_size']);
    $ppsTotalCount += count($ppsRows);

    $ppdRows = [];
    foreach ($ppBatch as $bIdx => $pp) {
        if ($ppMetas[$bIdx]['isDefault']) {
            $ppdRows[] = [$ppsFirst + $bIdx * $ppsPerPp];
        }
    }
    bulkInsert($pdo, 'spy_price_product_default', ['fk_price_product_store'], $ppdRows, $config['batch_size']);
    $ppdTotalCount += count($ppdRows);
};

$ppBatch = [];
$ppMetas = [];

foreach ($abstractIds as $idx => $absId) {
    $ppBatch[] = [$absId, null, $defaultPriceTypeId];
    $ppMetas[] = ['idx' => $idx, 'isDefault' => true];
    if ($originalPriceTypeId !== null) {
        $ppBatch[] = [$absId, null, $originalPriceTypeId];
        $ppMetas[] = ['idx' => $idx, 'isDefault' => false];
    }
    if (count($ppBatch) >= $ppBatchSize) {
        $flushPpBatch($ppBatch, $ppMetas);
        $bar->advance(count($ppBatch));
        $ppBatch = [];
        $ppMetas = [];
    }
}

foreach ($concreteIds as $cidx => $cId) {
    $ppBatch[] = [null, $cId, $defaultPriceTypeId];
    $ppMetas[] = ['idx' => $cidx, 'isDefault' => true];
    if ($originalPriceTypeId !== null) {
        $ppBatch[] = [null, $cId, $originalPriceTypeId];
        $ppMetas[] = ['idx' => $cidx, 'isDefault' => false];
    }
    if (count($ppBatch) >= $ppBatchSize) {
        $flushPpBatch($ppBatch, $ppMetas);
        $bar->advance(count($ppBatch));
        $ppBatch = [];
        $ppMetas = [];
    }
}
if ($ppBatch) {
    $flushPpBatch($ppBatch, $ppMetas);
}
unset($ppBatch, $ppMetas, $flushPpBatch);

$bar->finish();
$out->writeln('');

$pdo->commit();
$out->writeln(sprintf('  %d price_product  %d price_product_store  %d price_product_default',
    $ppTotalCount, $ppsTotalCount, $ppdTotalCount));

// ============================================================
// STEP 6 — STOCK
// ============================================================
section($out, 'Step 6: Generating stock...');

$pdo->beginTransaction();

// Note: spy_stock_product has no `uuid` column in this Spryker version — omit it.
$spCols  = ['fk_product','fk_stock','quantity','is_never_out_of_stock'];
$spRows  = [];
$spCount = 0;
$bar     = mkBar($out, $totalConcretes * count($warehouseIds), 'spy_stock_product');
foreach ($concreteIds as $idx => $cId) {
    foreach ($warehouseIds as $stockId) {
        $spRows[] = [$cId, $stockId, 10000, 1];  // ample stock + never-out-of-stock → always orderable
    }
    if (count($spRows) >= $config['batch_size']) {
        bulkInsert($pdo, 'spy_stock_product', $spCols, $spRows, $config['batch_size']);
        $spCount += count($spRows);
        $bar->advance(count($spRows));
        $spRows = [];
    }
}
if ($spRows) {
    bulkInsert($pdo, 'spy_stock_product', $spCols, $spRows, $config['batch_size']);
    $spCount += count($spRows);
    $spRows  = [];
}
$bar->finish();
$out->writeln('');

$pdo->commit();
unset($concreteIds);

// ============================================================
// STEP 7 — AVAILABILITY
// ============================================================
section($out, 'Step 7: Generating availability...');

$pdo->beginTransaction();

// spy_availability_abstract: one per (abstract_sku × store)
$aaRows = [];
foreach ($abstractIds as $idx => $absId) {
    $sku = sprintf('GEN-ABSTRACT-%07d', $idxBase + $idx);
    $qty = 100000;  // ample availability (index-derived formula produced 0 for low indices → unorderable)
    foreach ($resolvedStores as $s) {
        $aaRows[] = [$sku, number_format($qty, 10, '.', ''), $s['id']];
    }
}
$bar     = mkBar($out, count($aaRows), 'spy_availability_abstract');
$aaFirst = bulkInsert($pdo, 'spy_availability_abstract',
    ['abstract_sku','quantity','fk_store'], $aaRows, $config['batch_size']);
$aaIds   = range($aaFirst, $aaFirst + count($aaRows) - 1);
$bar->finish();
$out->writeln('');

$storeArr  = array_values($resolvedStores);
$numStores = count($storeArr);
$avCols    = ['sku','fk_availability_abstract','quantity','is_never_out_of_stock','fk_store'];
$avRows    = [];
$avCount   = 0;
$concrIdx  = 0;
$bar       = mkBar($out, $totalConcretes * $numStores, 'spy_availability');

foreach ($abstractIds as $absIdx => $absId) {
    $numConc = $concretesPerAbstract[$absIdx];
    for ($v = 0; $v < $numConc; $v++) {
        $sku = sprintf('GEN-CONCRETE-%07d-%d', $idxBase + $absIdx, $v);
        $qty = 100000;  // ample availability
        foreach ($storeArr as $sIdx => $s) {
            $avRows[] = [$sku, $aaIds[$absIdx * $numStores + $sIdx], number_format($qty, 10, '.', ''), 1, $s['id']];  // never_out_of_stock=1
        }
        $concrIdx++;
        if (count($avRows) >= $config['batch_size']) {
            bulkInsert($pdo, 'spy_availability', $avCols, $avRows, $config['batch_size']);
            $avCount += count($avRows);
            $bar->advance(count($avRows));
            $avRows = [];
        }
    }
}
if ($avRows) {
    bulkInsert($pdo, 'spy_availability', $avCols, $avRows, $config['batch_size']);
    $avCount += count($avRows);
    $avRows   = [];
}
$bar->finish();
$out->writeln('');
unset($concretesPerAbstract, $aaIds);

$pdo->commit();

// ============================================================
// STEP 8 — PRODUCT IMAGES
// ============================================================
section($out, 'Step 8: Generating product images...');

$pdo->beginTransaction();

$imgRows = [];
foreach ($abstractIds as $idx => $absId) {
    $sku      = sprintf('GEN-ABSTRACT-%07d', $idxBase + $idx);
    $imgRows[] = [
        "https://via.placeholder.com/200x200.png?text=$sku",
        "https://via.placeholder.com/600x600.png?text=$sku",
        $now, $now,
    ];
}
$bar      = mkBar($out, $productCount, 'spy_product_image');
$imgFirst = bulkInsert($pdo, 'spy_product_image',
    ['external_url_small','external_url_large','created_at','updated_at'], $imgRows, $config['batch_size']);
$imgIds   = range($imgFirst, $imgFirst + $productCount - 1);
$bar->finish();
$out->writeln('');

// One image set per abstract × locale
$setRows = [];
foreach ($abstractIds as $aIdx => $absId) {
    foreach ($allLocaleIds as $localeName => $localeId) {
        $setRows[] = [$absId, $localeId, 'default', $now, $now];
    }
}
$bar      = mkBar($out, count($setRows), 'spy_product_image_set');
$setFirst = bulkInsert($pdo, 'spy_product_image_set',
    ['fk_product_abstract','fk_locale','name','created_at','updated_at'], $setRows, $config['batch_size']);
$setIds   = range($setFirst, $setFirst + count($setRows) - 1);
$bar->finish();
$out->writeln('');

$localeCount = count($allLocaleIds);
$s2iRows     = [];
foreach ($setIds as $sIdx => $setId) {
    $imgId    = $imgIds[(int)floor($sIdx / $localeCount)];
    $s2iRows[] = [$setId, $imgId, 0];
}
bulkInsert($pdo, 'spy_product_image_set_to_product_image',
    ['fk_product_image_set','fk_product_image','sort_order'], $s2iRows, $config['batch_size']);

$pdo->commit();

// ============================================================
// STEP 9 — PRODUCT OFFERS
// ============================================================
if ($config['offers_per_merchant'] > 0 && $merchantList) {
    section($out, 'Step 9: Generating product offers...');
    $pdo->beginTransaction();

    $totalOffers = count($merchantList) * $config['offers_per_merchant'];
    $bar         = mkBar($out, $totalOffers, 'spy_product_offer');
    $offerRows   = [];
    $offerCount  = 0;

    foreach ($merchantList as $mIdx => $merchant) {
        for ($o = 0; $o < $config['offers_per_merchant']; $o++) {
            // Distribute offers across concretes: merchant i, offer o → concrete index
            $concIdx    = ($mIdx * $config['offers_per_merchant'] + $o) % $totalConcretes;
            $concSku    = $concreteSkus[$concIdx];
            $offerRef   = sprintf('OFFER-%s-%05d', $merchant['merchant_reference'], $o);
            $offerRows[] = [
                $concSku,
                $offerRef,
                $merchant['merchant_reference'],
                1,
                'approved',
                $now, $now,
            ];
            $offerCount++;
            if ($offerCount % $config['batch_size'] === 0) {
                $bar->advance($config['batch_size']);
            }
        }
    }

    $offerFirst = bulkInsert($pdo, 'spy_product_offer',
        ['concrete_sku','product_offer_reference','merchant_reference','is_active','approval_status','created_at','updated_at'],
        $offerRows, $config['batch_size']);
    $offerIds = range($offerFirst, $offerFirst + count($offerRows) - 1);
    $bar->finish();
    $out->writeln('');

    // offer → store
    $osRows = [];
    foreach ($offerIds as $offerId) {
        foreach ($resolvedStores as $s) {
            $osRows[] = [$offerId, $s['id']];
        }
    }
    bulkInsert($pdo, 'spy_product_offer_store', ['fk_product_offer','fk_store'], $osRows, $config['batch_size']);

    // offer prices: spy_price_product (no abstract, no concrete) → spy_price_product_store → spy_price_product_offer
    $oppRows = [];
    foreach ($offerIds as $oIdx => $offerId) {
        $oppRows[] = [null, null, $defaultPriceTypeId];
    }
    $oppFirst = bulkInsert($pdo, 'spy_price_product',
        ['fk_product_abstract','fk_product','fk_price_type'], $oppRows, $config['batch_size']);
    $oppIds   = range($oppFirst, $oppFirst + count($oppRows) - 1);

    $oppsRows = [];
    foreach ($oppIds as $oIdx => $ppId) {
        $gross = 100 + ($oIdx % 5000);  // low offer price (cart-value limit)
        $net   = (int)($gross * 0.8);
        foreach ($storeCurrencyPairs as $sc) {
            $oppsRows[] = [$ppId, $sc['currency_id'], $sc['store_id'], $gross, $net];
        }
    }
    $oppsFirst = bulkInsert($pdo, 'spy_price_product_store',
        ['fk_price_product','fk_currency','fk_store','gross_price','net_price'],
        $oppsRows, $config['batch_size']);
    $oppsIds = range($oppsFirst, $oppsFirst + count($oppsRows) - 1);

    $ppoRows    = [];
    $scCount    = count($storeCurrencyPairs);
    foreach ($offerIds as $oIdx => $offerId) {
        $ppsId     = $oppsIds[$oIdx * $scCount];  // first store/currency pps for this offer
        $ppoRows[] = [$offerId, $ppsId];
    }
    bulkInsert($pdo, 'spy_price_product_offer',
        ['fk_product_offer','fk_price_product_store'], $ppoRows, $config['batch_size']);

    // offer stock
    if ($warehouseIds) {
        $ostkRows = [];
        foreach ($offerIds as $oIdx => $offerId) {
            $ostkRows[] = [$offerId, $warehouseIds[$oIdx % count($warehouseIds)], 10 + ($oIdx % 990), 0];
        }
        bulkInsert($pdo, 'spy_product_offer_stock',
            ['fk_product_offer','fk_stock','quantity','is_never_out_of_stock'], $ostkRows, $config['batch_size']);
    }

    $pdo->commit();
    $out->writeln(sprintf('  Created %d offers across %d merchants', count($offerIds), count($merchantList)));
} else {
    $out->writeln('');
    $out->writeln('<info>Step 9: Product offers — skipped</info>');
    $offerIds = [];
}

// ============================================================
// STEP 10 — COMPANIES, BUs, ADDRESSES, CUSTOMERS
// ============================================================
$companyCount = (int)ceil($config['customer_count'] / max(1, $config['customers_per_company']));

section($out, "Step 10: Generating $companyCount companies + {$config['customer_count']} customers...");

$pdo->beginTransaction();

// Companies
$coRows = [];
for ($c = 0; $c < $companyCount; $c++) {
    $coRows[] = ["Company-$c", "gen-company-$c", 1, 1, uuid4()];
}
$coFirst = bulkInsert($pdo, 'spy_company', ['name','key','status','is_active','uuid'], $coRows, $config['batch_size']);
$coIds   = range($coFirst, $coFirst + $companyCount - 1);

// Company → store
$cosRows = [];
foreach ($coIds as $coId) {
    foreach ($resolvedStores as $s) {
        $cosRows[] = [$coId, $s['id']];
    }
}
bulkInsert($pdo, 'spy_company_store', ['fk_company','fk_store'], $cosRows, $config['batch_size']);

// Addresses (1–3 per company)
$cuaRows = [];
foreach ($coIds as $cIdx => $coId) {
    $numAddr = 1 + ($cIdx % 3);  // 1, 2, or 3
    for ($a = 0; $a < $numAddr; $a++) {
        $cuaRows[] = [
            $coId, $countryDe,
            sprintf('%d Main Street', $cIdx * 10 + $a),
            sprintf('%05d', 10000 + $cIdx * 3 + $a),
            'Berlin',
            sprintf('gen-addr-%d-%d', $cIdx, $a),
            uuid4(),
        ];
    }
}
$cuaFirst = bulkInsert($pdo, 'spy_company_unit_address',
    ['fk_company','fk_country','address1','zip_code','city','key','uuid'], $cuaRows, $config['batch_size']);
$cuaIds = range($cuaFirst, $cuaFirst + count($cuaRows) - 1);

// Business units (1–5 per company)
$buRows  = [];
$buMeta  = [];  // [coId, coIdx] per BU
$addrIdx = 0;
foreach ($coIds as $cIdx => $coId) {
    $numBu = 1 + ($cIdx % 5);  // 1–5 BUs
    for ($b = 0; $b < $numBu; $b++) {
        $buRows[] = [$coId, sprintf('BU-%d-%d', $cIdx, $b), sprintf('gen-bu-%d-%d', $cIdx, $b), uuid4()];
        $buMeta[] = ['coId' => $coId, 'coIdx' => $cIdx, 'buLocalIdx' => $b];
    }
}
$buFirst = bulkInsert($pdo, 'spy_company_business_unit',
    ['fk_company','name','key','uuid'], $buRows, $config['batch_size']);
$buIds   = range($buFirst, $buFirst + count($buRows) - 1);

// Link addresses to BUs (first address of company → first BU of company)
$buaRows  = [];
$addrBase = 0;
$buBase   = 0;
foreach ($coIds as $cIdx => $coId) {
    $numAddr = 1 + ($cIdx % 3);
    $numBu   = 1 + ($cIdx % 5);
    for ($a = 0; $a < $numAddr; $a++) {
        $buaRows[] = [$buIds[$buBase + ($a % $numBu)], $cuaIds[$addrBase + $a]];
    }
    $addrBase += $numAddr;
    $buBase   += $numBu;
}
bulkInsert($pdo, 'spy_company_unit_address_to_company_business_unit',
    ['fk_company_business_unit','fk_company_unit_address'], $buaRows, $config['batch_size']);

// Company roles (one default per company)
$crRows = [];
foreach ($coIds as $cIdx => $coId) {
    $crRows[] = [$coId, 'Buyer', 1, sprintf('gen-role-%d', $cIdx), uuid4()];
}
$crFirst = bulkInsert($pdo, 'spy_company_role',
    ['fk_company','name','is_default','key','uuid'], $crRows, $config['batch_size']);
$crIds   = range($crFirst, $crFirst + $companyCount - 1);

// Customers
$custRows = [];
$custTotal = $config['customer_count'];
$bar       = mkBar($out, $custTotal, 'spy_customer');
for ($k = 0; $k < $custTotal; $k++) {
    $ref      = sprintf('CUST-%06d', $k);
    $email    = sprintf('customer-%d@test.local', $k);
    $first    = $FIRST_NAMES[$k % count($FIRST_NAMES)];
    $last     = $LAST_NAMES[$k % count($LAST_NAMES)];
    $localeId = array_values($allLocaleIds)[0];
    $custRows[] = [$ref, $email, $first, $last, $BCRYPT_PASS, $localeId, date('Y-m-d'), $now, $now];
    if (($k + 1) % $config['batch_size'] === 0) {
        $bar->advance($config['batch_size']);
    }
}
$custFirst = bulkInsert($pdo, 'spy_customer',
    ['customer_reference','email','first_name','last_name','password','fk_locale','registered','created_at','updated_at'],
    $custRows, $config['batch_size']);
$custIds   = range($custFirst, $custFirst + $custTotal - 1);
$bar->finish();
$out->writeln('');

// Map customers to companies and BUs
// Build per-company BU list
$buByCompanyIdx = [];
$buBase = 0;
foreach ($coIds as $cIdx => $coId) {
    $numBu = 1 + ($cIdx % 5);
    $buByCompanyIdx[$cIdx] = array_slice($buIds, $buBase, $numBu);
    $buBase += $numBu;
}

$cuRows   = [];
$curRows  = [];  // spy_company_role_to_company_user
$cuFirst  = null;

foreach ($custIds as $k => $custId) {
    $coIdx  = (int)floor($k / $config['customers_per_company']) % $companyCount;
    $coId   = $coIds[$coIdx];
    $buList = $buByCompanyIdx[$coIdx];
    $buId   = $buList[$k % count($buList)];
    $cuRows[] = [$coId, $custId, $buId, 1, 0, sprintf('gen-cu-%d', $k), uuid4()];
}
$cuFirst = bulkInsert($pdo, 'spy_company_user',
    ['fk_company','fk_customer','fk_company_business_unit','is_active','is_default','key','uuid'],
    $cuRows, $config['batch_size']);
$cuIds = range($cuFirst, $cuFirst + $custTotal - 1);

// Role → company user
foreach ($cuIds as $cuIdx => $cuId) {
    $coIdx   = (int)floor($cuIdx / $config['customers_per_company']) % $companyCount;
    $roleId  = $crIds[$coIdx];
    $curRows[] = [$roleId, $cuId, $now, $now];
}
bulkInsert($pdo, 'spy_company_role_to_company_user',
    ['fk_company_role','fk_company_user','created_at','updated_at'], $curRows, $config['batch_size']);

$pdo->commit();
$out->writeln(sprintf('  Created %d companies, %d BUs, %d customers', $companyCount, count($buIds), $custTotal));

// ============================================================
// STEP 11 — MERCHANT RELATIONSHIPS + CUSTOMER-SPECIFIC PRICES
// ============================================================
if ($config['customer_specific_prices_count'] > 0 && $merchantList) {
    section($out, 'Step 11: Generating merchant relationships & B2B prices...');
    $pdo->beginTransaction();

    // One merchant relationship per BU (links a merchant to a BU)
    $mrRows = [];
    foreach ($buIds as $bIdx => $buId) {
        $merchant  = $merchantList[$bIdx % count($merchantList)];
        $mrRows[]  = [
            $merchant['id_merchant'],
            $buId,
            sprintf('gen-mr-%d', $bIdx),
            $now, $now,
        ];
    }
    $mrFirst = bulkInsert($pdo, 'spy_merchant_relationship',
        ['fk_merchant','fk_company_business_unit','merchant_relationship_key','created_at','updated_at'],
        $mrRows, $config['batch_size']);
    $mrIds = range($mrFirst, $mrFirst + count($mrRows) - 1);

    // spy_merchant_relationship_to_company_business_unit
    $mrtcbuRows = [];
    foreach ($mrIds as $mrIdx => $mrId) {
        $mrtcbuRows[] = [$mrId, $buIds[$mrIdx]];
    }
    bulkInsert($pdo, 'spy_merchant_relationship_to_company_business_unit',
        ['fk_merchant_relationship','fk_company_business_unit'], $mrtcbuRows, $config['batch_size']);

    // Customer-specific prices: randomly pick N unique (bu + abstract) pairs
    $numB2b    = min($config['customer_specific_prices_count'], count($buIds) * $productCount);
    $b2bPairs  = [];
    $usedPairs = [];

    for ($n = 0; $n < $numB2b * 3 && count($b2bPairs) < $numB2b; $n++) {
        $buIdx   = ($n * 13) % count($buIds);
        $absIdx  = ($n * 17) % $productCount;
        $pairKey = "$buIdx:$absIdx";
        if (!isset($usedPairs[$pairKey])) {
            $usedPairs[$pairKey] = true;
            $b2bPairs[] = ['buIdx' => $buIdx, 'absIdx' => $absIdx, 'absId' => $abstractIds[$absIdx], 'mrId' => $mrIds[$buIdx]];
        }
    }

    // Reuse existing spy_price_product rows from Step 5 — do NOT insert new ones;
    // spy_price_product has a unique constraint on (fk_product_abstract, fk_price_type).
    $uniqueAbsIds = array_values(array_unique(array_column($b2bPairs, 'absId')));
    $ph           = implode(',', array_fill(0, count($uniqueAbsIds), '?'));
    $stmt         = $pdo->prepare(
        "SELECT fk_product_abstract, id_price_product FROM spy_price_product
         WHERE fk_product_abstract IN ($ph) AND fk_price_type = ? AND fk_product IS NULL"
    );
    $stmt->execute([...$uniqueAbsIds, $defaultPriceTypeId]);
    $ppIdByAbsId = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);  // absId => ppId

    // Build discounted price_product_store rows (5–30% below default)
    $b2bPpsRows  = [];
    $b2bPpmrMeta = [];

    foreach ($b2bPairs as $pairIdx => $pair) {
        $ppId = $ppIdByAbsId[$pair['absId']] ?? null;
        if ($ppId === null) {
            continue;
        }
        $disc        = 0.70 + ($pairIdx % 26) * 0.01;
        $gross       = (int)((100 + ($pairIdx % 5000)) * $disc);  // low MR price (cart-value limit)
        $net         = (int)($gross * 0.8);
        $ppsOffset   = count($b2bPpsRows);
        foreach ($storeCurrencyPairs as $sc) {
            $b2bPpsRows[] = [$ppId, $sc['currency_id'], $sc['store_id'], $gross, $net];
        }
        $b2bPpmrMeta[] = ['mrId' => $pair['mrId'], 'absId' => $pair['absId'], 'ppsOffset' => $ppsOffset];
    }

    $b2bPpsFirst = bulkInsert($pdo, 'spy_price_product_store',
        ['fk_price_product','fk_currency','fk_store','gross_price','net_price'],
        $b2bPpsRows, $config['batch_size']);
    $b2bPpsIds = range($b2bPpsFirst, $b2bPpsFirst + count($b2bPpsRows) - 1);

    // spy_price_product_merchant_relationship: one per (merchant_relationship + abstract)
    $ppmrRows = [];
    foreach ($b2bPpmrMeta as $meta) {
        $ppsId      = $b2bPpsIds[$meta['ppsOffset']];
        $ppmrRows[] = [$meta['mrId'], $ppsId, null, $meta['absId']];
    }
    bulkInsert($pdo, 'spy_price_product_merchant_relationship',
        ['fk_merchant_relationship','fk_price_product_store','fk_product','fk_product_abstract'],
        $ppmrRows, $config['batch_size']);

    $pdo->commit();
    $out->writeln(sprintf('  Created %d merchant relationships, %d B2B price entries',
        count($mrIds), count($ppmrRows)));
} else {
    $out->writeln('');
    $out->writeln('<info>Step 11: B2B prices — skipped</info>');
}

// ============================================================
// DONE
// ============================================================
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
$pdo->exec('SET UNIQUE_CHECKS=1');

$elapsed = microtime(true) - $startTime;

function printRow(ConsoleOutput $out, string $label, int $count): void
{
    $out->writeln(sprintf('<info>  %-42s %s</info>', $label, number_format($count)));
}

$out->writeln('');
$sep = str_repeat('═', 52);
$out->writeln("<info>$sep</info>");
$out->writeln(sprintf('<info>  Done in %.1fs  (%.0f abstract products/min)</info>',
    $elapsed, $productCount / ($elapsed / 60)));
$out->writeln("<info>$sep</info>");

$out->writeln('<info>  ── Products ─────────────────────────────────────</info>');
printRow($out, 'spy_product_abstract',                    $productCount);
printRow($out, 'spy_product_abstract_localized_attributes', $productCount * count($allLocaleIds));
printRow($out, 'spy_product_abstract_store',              $productCount * count($resolvedStores));
printRow($out, 'spy_product_category',                    $pcCount ?? 0);
printRow($out, 'spy_url (product abstract)',               $urlCount ?? 0);
printRow($out, 'spy_product (concrete)',                  $totalConcretes);
printRow($out, 'spy_product_localized_attributes',        $totalConcretes * count($allLocaleIds));
printRow($out, 'spy_product_search',                      $psCount ?? 0);

$out->writeln('<info>  ── Categories ───────────────────────────────────</info>');
printRow($out, 'spy_category (total available)',           count($allCategoryIds));

$out->writeln('<info>  ── Prices ───────────────────────────────────────</info>');
printRow($out, 'spy_price_product',                       $ppTotalCount ?? 0);
printRow($out, 'spy_price_product_store',                 $ppsTotalCount ?? 0);
printRow($out, 'spy_price_product_default',               $ppdTotalCount ?? 0);
printRow($out, 'spy_price_product_merchant_relationship', count($ppmrRows ?? []));

$out->writeln('<info>  ── Stock & Availability ─────────────────────────</info>');
printRow($out, 'spy_stock_product',                       $spCount ?? 0);
printRow($out, 'spy_availability_abstract',               count($aaRows ?? []));
printRow($out, 'spy_availability',                        $avCount ?? 0);

$out->writeln('<info>  ── Images ───────────────────────────────────────</info>');
printRow($out, 'spy_product_image',                       count($imgIds ?? []));
printRow($out, 'spy_product_image_set',                   count($setIds ?? []));
printRow($out, 'spy_product_image_set_to_product_image',  count($s2iRows ?? []));

$out->writeln('<info>  ── Merchants & Offers ───────────────────────────</info>');
printRow($out, 'spy_merchant (total active)',              count($merchantList));
printRow($out, 'spy_product_offer',                       count($offerIds));
printRow($out, 'spy_price_product_offer',                 count($ppoRows ?? []));
printRow($out, 'spy_product_offer_stock',                 count($ostkRows ?? []));

$out->writeln('<info>  ── B2B ──────────────────────────────────────────</info>');
printRow($out, 'spy_company',                             $companyCount);
printRow($out, 'spy_company_business_unit',               count($buIds));
printRow($out, 'spy_company_unit_address',                count($cuaIds ?? []));
printRow($out, 'spy_company_role',                        count($crIds ?? []));
printRow($out, 'spy_customer',                            $custTotal);
printRow($out, 'spy_company_user',                        count($cuIds ?? []));
printRow($out, 'spy_company_role_to_company_user',        count($curRows ?? []));
printRow($out, 'spy_merchant_relationship',               count($mrIds ?? []));

$out->writeln("<info>$sep</info>");
$out->writeln('');
