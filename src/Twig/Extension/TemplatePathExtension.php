<?php

namespace C4Y\SearchLiteBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to add template path functionality to Twig
 */
class TemplatePathExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('search_lite_template_path', [$this, 'getTemplatePath']),
        ];
    }
    
    /**
     * Returns the path to a template in the bundle
     *
     * @param string $templateName Name of the template
     * @return string Path to the template
     */
    public function getTemplatePath(string $templateName): string
    {
        return dirname(__DIR__, 3) . '/templates/' . $templateName;
    }
}
