<?php

namespace C4Y\SearchLiteBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;
use Symfony\Component\HttpKernel\KernelInterface;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;
use Contao\PageModel;
use Codefog\TagsBundle\Manager\DefaultManager;

#[AsHook('indexPage')]
class IndexPageListener
{
    /**
     * @var string
     */
    private $cacheDir;
    
    /**
     * @var DefaultManager
     */
    private $tagsManager;
    
    /**
     * IndexPageListener constructor.
     * 
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $projectDir = $kernel->getProjectDir();
        $environment = $kernel->getEnvironment();
        $this->cacheDir = $projectDir . '/var/cache/' . $environment . '/search_lite';
        $this->tagsManager = $kernel->getContainer()->get('codefog_tags.manager.search_lite_manager');
    }
    
    public function __invoke(string $content, array $pageData, array &$indexData): void
    {

        $this->addWebpageToLoupe($content, $indexData);
    }

    // Hilfsfunktion zum Initialisieren von Loupe
    private function getLoupeEngine(): Loupe
    {
        $config = Configuration::create()
            ->withPrimaryKey('id')
            ->withSearchableAttributes(['title', 'content'])
            ->withFilterableAttributes(['title'])
            ->withSortableAttributes(['title']);

        return (new LoupeFactory())->create($this->cacheDir, $config);
    }

    // Hilfsfunktion zum Hinzufügen einer Webseite zu Loupe
    function addWebpageToLoupe(string $content, array $indexData): bool
    {
        $engine = $this->getLoupeEngine();

        $criteria = $this->tagsManager->createTagCriteria()->setSourceIds([$indexData['pid']]);
        $tags = $this->tagsManager->getTagFinder()->findMultiple($criteria);
        $tagNames = array_map(function ($tag) {
            return $tag->getName();
        }, $tags);

        $document = [
            'id' => $indexData['pid'],
            'url' => $indexData['url'],
            'title' => $indexData['title'],
            'tags' => $tagNames,
            'content' => $this->cleanHtml($content)
        ];

        file_put_contents($this->cacheDir . '/index.log', sprintf('Indexing document: %s (%s)', $document['id'], $document['title']) . "\n", FILE_APPEND);
        
        // Dokument neu hinzufügen (Upsert)
        $engine->addDocument($document);
        
        file_put_contents($this->cacheDir . '/index.log', sprintf('Document indexed. Total documents: %d', $engine->countDocuments()) . "\n", FILE_APPEND);

        return true;
    }

    private function cleanHtml(string $html): string
    {
        // Ersetze Bilder durch ihre Alt-Texte, falls vorhanden
        $html = preg_replace('/<img\b[^>]*alt=["\']([^"\']*)["\'](.*?)>/i', '[Bild: $1]', $html);
        // Entferne Bilder ohne Alt-Text
        $html = preg_replace('/<img\b[^>]*>/i', '[Bild]', $html);

        // Add a whitespace character before line-breaks and between consecutive tags (see Contao #5363)
        $html = str_ireplace(array('<br', '><'), array(' <br', '> <'), $html);

        // Ersetze bestimmte Block-Elemente durch Zeilenumbrüche
        $html = preg_replace('/<\/?(?:div|p|h[1-6]|br|li|ul|ol|table|tr)[^>]*>/i', "\n", $html);

        // Entferne alle verbliebenen HTML-Tags
        $html = strip_tags($html);

        // Bereinige Whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/\n\s*\n/', "\n\n", $html);

        return $html;
    }
}