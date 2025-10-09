<?php

namespace Go\Yves\Twig\Plugin;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Twig\Environment;

class ConvertVarToTemplateTwigFilter  extends AbstractPlugin implements TwigPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Twig\Environment $twig
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Twig\Environment
     */
    public function extend(Environment $twig, ContainerInterface $container): Environment
    {
        $filter = new \Twig\TwigFilter(
            'render_dynamic',
            function (array $context, string $string) use ($twig): string  {
                return $twig->createTemplate($string)->render($context);
            },
            ['is_safe' => ['html'], 'needs_context' => true]
        );

        $twig->addFilter($filter);

        return $twig;
    }
}
