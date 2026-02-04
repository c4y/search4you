<?php
/**
 * @package    contao-search-lite
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\SearchLiteBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\HttpKernel\KernelInterface;
use Contao\PageModel;
use Codefog\TagsBundle\Manager\DefaultManager;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;

#[AsHook('indexPage')]
class IndexPageListener
{
    /**
     * @var DefaultManager
     */
    private $tagsManager;
    
    /**
     * @var DefaultManager
     */
    private $categoryManager;
    
    /**
     * @var LoupeEngineFactory
     */
    private $loupeEngineFactory;

    /**
     * @var InsertTagParser
     */
    private $insertTagParser;
    
    /**
     * IndexPageListener constructor.
     */
    public function __construct(KernelInterface $kernel, LoupeEngineFactory $loupeEngineFactory, InsertTagParser $insertTagParser) {
        $this->tagsManager = $kernel->getContainer()->get('codefog_tags.manager.search_lite_tags_manager');
        $this->categoryManager = $kernel->getContainer()->get('codefog_tags.manager.search_lite_category_manager');
        $this->loupeEngineFactory = $loupeEngineFactory;
        $this->insertTagParser = $insertTagParser;
    }
    
    public function __invoke(string $content, array $pageData, array &$indexData): void
    {
        $content = $this->insertTagParser->replaceInline($content);
        $this->addWebpageToLoupe($content, $indexData);
    }

    // Hilfsfunktion zum Hinzufügen einer Webseite zu Loupe
    function addWebpageToLoupe(string $content, array $indexData): bool
    {
        $engine = $this->loupeEngineFactory->getLoupeEngine();

        $tagsCriteria = $this->tagsManager->createTagCriteria()->setSourceIds([$indexData['pid']]);
        $tags = $this->tagsManager->getTagFinder()->findMultiple($tagsCriteria);

        $categoryCriteria = $this->categoryManager->createTagCriteria()->setSourceIds([$indexData['pid']]);
        $category = $this->categoryManager->getTagFinder()->findMultiple($categoryCriteria);
        
        $tagNames = array_map(function ($tag) {
            return $tag->getName();
        }, $tags);

        $objPage = PageModel::findByPk($indexData['pid']);
        $objPage->loadDetails();
        
        $cleanContent = $this->cleanHtml($content);
        $document = [
            'id' => 'page-' . $indexData['pid'],
            'is_featured' => false,
            'origin' => 'page',
            'root' => $objPage->rootId, 
            'sorting' => null,
            'url' => $indexData['url'],
            'title' => $indexData['title'],
            'category' => $category[0] === null ? '' : $category[0]->getName(),
            'tags' => $tagNames,
            'content' => $cleanContent,
            'search' => $this->removeStopwords($cleanContent)
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $result = $engine->addDocument($document);

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

        // Entferne nicht zu indexierende Bereiche (Contao Standard)
        while (($intStart = strpos($html, '<!-- indexer::stop -->')) !== false) {
            if (($intEnd = strpos($html, '<!-- indexer::continue -->', $intStart)) !== false) {
                $intCurrent = $intStart;

                // Handle nested tags
                while (($intNested = strpos($html, '<!-- indexer::stop -->', $intCurrent + 22)) !== false && $intNested < $intEnd) {
                    if (($intNewEnd = strpos($html, '<!-- indexer::continue -->', $intEnd + 26)) !== false) {
                        $intEnd = $intNewEnd;
                        $intCurrent = $intNested;
                    } else {
                        break;
                    }
                }

                $html = substr($html, 0, $intStart) . substr($html, $intEnd + 26);
            } else {
                break;
            }
        }

        // Nur den Body-Bereich indexieren, falls vorhanden
        if (preg_match('/<\/head>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = strlen($matches[0][0]) + $matches[0][1];
            $html = substr($html, $offset);
        }

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