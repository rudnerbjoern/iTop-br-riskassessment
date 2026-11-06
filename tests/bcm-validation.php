<?php

declare(strict_types=1);

namespace Combodo\iTop\Service\Events {
    // Only the type is needed; the listener does not read event data.
    class EventData
    {
    }
}

namespace {
    class Dict
    {
        public static function Format(string $key): string
        {
            return $key;
        }
    }

    class BcmValidationObject
    {
        use BcmValidationUnderTest;

        public array $issues = [];
        private array $values;
        private array $changes;

        public function __construct(array $values, array $changes)
        {
            $this->values = $values;
            $this->changes = $changes;
        }

        public function ListChanges(): array
        {
            return $this->changes;
        }

        public function Get(string $attribute)
        {
            return $this->values[$attribute] ?? null;
        }

        public function AddCheckIssue(string $issue): void
        {
            $this->issues[] = $issue;
        }
    }

    $cases = [
        'accept MTD greater than RTO' => [60, 120, ['bcm_rto' => true], false],
        'accept MTD equal to RTO' => [60, 60, ['bcm_rto' => true], false],
        'reject RTO increased beyond MTD' => [120, 60, ['bcm_rto' => true], true],
        'reject MTD reduced below RTO' => [120, 60, ['bcm_mtd' => true], true],
        'reject inconsistent simultaneous changes' => [120, 60, ['bcm_rto' => true, 'bcm_mtd' => true], true],
        'accept numeric strings in order' => ['60', '120', ['bcm_mtd' => true], false],
        'reject numeric strings out of order' => ['120', '60', ['bcm_mtd' => true], true],
        'accept empty recovery objectives' => [null, null, ['bcm_rto' => true], false],
        'accept zero recovery objectives' => [0, 0, ['bcm_mtd' => true], false],
        'accept MTD without RTO' => [null, 60, ['bcm_mtd' => true], false],
        'reject positive RTO without MTD' => [60, null, ['bcm_rto' => true], true],
        'reject positive RTO with zero MTD' => [60, 0, ['bcm_mtd' => true], true],
        'skip validation when no attributes change' => [120, 60, [], false],
        'skip validation for unrelated changes' => [120, 60, ['rm_integrity' => true], false],
    ];

    $tests = [];
    foreach ($cases as $name => [$rto, $mtd, $changes, $expectIssue]) {
        $tests[$name] = static function () use ($rto, $mtd, $changes, $expectIssue): void {
            $object = new BcmValidationObject(['bcm_rto' => $rto, 'bcm_mtd' => $mtd], $changes);
            $object->OnFunctionalCICheckToWriteBCM(new \Combodo\iTop\Service\Events\EventData());
            $expected = $expectIssue ? ['Class:FunctionalCI/Error:MtdMustBeGreaterThanRto'] : [];
            if ($object->issues !== $expected) {
                throw new \RuntimeException('Unexpected BCM validation issues: ' . json_encode($object->issues));
            }
        };
    }

    return $tests;
}
