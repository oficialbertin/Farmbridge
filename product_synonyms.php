<?php
/**
 * Comprehensive Product Synonym and Fuzzy Matching System
 * Handles misspellings, alternative names, local languages, and variations
 */

class ProductMatcher {
    private $synonyms = [];
    private $fuzzy_threshold = 0.7;
    
    public function __construct() {
        $this->loadSynonyms();
    }
    
    private function loadSynonyms() {
        $this->synonyms = [
            // COMMON LEAFY GREENS (added)
            'kale' => [
                'exact_matches' => ['kale'],
                'kinyarwanda' => ['sukuma', 'isukari ya mboga'],
                'swahili' => ['sukuma wiki'],
                'misspellings' => ['kalle','kales'],
                'variations' => ['collard greens']
            ],
            'lettuce' => [
                'exact_matches' => ['lettuce'],
                'kinyarwanda' => ['letisi'],
                'swahili' => ['saladi'],
                'misspellings' => ['lettice'],
                'variations' => ['romaine','iceberg','butterhead']
            ],
            // POTATOES
            'potato' => [
                'exact_matches' => ['potato', 'potatoes', 'irish potato', 'irish potatoes', 'white potato', 'white potatoes'],
                'kinyarwanda' => ['ibirayi', 'ibirayi byo mu misozi'],
                'swahili' => ['viazi', 'viazi vya kienyeji'],
                'misspellings' => ['potatos', 'patatoes', 'patatos', 'potatoe', 'potato\'s', 'irish potatos', 'irish patatoes', 'irish patatos', 'irish potatoe', 'irish potato\'s'],
                'variations' => ['kinigi', 'spud', 'tater']
            ],
            
            // SWEET POTATOES
            'sweet potato' => [
                'exact_matches' => ['sweet potato', 'sweet potatoes'],
                'kinyarwanda' => ['ibijumba', 'ibijumba by\'umutuku', 'ibijumba by\'umweru'],
                'swahili' => ['viazi vitamu', 'viazi vya sukari'],
                'misspellings' => ['sweet potatos', 'sweet patatoes', 'sweet patatos', 'sweet potatoe', 'sweet potato\'s'],
                'variations' => ['batata', 'yam', 'kumara']
            ],
            
            // TOMATOES
            'tomato' => [
                'exact_matches' => ['tomato', 'tomatoes'],
                'kinyarwanda' => ['inyanya', 'inyanya z\'umutuku'],
                'swahili' => ['nyanya', 'nyanya nyekundu'],
                'misspellings' => ['tomatoe', 'tomato\'s', 'tomatos', 'tamato', 'tamatoes'],
                'variations' => ['love apple', 'golden apple']
            ],
            
            // ONIONS
            'onion' => [
                'exact_matches' => ['onion', 'onions', 'bulb onion'],
                'kinyarwanda' => ['itunguru', 'itunguru rya nyirabwoba'],
                'swahili' => ['kitunguu', 'kitunguu cha kizungu'],
                'misspellings' => ['onion\'s', 'onions\'', 'onionn', 'onionns'],
                'variations' => ['red onion', 'white onion', 'yellow onion', 'purple onion']
            ],
            
            // MAIZE/CORN
            'maize' => [
                'exact_matches' => ['maize', 'corn', 'sweet corn'],
                'kinyarwanda' => ['igikeri', 'igikeri cy\'umwuka'],
                'swahili' => ['mahindi', 'mahindi ya sukari'],
                'misspellings' => ['maze', 'mazie', 'corne', 'corn\'s'],
                'variations' => ['field corn', 'dent corn', 'flint corn']
            ],
            
            // BANANAS
            'banana' => [
                'exact_matches' => ['banana', 'bananas'],
                'kinyarwanda' => ['umuneke', 'umuneke w\'umweru'],
                'swahili' => ['ndizi', 'ndizi ya kizungu'],
                'misspellings' => ['bananna', 'banana\'s', 'bananas\'', 'banane'],
                'variations' => ['plantain', 'cooking banana', 'dessert banana']
            ],
            
            // BEANS
            'bean' => [
                'exact_matches' => ['bean', 'beans'],
                'kinyarwanda' => ['ubwishyimbo', 'ubwishyimbo bw\'umutuku'],
                'swahili' => ['maharagwe', 'maharagwe ya kizungu'],
                'misspellings' => ['bean\'s', 'beans\'', 'beane', 'beanes'],
                'variations' => ['kidney bean', 'pinto bean', 'black bean', 'navy bean']
            ],
            
            // RICE
            'rice' => [
                'exact_matches' => ['rice'],
                'kinyarwanda' => ['umuceri', 'umuceri w\'umweru'],
                'swahili' => ['mchele', 'mchele wa kizungu'],
                'misspellings' => ['rice\'s', 'ryce', 'ryce\'s'],
                'variations' => ['white rice', 'brown rice', 'basmati rice', 'jasmine rice']
            ],
            
            // CASSAVA
            'cassava' => [
                'exact_matches' => ['cassava', 'manioc', 'yuca'],
                'kinyarwanda' => ['imyumbati', 'imyumbati y\'umweru'],
                'swahili' => ['muhogo', 'muhogo wa kizungu'],
                'misspellings' => ['cassava\'s', 'cassavva', 'cassavaa', 'maniac'],
                'variations' => ['tapioca', 'mandioca']
            ],
            
            // CARROTS
            'carrot' => [
                'exact_matches' => ['carrot', 'carrots'],
                'kinyarwanda' => ['karoti', 'karoti z\'umutuku'],
                'swahili' => ['karoti', 'karoti ya kizungu'],
                'misspellings' => ['carrot\'s', 'carrots\'', 'carotte', 'carottes'],
                'variations' => ['orange carrot', 'purple carrot', 'white carrot']
            ],
            
            // CABBAGE
            'cabbage' => [
                'exact_matches' => ['cabbage', 'cabbages'],
                'kinyarwanda' => ['ishu', 'ishu rya kizungu'],
                'swahili' => ['koliflawa', 'koliflawa ya kizungu'],
                'misspellings' => ['cabbage\'s', 'cabbages\'', 'cabage', 'cabages'],
                'variations' => ['green cabbage', 'red cabbage', 'savoy cabbage']
            ],
            
            // SPINACH
            'spinach' => [
                'exact_matches' => ['spinach'],
                'kinyarwanda' => ['isupinashi', 'isupinashi ya kizungu'],
                'swahili' => ['spinachi', 'spinachi ya kizungu'],
                'misspellings' => ['spinach\'s', 'spinage', 'spinage\'s', 'spinnach'],
                'variations' => ['baby spinach', 'malabar spinach']
            ],
            
            // APPLES
            'apple' => [
                'exact_matches' => ['apple', 'apples'],
                'kinyarwanda' => ['ipome','pome', 'ipome ya kizungu'],
                'swahili' => ['tufaha', 'tufaha ya kizungu'],
                'misspellings' => ['apple\'s', 'apples\'', 'appel', 'appels'],
                'variations' => ['red apple', 'green apple', 'golden apple']
            ],
            
            // ORANGES
            'orange' => [
                'exact_matches' => ['orange', 'oranges'],
                'kinyarwanda' => ['oranj', 'ironji', 'oranj ya kizungu'],
                'swahili' => ['machungwa', 'machungwa ya kizungu'],
                'misspellings' => ['orange\'s', 'oranges\'', 'oragne', 'oragnes'],
                'variations' => ['sweet orange', 'bitter orange', 'blood orange']
            ],
            
            // MANGOES
            'mango' => [
                'exact_matches' => ['mango', 'mangoes', 'mangos'],
                'kinyarwanda' => ['umwembe', 'umwembe w\'umutuku'],
                'swahili' => ['embe', 'embe la kizungu'],
                'misspellings' => ['mango\'s', 'mangoes\'', 'mangoe', 'mangos\''],
                'variations' => ['green mango', 'ripe mango', 'sweet mango']
            ],
            
            // PINEAPPLES
            'pineapple' => [
                'exact_matches' => ['pineapple', 'pineapples'],
                'kinyarwanda' => ['inanasi', 'inanasi ya kizungu'],
                'swahili' => ['nanasi', 'nanasi ya kizungu'],
                'misspellings' => ['pineapple\'s', 'pineapples\'', 'pineaple', 'pineaple\'s'],
                'variations' => ['fresh pineapple', 'sweet pineapple']
            ],
            
            // AVOCADOS
            'avocado' => [
                'exact_matches' => ['avocado', 'avocados'],
                'kinyarwanda' => ['avoka', 'ivoka', 'avoka ya kizungu'],
                'swahili' => ['parachichi', 'parachichi ya kizungu'],
                'misspellings' => ['avocado\'s', 'avocados\'', 'avacado', 'avacados'],
                'variations' => ['hass avocado', 'fuerte avocado']
            ],
            
            // COFFEE
            'coffee' => [
                'exact_matches' => ['coffee'],
                'kinyarwanda' => ['ikawa', 'ikawa ya kizungu'],
                'swahili' => ['kahawa', 'kahawa ya kizungu'],
                'misspellings' => ['coffee\'s', 'coffe', 'coffe\'s', 'cofee'],
                'variations' => ['arabica coffee', 'robusta coffee', 'green coffee']
            ],
            
            // TEA
            'tea' => [
                'exact_matches' => ['tea'],
                'kinyarwanda' => ['icayi', 'icayi ya kizungu'],
                'swahili' => ['chai', 'chai ya kizungu'],
                'misspellings' => ['tea\'s', 'te', 'te\'s', 'teea'],
                'variations' => ['black tea', 'green tea', 'herbal tea']
            ],
            
            // EGGPLANT
            'eggplant' => [
                'exact_matches' => ['eggplant', 'eggplants', 'aubergine', 'brinjal'],
                'kinyarwanda' => ['intoryi', 'intoryi ya kizungu'],
                'swahili' => ['biringani', 'biringani ya kizungu'],
                'misspellings' => ['eggplant\'s', 'eggplants\'', 'eggplante', 'eggplantes'],
                'variations' => ['purple eggplant', 'white eggplant']
            ],
            
            // CUCUMBER
            'cucumber' => [
                'exact_matches' => ['cucumber', 'cucumbers'],
                'kinyarwanda' => ['konkombere', 'konkombere ya kizungu'],
                'swahili' => ['tango', 'tango la kizungu'],
                'misspellings' => ['cucumber\'s', 'cucumbers\'', 'cucumbre', 'cucumbres'],
                'variations' => ['english cucumber', 'pickling cucumber']
            ],
            
            // BELL PEPPER
            'bell pepper' => [
                'exact_matches' => ['bell pepper', 'bell peppers', 'pepper', 'peppers', 'capsicum', 'sweet pepper'],
                'kinyarwanda' => ['urubibi', 'urubibi rwa kizungu'],
                'swahili' => ['pilipili hoho', 'pilipili hoho ya kizungu'],
                'misspellings' => ['bell pepper\'s', 'bell peppers\'', 'bel pepper', 'bel peppers'],
                'variations' => ['red pepper', 'green pepper', 'yellow pepper', 'orange pepper']
            ],
            
            // GARLIC
            'garlic' => [
                'exact_matches' => ['garlic'],
                'kinyarwanda' => ['itunguru rya kizungu', 'itunguru rya nyirabwoba'],
                'swahili' => ['kitunguu saumu', 'kitunguu saumu cha kizungu'],
                'misspellings' => ['garlic\'s', 'garlik', 'garlik\'s', 'garlicc'],
                'variations' => ['fresh garlic', 'dried garlic']
            ],
            
            // GINGER
            'ginger' => [
                'exact_matches' => ['ginger'],
                'kinyarwanda' => ['tangawizi', 'tangawizi ya kizungu'],
                'swahili' => ['tangawizi', 'tangawizi ya kizungu'],
                'misspellings' => ['ginger\'s', 'gingre', 'gingre\'s', 'gingerr'],
                'variations' => ['fresh ginger', 'dried ginger', 'ginger root']
            ],
            
            // LEMON
            'lemon' => [
                'exact_matches' => ['lemon', 'lemons'],
                'kinyarwanda' => ['indimu', 'indimu ya kizungu'],
                'swahili' => ['limau', 'limau la kizungu'],
                'misspellings' => ['lemon\'s', 'lemons\'', 'lemonn', 'lemonns'],
                'variations' => ['fresh lemon', 'sour lemon']
            ],
            
            // LIME
            'lime' => [
                'exact_matches' => ['lime', 'limes'],
                'kinyarwanda' => ['indimu y\'umutuku', 'indimu ya kizungu'],
                'swahili' => ['ndimu', 'ndimu ya kizungu'],
                'misspellings' => ['lime\'s', 'limes\'', 'lim', 'lim\'s'],
                'variations' => ['key lime', 'persian lime']
            ],
            
            // WHEAT
            'wheat' => [
                'exact_matches' => ['wheat'],
                'kinyarwanda' => ['ingano', 'ingano ya kizungu'],
                'swahili' => ['ngano', 'ngano ya kizungu'],
                'misspellings' => ['wheat\'s', 'wheet', 'wheet\'s', 'wheatt'],
                'variations' => ['winter wheat', 'spring wheat', 'durum wheat']
            ],
            
            // SORGHUM
            'sorghum' => [
                'exact_matches' => ['sorghum', 'milo'],
                'kinyarwanda' => ['amashya', 'amashya ya kizungu'],
                'swahili' => ['mtama', 'mtama wa kizungu'],
                'misspellings' => ['sorghum\'s', 'sorgham', 'sorgham\'s', 'sorghumm'],
                'variations' => ['grain sorghum', 'sweet sorghum']
            ]
        ];
    }
    
    /**
     * Find the best matching product for a given input
     */
    public function findProduct($input) {
        $input = strtolower(trim($input));
        
        // First try exact matches
        $exact_match = $this->findExactMatch($input);
        if ($exact_match) {
            return [
                'product' => $exact_match,
                'confidence' => 1.0,
                'match_type' => 'exact'
            ];
        }
        
        // Try fuzzy matching
        $fuzzy_match = $this->findFuzzyMatch($input);
        if ($fuzzy_match) {
            return $fuzzy_match;
        }
        
        // Try partial matching
        $partial_match = $this->findPartialMatch($input);
        if ($partial_match) {
            return $partial_match;
        }
        
        return null;
    }
    
    /**
     * Find exact matches in all synonym categories
     */
    private function findExactMatch($input) {
        foreach ($this->synonyms as $product => $categories) {
            foreach ($categories as $category => $terms) {
                if (in_array($input, $terms)) {
                    return $product;
                }
            }
        }
        return null;
    }
    
    /**
     * Find fuzzy matches using similarity algorithms
     */
    private function findFuzzyMatch($input) {
        $best_match = null;
        $best_score = 0;
        
        foreach ($this->synonyms as $product => $categories) {
            foreach ($categories as $category => $terms) {
                foreach ($terms as $term) {
                    $similarity = $this->calculateSimilarity($input, $term);
                    if ($similarity > $best_score && $similarity >= $this->fuzzy_threshold) {
                        $best_score = $similarity;
                        $best_match = [
                            'product' => $product,
                            'confidence' => $similarity,
                            'match_type' => 'fuzzy',
                            'matched_term' => $term
                        ];
                    }
                }
            }
        }
        
        return $best_match;
    }
    
    /**
     * Find partial matches (input contains product name or vice versa)
     */
    private function findPartialMatch($input) {
        foreach ($this->synonyms as $product => $categories) {
            foreach ($categories as $category => $terms) {
                foreach ($terms as $term) {
                    if (strpos($input, $term) !== false || strpos($term, $input) !== false) {
                        $confidence = strlen($term) / max(strlen($input), strlen($term));
                        if ($confidence >= 0.6) {
                            return [
                                'product' => $product,
                                'confidence' => $confidence,
                                'match_type' => 'partial',
                                'matched_term' => $term
                            ];
                        }
                    }
                }
            }
        }
        return null;
    }
    
    /**
     * Calculate similarity between two strings using multiple algorithms
     */
    private function calculateSimilarity($str1, $str2) {
        // Levenshtein distance
        $levenshtein = levenshtein($str1, $str2);
        $max_len = max(strlen($str1), strlen($str2));
        $levenshtein_similarity = $max_len > 0 ? (1 - $levenshtein / $max_len) : 1;
        
        // Similar text
        similar_text($str1, $str2, $similar_text_percent);
        $similar_text_similarity = $similar_text_percent / 100;
        
        // Soundex (for phonetic similarity)
        $soundex1 = soundex($str1);
        $soundex2 = soundex($str2);
        $soundex_similarity = $soundex1 === $soundex2 ? 0.8 : 0.2;
        
        // Combined score (weighted average)
        $combined_score = ($levenshtein_similarity * 0.4) + 
                         ($similar_text_similarity * 0.4) + 
                         ($soundex_similarity * 0.2);
        
        return $combined_score;
    }
    
    /**
     * Get all synonyms for a product
     */
    public function getSynonyms($product) {
        return isset($this->synonyms[$product]) ? $this->synonyms[$product] : [];
    }
    
    /**
     * Add new synonyms for a product
     */
    public function addSynonyms($product, $synonyms) {
        if (!isset($this->synonyms[$product])) {
            $this->synonyms[$product] = [];
        }
        $this->synonyms[$product] = array_merge_recursive($this->synonyms[$product], $synonyms);
    }
    
    /**
     * Get all available products
     */
    public function getAllProducts() {
        return array_keys($this->synonyms);
    }
}

// Usage example and testing
if (isset($_GET['test'])) {
    $matcher = new ProductMatcher();
    
    $test_inputs = [
        'irish potatos',
        'ibirayi',
        'sweet patatoes',
        'ibijumba',
        'tomatoe',
        'inyanya',
        'onion\'s',
        'itunguru',
        'maze',
        'igikeri',
        'bananna',
        'umuneke',
        'bean\'s',
        'ubwishyimbo'
    ];
    
    echo "<h2>Product Matching Test Results</h2>";
    foreach ($test_inputs as $input) {
        $result = $matcher->findProduct($input);
        if ($result) {
            echo "<p><strong>$input</strong> → <strong>{$result['product']}</strong> (Confidence: " . round($result['confidence'] * 100, 1) . "%, Type: {$result['match_type']})</p>";
        } else {
            echo "<p><strong>$input</strong> → <em>No match found</em></p>";
        }
    }
}
?>
