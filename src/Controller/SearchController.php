<?php
/**
 * @package    contao-search-lite
 * @author     david thieme <david.thieme@code4you.net>
 * @copyright  code4you 2023
 * @license    GPL-3.0-or-later
 */

namespace C4Y\SearchLiteBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\SearchParameters;
use Loupe\Loupe\LoupeFactory;

/**
 * Class SearchController
 * 
 * API-Controller zur Durchführung von Suchoperationen und Rückgabe der Ergebnisse als JSON
 */
class SearchController extends AbstractController
{
    /**
     * @var string
     */
    private $projectDir;
    
    /**
     * @var string
     */
    private $environment;
    
    /**
     * @var string
     */
    private $cacheDir;
    
    /**
     * Constructor
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->projectDir = $kernel->getProjectDir();
        $this->environment = $kernel->getEnvironment();
        $this->cacheDir = $this->projectDir . '/var/cache/' . $this->environment . '/search_lite';
    }
    
    /**
     * Initialize and get the Loupe search engine instance
     *
     * @return Loupe
     */
    private function getLoupeEngine(): Loupe
    {
        $config = Configuration::create()
            ->withPrimaryKey('id')
            ->withSearchableAttributes(['title', 'content'])
            ->withFilterableAttributes(['tags'])
            ->withSortableAttributes(['title']);

        static $engine = null;
        if ($engine instanceof Loupe) {
            return $engine;
        }

        $engine = (new LoupeFactory())->create($this->cacheDir, $config);
        
        return $engine;
    }
    
    /**
     * Extracts context snippets around highlighted search terms
     * 
     * @param string $highlightedContent Content with highlighted terms in <em> tags
     * @param int $snippetSize Maximum snippet size
     * 
     * @return array Array of context snippets
     */
    private function extractContexts(string $highlightedContent, int $snippetSize = 200): array
    {
        // If no highlighted content, return an empty array
        if (empty($highlightedContent)) {
            return [];
        }
        
        // Split by highlight tags
        $parts = preg_split('/(<em>.*?<\/em>)/i', $highlightedContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $contexts = [];
        $currentContext = '';
        
        foreach ($parts as $part) {
            // If it's a highlighted part
            if (preg_match('/<em>(.*?)<\/em>/i', $part)) {
                $currentContext .= $part;
            } 
            // Regular text
            else {
                // Truncate long non-highlighted parts
                if (strlen($part) > $snippetSize * 2) {
                    // Keep first and last parts of long text
                    $currentContext .= substr($part, 0, $snippetSize) . '...' . substr($part, -$snippetSize);
                } else {
                    $currentContext .= $part;
                }
            }
            
            // If context is getting too long, start a new one
            if (strlen($currentContext) > $snippetSize * 3) {
                $contexts[] = $currentContext;
                $currentContext = '';
            }
        }
        
        // Add the last context if not empty
        if (!empty($currentContext)) {
            $contexts[] = $currentContext;
        }
        
        return $contexts;
    }
    
    /**
     * @Route("/search-lite/search", name="c4y_search_api")
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');
        
        // Wenn kein Query Parameter vorhanden ist, leere Ergebnisse zurückgeben
        if (empty($query)) {
            return new JsonResponse([
                'query' => '',
                'results' => [],
                'total_hits' => 0
            ]);
        }
        
        // Suche durchführen
        try {
            $engine = $this->getLoupeEngine();
            
            // Suche mit Hervorhebung konfigurieren
            $searchParams = SearchParameters::create()
                ->withQuery($query)
                ->withAttributesToHighlight(['title', 'content'], '<em>', '</em>');
                
            $searchResult = $engine->search($searchParams);
            $resultArray = $searchResult->toArray();
            
            // Für alle Treffer das Ergebnis aufbereiten
            $formattedResults = [];
            foreach ($resultArray['hits'] as $hit) {
                // Title mit Highlight extrahieren
                $title = isset($hit['_formatted']['title']) ? $hit['_formatted']['title'] : ($hit['title'] ?? 'Kein Titel');
                
                // Content mit Highlight und Kontext
                $contentHighlights = isset($hit['_formatted']['content']) ? $hit['_formatted']['content'] : '';
                $contexts = $this->extractContexts($contentHighlights);
                $combinedSnippet = implode('...', $contexts);
                
                // Ensure we have a valid score
                $score = isset($hit['_score']) ? (float)$hit['_score'] : 1.0;
                
                $formattedResults[] = [
                    'id' => $hit['id'],
                    'url' => $hit['url'] ?? '',
                    'title' => $title,
                    'content_snippet' => $combinedSnippet,
                    'score' => $score
                ];
            }
            
            // JSON-Antwort zurückgeben
            return new JsonResponse([
                'query' => $query,
                'results' => $formattedResults, 
                'total_hits' => $resultArray['totalHits'] ?? 0
            ]);
        } catch (\Exception $e) {
            // Bei Fehler entsprechende JSON-Fehlermeldung zurückgeben
            return new JsonResponse([
                'query' => $query,
                'results' => [], 
                'error' => 'Fehler bei der Suche: ' . $e->getMessage(),
                'total_hits' => 0
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}