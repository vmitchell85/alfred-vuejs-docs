<?php

use Alfred\Workflows\Workflow;
use AlgoliaSearch\Client as Algolia;
use AlgoliaSearch\Version as AlgoliaUserAgent;

require __DIR__ . '/vendor/autoload.php';

$query = $argv[1];
$branch = empty($_ENV['branch']) ? 'master' : $_ENV['branch'];

$workflow = new Workflow;
$algolia = new Algolia('BH4D9OD16A', '85cc3221c9f23bfbaa4e3913dd7625ea');

AlgoliaUserAgent::addSuffixUserAgentSegment('Alfred Workflow', '0.2.1');

$index = $algolia->initIndex('vuejs');
$search = $index->search($query, ['facetFilters' => 'version:v2']);
$results = $search['hits'];

if (empty($results)) {
    $workflow->result()
        ->title('No matches')
        ->icon('google.png')
        ->subtitle("No match found in the docs. Search Google for: \"{$query}\"")
        ->arg("https://www.google.com/search?q=vuejs+{$query}")
        ->quicklookurl("https://www.google.com/search?q=vuejs+{$query}")
        ->valid(true);

    echo $workflow->output();
    exit;
}

foreach ($results as $hit) {
    $hasText = isset($hit['_highlightResult']['content']['value']);
    $hasSubtitle = isset($hit['h2']);

    $title = $hit['h1'];
    $subtitle = $hasSubtitle ? $hit['h2'] : null;

    if ($hasText) {
        $subtitle = $hit['_highlightResult']['content']['value'];

        if ($hasSubtitle) {
            $title = "{$title} » {$hit['h2']}";
        }
    }

    $title = $hit['hierarchy']['lvl6'] ?: $hit['hierarchy']['lvl5'] ?: $hit['hierarchy']['lvl4'] ?: $hit['hierarchy']['lvl2'] ?: $hit['hierarchy']['lvl1'] ?: $hit['hierarchy']['lvl0'];

    $subtitle = $hit['hierarchy']['lvl0'] . ($hit['hierarchy']['lvl1'] ? ' => ' . $hit['hierarchy']['lvl1'] : '');

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
