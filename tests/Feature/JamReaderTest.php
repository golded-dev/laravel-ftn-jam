<?php

use Golded\Ftn\Jam\JamReader;
use Golded\Ftn\ParsedMessage;

function jamFixtureBase(): string
{
    return __DIR__.'/../Fixtures/jam/jtest1';
}

it('reads real JAM messages', function (): void {
    $messages = array_values(iterator_to_array(new JamReader()->read(jamFixtureBase())));
    $first = firstJamMessage($messages);

    expect($first->fromName)->toBe('Odinn Sorensen')
        ->and($first->toName)->not->toBeEmpty()
        ->and($first->postedAt)->not->toBeNull()
        ->and($first->externalId)->not->toStartWith('hash:');
});

it('attaches control metadata and provenance', function (): void {
    $messages = array_values(iterator_to_array(new JamReader()->read(jamFixtureBase())));
    $first = firstJamMessage($messages);

    expect($first->controlLines?->msgid)->toBe($first->externalId)
        ->and($first->provenance?->sourceType)->toBe('jam')
        ->and($first->provenance?->sourcePath)->toEndWith('/jtest1.jhr')
        ->and($first->provenance?->sourceId)->toBe((string) $first->msgno)
        ->and($first->provenance?->sourceOffset)->toBeInt();
});

/**
 * @param list<ParsedMessage> $messages
 */
function firstJamMessage(array $messages): ParsedMessage
{
    if ($messages === []) {
        throw new RuntimeException('Expected at least one parsed JAM message.');
    }

    return $messages[0];
}
