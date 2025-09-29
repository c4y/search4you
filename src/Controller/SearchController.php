<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

namespace C4Y\Search4you\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\SearchParameters;
use Loupe\Loupe\LoupeFactory;
use C4Y\Search4you\Service\LoupeEngineFactory;

/**
 * Class SearchController
 * 
 * API-Controller zur Durchführung von Suchoperationen und Rückgabe der Ergebnisse als JSON
 */
class SearchController extends AbstractController
{
    /**
     * @var LoupeEngineFactory
     */
    private $loupeEngineFactory;
    
    /**
     * Constructor
     */
    public function __construct(LoupeEngineFactory $loupeEngineFactory)
    {
        $this->loupeEngineFactory = $loupeEngineFactory;
    }
    
    /**
     * Initialize and get the Loupe search engine instance
     *
     * @return Loupe
     */
    private function getLoupeEngine(): Loupe
    {
        return $this->loupeEngineFactory->getLoupeEngine();
    }
    
    /**
     * Extracts context snippets around highlighted search terms
     * 
     * @param string $highlightedContent Content with highlighted terms in <em> tags
     * @param int $snippetSize Maximum snippet size
     * 
     * @return array Array of context snippets
     */
    private function extractContexts(string $highlightedContent, int $snippetSize = 50): array
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
     * Ensure string is properly UTF-8 encoded
     * 
     * @param mixed $data The data to clean
     * @return mixed The cleaned data
     */
    private function ensureUtf8($data)
    {
        if (is_string($data)) {
            // Remove any invalid UTF-8 characters
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } elseif (is_array($data)) {
            return array_map([$this, 'ensureUtf8'], $data);
        }
        return $data;
    }

    /**
     * @return JsonResponse
     */
    #[Route('/search4you/search', name: 'c4y_search_api')]
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');
        $tagsFilter = trim($request->query->get('tags', ''));
        $categoryFilter = trim($request->query->get('category', ''));
        $rootPage = $request->query->get('rootPage', '');
        $featuredCategory = $request->query->get('featuredCategory', '');
        
        // Wenn weder Query noch Tag vorhanden ist, leere Ergebnisse zurückgeben
        if (empty($query) && empty($tagsFilter) && empty($categoryFilter)) {
            return new JsonResponse([
                'query' => $query,
                'results' => [],
                'total_hits' => 0,
                'tags' => []
            ]);
        }
        
        // Suche durchführen
        try {
            $engine = $this->getLoupeEngine();

            // Suche mit Hervorhebung konfigurieren
            // Verwende search für die Suche, aber content für die Anzeige
            $searchParams = SearchParameters::create()
                ->withQuery($query)
                ->withAttributesToSearchOn(['title', 'search'])  // Explicitly set searchable fields
                ->withAttributesToHighlight(['title', 'search'], '<em>', '</em>')
                ->withSort(['is_featured:desc', 'sorting:asc']);

            // nur in einem Seitenbaum und featured Items einer Kategorie
            $filter = "(origin='page' AND root=" . $rootPage . ") OR (origin='featured' AND root=" . $featuredCategory . ")";
                
            // Tag-Filter anwenden, falls vorhanden
            if (!empty($tagsFilter)) {
                $filter .= " AND tags = '" . $tagsFilter . "'";
            }

            // Kategorie-Filter anwenden, falls vorhanden
            if (!empty($categoryFilter)) {
                $filter .= " AND category = '" . $categoryFilter . "'";
            }   

            $searchParams = $searchParams->withFilter($filter);
            $searchResult = $engine->search($searchParams);
            $resultArray = $searchResult->toArray();
            
            // Für alle Treffer das Ergebnis aufbereiten und Tags sammeln
            $formattedResults = [];
            $allTags = [];
            $allCategories = [];
            
            foreach ($resultArray['hits'] as $hit) {
                $isFeatured = $hit['is_featured'] ?? false;
                
                // Title mit Highlight extrahieren
                $title = isset($hit['_formatted']['title']) ? $hit['_formatted']['title'] : ($hit['title'] ?? 'Kein Titel');
                
                // Für featured Items keinen Kontext extrahieren
                $combinedSnippet = '';
                if (!$isFeatured) {
                    // Nur für nicht-featured Items Kontext extrahieren
                    $contentHighlights = isset($hit['_formatted']['search']) ? $hit['_formatted']['search'] : '';
                    $contexts = $this->extractContexts($contentHighlights);
                    $combinedSnippet = implode('...', $contexts);
                }
                
                // Ensure we have a valid score
                $score = isset($hit['_score']) ? (float)$hit['_score'] : 1.0;
                
                // Tags und Kategorien nur für nicht-featured Items verarbeiten
                $tags = [];
                if (!$isFeatured) {
                    $tags = $hit['tags'] ?? [];
                    if (!empty($tags) && is_string($tags)) {
                        $tags = array_map('trim', explode(',', $tags));
                    }
                    if (!empty($tags) && is_array($tags)) {
                        $allTags = array_merge($allTags, $tags);
                    }
                    
                    // Kategorie aus dem Hit extrahieren, falls vorhanden
                    $category = $hit['category'] ?? '';
                    if (!empty($category)) {
                        $allCategories[] = $category;
                    }
                } else {
                    $combinedSnippet = $hit['content'];
                }
                
                $formattedResults[] = [
                    'id' => $hit['id'],
                    'url' => $hit['url'] ?? '',
                    'title' => $title,
                    'origin' => $hit['origin'] ?? '',
                    'root' => $hit['root'] ?? '',
                    'sorting' => $hit['sorting'] ?? '',
                    'content_snippet' => $combinedSnippet,
                    'score' => $score,
                    'category' => $hit['category'] ?? '',
                    'tags' => is_array($tags) ? $tags : [],
                    'is_featured' => $isFeatured,
                    'css' => $hit['css'] ?? ''
                ];
            }
            
            // Doppelte Tags und Kategorien entfernen und leere Einträge filtern
            $uniqueTags = array_values(array_unique(array_filter(array_map('trim', $allTags))));
            $uniqueCategories = array_values(array_unique(array_filter(array_map('trim', $allCategories))));
            
            // JSON-Antwort vorbereiten und UTF-8 sicherstellen
            $responseData = [
                'query' => $query,
                'filter_tags' => $tagsFilter ?: null,
                'results' => $this->ensureUtf8($formattedResults), 
                'total_hits' => $resultArray['totalHits'] ?? 0,
                'tags' => $this->ensureUtf8($uniqueTags),
                'categories' => $this->ensureUtf8($uniqueCategories)
            ];
            
            // JSON-Antwort mit korrekter Kodierung zurückgeben
            $response = new JsonResponse($responseData);
            $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_INVALID_UTF8_SUBSTITUTE
            );
            return $response;
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