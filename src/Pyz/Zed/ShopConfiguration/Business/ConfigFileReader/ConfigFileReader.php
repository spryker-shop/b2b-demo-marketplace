<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigFileReader;

use Generated\Shared\Transfer\ShopConfigurationFileDataTransfer;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser;
use Pyz\Zed\ShopConfiguration\ShopConfigurationConfig;

class ConfigFileReader implements ConfigFileReaderInterface
{
    /**
     * @var \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig
     */
    protected ShopConfigurationConfig $config;

    /**
     * @var \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser
     */
    protected YamlParser $yamlParser;

    /**
     * @var \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser
     */
    protected XmlParser $xmlParser;

    /**
     * @var \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser
     */
    protected JsonParser $jsonParser;

    /**
     * @param \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig $config
     * @param \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser $yamlParser
     * @param \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser $xmlParser
     * @param \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser $jsonParser
     */
    public function __construct(
        ShopConfigurationConfig $config,
        YamlParser $yamlParser,
        XmlParser $xmlParser,
        JsonParser $jsonParser
    ) {
        $this->config = $config;
        $this->yamlParser = $yamlParser;
        $this->xmlParser = $xmlParser;
        $this->jsonParser = $jsonParser;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer>
     */
    public function readAllConfigurationFiles(): array
    {
        $fileDataTransfers = [];
        $discoveredFiles = $this->discoverConfigurationFiles();

        foreach ($discoveredFiles as $fileInfo) {
            try {
                $fileDataTransfer = $this->parseConfigurationFile($fileInfo);
                if ($fileDataTransfer !== null) {
                    $fileDataTransfers[] = $fileDataTransfer;
                }
            } catch (\Exception $e) {
                // Log error and continue with other files
                error_log(sprintf(
                    'Failed to parse configuration file %s: %s',
                    $fileInfo['path'],
                    $e->getMessage()
                ));
            }
        }

        return $fileDataTransfers;
    }

    /**
     * @param string $module
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer>
     */
    public function readConfigurationFilesForModule(string $module): array
    {
        $allFiles = $this->readAllConfigurationFiles();

        return array_filter($allFiles, function (ShopConfigurationFileDataTransfer $fileData) use ($module) {
            return $fileData->getModule() === $module;
        });
    }

    /**
     * @return array<array>
     */
    protected function discoverConfigurationFiles(): array
    {
        $files = [];
        $basePath = APPLICATION_ROOT_DIR . '/src';
        $discoveryPaths = $this->config->getDiscoveryPaths();
        $supportedExtensions = $this->config->getSupportedFileExtensions();

        foreach ($discoveryPaths as $pathPattern) {
            $files = array_merge($files, $this->scanDirectoriesForFiles($basePath, $pathPattern, $supportedExtensions));
        }

        return $files;
    }

    /**
     * @param string $basePath
     * @param string $pathPattern
     * @param array<string> $supportedExtensions
     *
     * @return array<array>
     */
    protected function scanDirectoriesForFiles(string $basePath, string $pathPattern, array $supportedExtensions): array
    {
        $files = [];
        $namespaces = ['Pyz', 'Spryker', 'SprykerShop', 'SprykerEco'];

        foreach ($namespaces as $namespace) {
            $scanPath = str_replace('{namespace}', $namespace, $pathPattern);
            $fullScanPath = $basePath . '/' . $scanPath;

            if (strpos($scanPath, '{module}') !== false) {
                $files = array_merge($files, $this->scanForModuleDirectories($basePath, $scanPath, $supportedExtensions));
            } else {
                $files = array_merge($files, $this->scanDirectoryForFiles($fullScanPath, $supportedExtensions));
            }
        }

        return $files;
    }

    /**
     * @param string $basePath
     * @param string $pathPattern
     * @param array<string> $supportedExtensions
     *
     * @return array<array>
     */
    protected function scanForModuleDirectories(string $basePath, string $pathPattern, array $supportedExtensions): array
    {
        $files = [];
        $pathParts = explode('/{module}/', $pathPattern);
        $beforeModule = $pathParts[0];
        $afterModule = $pathParts[1] ?? '';

        $modulesBasePath = $basePath . '/' . $beforeModule;
        if (!is_dir($modulesBasePath)) {
            return $files;
        }

        $modules = scandir($modulesBasePath);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $modulePath = $modulesBasePath . '/' . $module;
            if (!is_dir($modulePath)) {
                continue;
            }

            $configPath = $modulePath . '/' . $afterModule;
            if (is_dir($configPath)) {
                $moduleFiles = $this->scanDirectoryForFiles($configPath, $supportedExtensions);
                foreach ($moduleFiles as $fileInfo) {
                    $fileInfo['module'] = $module;
                    $files[] = $fileInfo;
                }
            }
        }

        return $files;
    }

    /**
     * @param string $directory
     * @param array<string> $supportedExtensions
     *
     * @return array<array>
     */
    protected function scanDirectoryForFiles(string $directory, array $supportedExtensions): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $supportedExtensions)) {
                    $files[] = [
                        'path' => $file->getPathname(),
                        'extension' => $extension,
                        'module' => $this->extractModuleFromPath($file->getPathname()),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    protected function extractModuleFromPath(string $filePath): string
    {
        // Extract module name from path like /src/Pyz/Shared/ModuleName/...
        $pathParts = explode('/', $filePath);
        $srcIndex = array_search('src', $pathParts);
        
        if ($srcIndex !== false && isset($pathParts[$srcIndex + 3])) {
            return $pathParts[$srcIndex + 3];
        }

        return 'Unknown';
    }

    /**
     * @param array $fileInfo
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationFileDataTransfer|null
     */
    protected function parseConfigurationFile(array $fileInfo): ?ShopConfigurationFileDataTransfer
    {
        $content = file_get_contents($fileInfo['path']);
        if ($content === false) {
            return null;
        }

        $parser = $this->getParserForExtension($fileInfo['extension']);
        if ($parser === null) {
            return null;
        }

        $data = $parser->parse($content);

        return (new ShopConfigurationFileDataTransfer())
            ->setModule($fileInfo['module'])
            ->setFilePath($fileInfo['path'])
            ->setFormat($fileInfo['extension'])
            ->setData($data);
    }

    /**
     * @param string $extension
     *
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\ParserInterface|null
     */
    protected function getParserForExtension(string $extension): ?\Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\ParserInterface
    {
        if ($this->yamlParser->supports($extension)) {
            return $this->yamlParser;
        }

        if ($this->xmlParser->supports($extension)) {
            return $this->xmlParser;
        }

        if ($this->jsonParser->supports($extension)) {
            return $this->jsonParser;
        }

        return null;
    }
}
