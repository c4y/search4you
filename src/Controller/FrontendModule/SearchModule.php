<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you\Controller\FrontendModule;

use Contao\ModuleModel;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'application')]
class SearchModule extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $template->text = $model->text;
        $template->featuredCategory = $model->search4you_featured_category;
        $template->rootPage = $model->search4you_rootPage;
        $template->hideTags = $model->search4you_hide_tags;
        
        return $template->getResponse();
    }
}