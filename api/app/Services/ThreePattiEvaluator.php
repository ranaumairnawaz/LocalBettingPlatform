<?php
namespace App\Services;

class ThreePattiEvaluator
{
    /**
     * Map card strings to ranks (2-14)
     */
    private static function getCardRank(string $card)
    {
        $val = substr($card, 0, -1);
        if ($val === 'A') return 14;
        if ($val === 'K') return 13;
        if ($val === 'Q') return 12;
        if ($val === 'J') return 11;
        return (int) $val;
    }

    /**
     * Get suit from card string
     */
    private static function getCardSuit(string $card)
    {
        return substr($card, -1);
    }

    /**
     * Evaluate hand and return detailed array including type and comparison score
     */
    public static function evaluateHand(array $cards)
    {
        if (count($cards) !== 3) {
            return ['type' => 'high_card', 'score' => 0];
        }

        $ranks = array_map(fn($c) => self::getCardRank($c), $cards);
        $suits = array_map(fn($c) => self::getCardSuit($c), $cards);
        sort($ranks);

        $isFlush = count(array_unique($suits)) === 1;
        $isStraight = false;

        // Check straight
        if ($ranks[2] - $ranks[1] === 1 && $ranks[1] - $ranks[0] === 1) {
            $isStraight = true;
        } elseif ($ranks === [2, 3, 14]) {
            // A-2-3 is a valid straight sequence in Teen Patti
            $isStraight = true;
            // Treat A-2-3 sequence rank value appropriately (Ace is high/low)
            $ranks = [2, 3, 4]; // Re-map temporarily for scoring below
        }

        // 1. Trail / Trio / Set (Three of a kind)
        if ($ranks[0] === $ranks[2]) {
            return [
                'type' => 'trail',
                'name' => 'Trail (Trio)',
                'score' => 6000000 + $ranks[0]
            ];
        }

        // 2. Pure Sequence (Straight Flush)
        if ($isStraight && $isFlush) {
            return [
                'type' => 'pure_sequence',
                'name' => 'Pure Sequence',
                'score' => 5000000 + $ranks[2]
            ];
        }

        // 3. Sequence (Straight)
        if ($isStraight) {
            return [
                'type' => 'sequence',
                'name' => 'Sequence',
                'score' => 4000000 + $ranks[2]
            ];
        }

        // 4. Color (Flush)
        if ($isFlush) {
            return [
                'type' => 'color',
                'name' => 'Color (Flush)',
                'score' => 3000000 + ($ranks[2] * 100) + ($ranks[1] * 10) + $ranks[0]
            ];
        }

        // 5. Pair (Two of a kind)
        if ($ranks[0] === $ranks[1] || $ranks[1] === $ranks[2]) {
            $pairRank = $ranks[1]; // sorting ensures middle element is always part of the pair
            $kicker = ($ranks[0] === $ranks[1]) ? $ranks[2] : $ranks[0];
            return [
                'type' => 'pair',
                'name' => 'Pair',
                'score' => 2000000 + ($pairRank * 100) + $kicker
            ];
        }

        // 6. High Card
        return [
            'type' => 'high_card',
            'name' => 'High Card',
            'score' => 1000000 + ($ranks[2] * 100) + ($ranks[1] * 10) + $ranks[0]
        ];
    }
}
