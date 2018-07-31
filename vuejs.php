<?php

use Alfred\Workflows\Workflow;
use AlgoliaSearch\Client as Algolia;
use AlgoliaSearch\Version as AlgoliaUserAgent;

require __DIR__ . '/vendor/autoload.php';

$query = $argv[1];

$workflow = new Workflow;
$algolia = new Algolia('BH4D9OD16A', '85cc3221c9f23bfbaa4e3913dd7625ea');

AlgoliaUserAgent::addSuffixUserAgentSegment('VueJS Alfred Workflow', '0.1.1');

$index = $algolia->initIndex('vuejs');
$search = $index->search($query, ['facetFilters' => 'version:v2']);
$results = $search['hits'];

if (empty($results)) {
    $workflow->result()
        ->title('No matches')
        ->icon('google.png')
        ->subtitle("No match found in the docs. Search Google for: \"VueJS+{$query}\"")
        ->arg("https://www.google.com/search?q=vuejs+{$query}")
        ->quicklookurl("https://www.google.com/search?q=vuejs+{$query}")
        ->valid(true);

    echo $workflow->output();
    exit;
}

foreach ($results as $hit) {
    $highestLvl = $hit['hierarchy']['lvl6'] ? 6 : (
        $hit['hierarchy']['lvl5'] ? 5 : (
            $hit['hierarchy']['lvl4'] ? 4 : (
                $hit['hierarchy']['lvl3'] ? 3 : (
                    $hit['hierarchy']['lvl2'] ? 2 : (
                        $hit['hierarchy']['lvl1'] ? 1 : 0
                    )
                )
            )
        )
    );

    $title = $hit['hierarchy']['lvl' . $highestLvl];
    $currentLvl = 0;
    $subtitle = $hit['hierarchy']['lvl0'];
    while ($currentLvl < $highestLvl) {
        $currentLvl = $currentLvl + 1;
        $subtitle = $subtitle . ' » ' . $hit['hierarchy']['lvl' . $currentLvl];
    }

    $workflow->result()
        ->uid($hit['objectID'])
        ->title($title)
        ->autocomplete($title)
        ->subtitle($subtitle)
        ->arg($hit['url'])
        ->quicklookurl($hit['url'])
        ->valid(true);
}

echo $workflow->output();
