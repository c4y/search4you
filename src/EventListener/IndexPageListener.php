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
     * @var DefaultManager
     */
    private $categoryManager;
    
    /**
     * IndexPageListener constructor.
     * 
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $projectDir = $kernel->getProjectDir();
        $environment = $kernel->getEnvironment();
        $this->cacheDir = $projectDir . '/var/search_lite';
        $this->tagsManager = $kernel->getContainer()->get('codefog_tags.manager.search_lite_tags_manager');
        $this->categoryManager = $kernel->getContainer()->get('codefog_tags.manager.search_lite_category_manager');
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
            ->withLanguages(['de', 'fr'])
            ->withFilterableAttributes(['tags', 'category']);

        return (new LoupeFactory())->create($this->cacheDir, $config);
    }

    // Hilfsfunktion zum Hinzufügen einer Webseite zu Loupe
    function addWebpageToLoupe(string $content, array $indexData): bool
    {
        $engine = $this->getLoupeEngine();

        $tagsCriteria = $this->tagsManager->createTagCriteria()->setSourceIds([$indexData['pid']]);
        $tags = $this->tagsManager->getTagFinder()->findMultiple($tagsCriteria);

        $categoryCriteria = $this->categoryManager->createTagCriteria()->setSourceIds([$indexData['pid']]);
        $category = $this->categoryManager->getTagFinder()->findMultiple($categoryCriteria);
        
        $tagNames = array_map(function ($tag) {
            return $tag->getName();
        }, $tags);

        $cleanContent = $this->cleanHtml($content);
        $document = [
            'id' => $indexData['pid'],
            'url' => $indexData['url'],
            'title' => $indexData['title'],
            'category' => $category[0]->getName(),
            'tags' => $tagNames,
            'content' => $cleanContent,
            'search' => $this->removeStopwords($cleanContent)
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $engine->addDocument($document);

        return true;
    }

    private function removeStopwords(string $text): string
    {
        static $stopwords = null;
        
        if ($stopwords === null) {
            $stopwordsFile = __DIR__.'/../../stopwords/stopwords-de.json';
            if (file_exists($stopwordsFile)) {
                $stopwords = json_decode(file_get_contents($stopwordsFile), true) ?: [];
            } else {
                $stopwords = [];
            }
        }
        
        if (empty($stopwords)) {
            return $text;
        }
        
        // Erstelle ein Muster für alle Stoppwörter
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $stopwords)) . ')\b/iu';
        
        // Ersetze Stoppwörter durch Leerzeichen und normalisiere Leerzeichen
        $text = preg_replace($pattern, ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    private function cleanHtml(string $html): string
    {
        // Entferne den Titel
        $html = preg_replace('/<title>(.*?)<\/title>/i', '', $html);

        // Ersetze Bilder durch ihre Alt-Texte, falls vorhanden
        $html = preg_replace('/<img\b[^>]*alt=[\'"]([^\'"]*)[\'"](.*?)>/i', '[Bild: $1]', $html);

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