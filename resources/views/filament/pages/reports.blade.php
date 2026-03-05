<x-filament-panels::page>
    {{-- Period Selector --}}
    <div class="mb-6">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Report Period:</label>
        <select wire:model.live="period" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
            <option value="1">Today</option>
            <option value="7">Last 7 Days</option>
            <option value="30">Last 30 Days</option>
            <option value="90">Last 90 Days</option>
            <option value="365">Last Year</option>
        </select>
    </div>

    {{-- Profit & Loss Report --}}
    <x-filament::section>
        <x-slot name="heading">Profit & Loss Report</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Wagered</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $profitReport['total_wagered'] ?? '0.00' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Payouts</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $profitReport['total_payouts'] ?? '0.00' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">House Profit</p>
                <p class="text-2xl font-bold {{ ($profitReport['is_profitable'] ?? true) ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $profitReport['house_profit'] ?? '0.00' }}
                </p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actual House Edge</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $profitReport['house_edge_actual'] ?? '0.00' }}%</p>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Deposits</p>
                <p class="text-xl font-semibold text-emerald-600">{{ $profitReport['total_deposits'] ?? '0.00' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Withdrawals</p>
                <p class="text-xl font-semibold text-red-600">{{ $profitReport['total_withdrawals'] ?? '0.00' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Net Revenue</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $profitReport['net_revenue'] ?? '0.00' }}</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Player Activity --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Player Activity</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $playerActivity['total_users'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">New Users</p>
                <p class="text-2xl font-bold text-emerald-600">{{ $playerActivity['new_users'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active Users</p>
                <p class="text-2xl font-bold text-blue-600">{{ $playerActivity['active_users'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Betting Users</p>
                <p class="text-2xl font-bold text-amber-600">{{ $playerActivity['betting_users'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg Bet Amount</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $playerActivity['avg_bet_amount'] ?? '0.00' }}</p>
            </div>
        </div>

        @if(!empty($playerActivity['top_winners']))
            <div class="mt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Top Winners</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Player</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($playerActivity['top_winners'] as $index => $winner)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $winner['username'] }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-emerald-600 font-semibold">{{ $winner['profit'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Game Health --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Game Health</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Rounds</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $gameHealth['total_rounds'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg Crash Point</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $gameHealth['avg_crash_point'] ?? '0.00' }}x</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Bets</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $gameHealth['total_bets'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg Bets/Round</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $gameHealth['avg_bets_per_round'] ?? 0 }}</p>
            </div>
        </div>

        @if(!empty($gameHealth['crash_distribution']))
            <div class="mt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Crash Point Distribution</h3>
                <div class="grid grid-cols-5 gap-2">
                    @php
                        $dist = $gameHealth['crash_distribution'];
                        $total = array_sum($dist) ?: 1;
                    @endphp
                    <div class="text-center">
                        <div class="bg-red-100 dark:bg-red-900/30 rounded-lg p-3">
                            <p class="text-lg font-bold text-red-600">{{ $dist['under_1_5x'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">&lt; 1.5x</p>
                            <p class="text-xs text-gray-400">{{ number_format(($dist['under_1_5x'] ?? 0) / $total * 100, 1) }}%</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3">
                            <p class="text-lg font-bold text-orange-600">{{ $dist['1_5x_to_2x'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">1.5-2x</p>
                            <p class="text-xs text-gray-400">{{ number_format(($dist['1_5x_to_2x'] ?? 0) / $total * 100, 1) }}%</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3">
                            <p class="text-lg font-bold text-yellow-600">{{ $dist['2x_to_5x'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">2-5x</p>
                            <p class="text-xs text-gray-400">{{ number_format(($dist['2x_to_5x'] ?? 0) / $total * 100, 1) }}%</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3">
                            <p class="text-lg font-bold text-green-600">{{ $dist['5x_to_10x'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">5-10x</p>
                            <p class="text-xs text-gray-400">{{ number_format(($dist['5x_to_10x'] ?? 0) / $total * 100, 1) }}%</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3">
                            <p class="text-lg font-bold text-purple-600">{{ $dist['over_10x'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">10x+</p>
                            <p class="text-xs text-gray-400">{{ number_format(($dist['over_10x'] ?? 0) / $total * 100, 1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Payment Report --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Payment Report</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pending Withdrawals</p>
                <p class="text-2xl font-bold text-amber-600">{{ $paymentReport['pending_withdrawals'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">₦{{ $paymentReport['pending_amount'] ?? '0.00' }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Failed Transactions</p>
                <p class="text-2xl font-bold text-red-600">{{ $paymentReport['failed_transactions'] ?? 0 }}</p>
            </div>
        </div>

        @if(!empty($paymentReport['deposits_by_gateway']))
            <div class="mt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deposits by Gateway</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gateway</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Count</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($paymentReport['deposits_by_gateway'] as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $row['gateway'] }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-600 dark:text-gray-300">{{ $row['count'] }}</td>
                                    <td class="px-4 py-2 text-sm text-right font-semibold text-emerald-600">₦{{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
