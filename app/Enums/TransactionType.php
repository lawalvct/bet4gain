<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case PurchaseCoins = 'purchase_coins';
    case SellCoins = 'sell_coins';
    case Bet = 'bet';
    case Win = 'win';
    case Refund = 'refund';
    case Bonus = 'bonus';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Withdrawal => 'Withdrawal',
            self::PurchaseCoins => 'Purchase Coins',
            self::SellCoins => 'Sell Coins',
            self::Bet => 'Bet',
            self::Win => 'Win',
            self::Refund => 'Refund',
            self::Bonus => 'Bonus',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::Deposit, self::Win, self::Refund, self::Bonus, self::SellCoins]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [self::Withdrawal, self::Bet, self::PurchaseCoins]);
    }
}
