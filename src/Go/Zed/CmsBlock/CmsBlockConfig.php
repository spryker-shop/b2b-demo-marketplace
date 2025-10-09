<?php

namespace Go\Zed\CmsBlock;

use Spryker\Shared\CmsBlock\CmsBlockConstants;
use Spryker\Shared\Kernel\KernelConstants;

class CmsBlockConfig extends \Spryker\Zed\CmsBlock\CmsBlockConfig
{
    /**
     * @api
     *
     * @param string $templateRelativePath
     *
     * @return array<string>
     */
    public function getTemplateRealPaths($templateRelativePath): array
    {
        $templatePaths = [];

        foreach ($this->getThemeNames() as $themeName) {
            foreach ($this->get(KernelConstants::PROJECT_NAMESPACES) as $namespace) {
                $templatePaths[] = $this->getAbsolutePath($templateRelativePath, 'Shared', $themeName, $namespace);
            }
        }

        return $templatePaths;
    }

    /**
     * @param string $templateRelativePath
     * @param string $twigLayer
     * @param string $themeName
     *
     * @return string
     */
    protected function getAbsolutePath(
        string $templateRelativePath,
        string $twigLayer,
        string $themeName = self::THEME_NAME_DEFAULT,
        string $projectNamespace = null,
    ): string {
        $templateRelativePath = str_replace(static::CMS_TWIG_TEMPLATE_PREFIX, '', $templateRelativePath);

        return sprintf(
            '%s/%s/%s/CmsBlock/Theme/%s%s',
            APPLICATION_SOURCE_DIR,
            $projectNamespace ?? $this->get(CmsBlockConstants::PROJECT_NAMESPACE),
            $twigLayer,
            $themeName,
            $templateRelativePath,
        );
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getCmsBlockTemplatePaths(): array
    {
        $templatePaths = [];
        foreach ($this->get(KernelConstants::PROJECT_NAMESPACES) as $namespace) {
            $templatePaths[] = sprintf(
                '%s/%s/Shared/CmsBlock/Theme/%s',
                APPLICATION_SOURCE_DIR,
                $namespace,
                static::THEME_NAME_DEFAULT,
            );
        }
        return $templatePaths;
    }
}
