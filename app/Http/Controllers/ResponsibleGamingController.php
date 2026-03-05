<?php

namespace App\Http\Controllers;

use App\Services\ResponsibleGamingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResponsibleGamingController extends Controller
{
    public function __construct(
        private ResponsibleGamingService $service
    ) {}

    /**
     * GET /api/responsible-gaming/status
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getStatus($request->user()),
        ]);
    }

    /**
     * POST /api/responsible-gaming/self-exclude
     */
    public function selfExclude(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $this->service->selfExclude($request->user(), $validated['days']);

        return response()->json([
            'message' => "You have been self-excluded for {$validated['days']} day(s). You will not be able to place bets or deposit during this period.",
            'data'    => $this->service->getStatus($request->user()->fresh()),
        ]);
    }

    /**
     * POST /api/responsible-gaming/deposit-limits
     */
    public function setDepositLimits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'daily_limit'   => 'nullable|numeric|min:0',
            'weekly_limit'  => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
        ]);

        $this->service->setDepositLimits(
            user:    $request->user(),
            daily:   $validated['daily_limit'] ?? null,
            weekly:  $validated['weekly_limit'] ?? null,
            monthly: $validated['monthly_limit'] ?? null,
        );

        return response()->json([
            'message' => 'Deposit limits updated successfully.',
            'data'    => $this->service->getStatus($request->user()->fresh()),
        ]);
    }

    /**
     * POST /api/responsible-gaming/bet-limit
     */
    public function setBetLimit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'daily_limit' => 'nullable|numeric|min:0',
        ]);

        $this->service->setBetLimit($request->user(), $validated['daily_limit'] ?? null);

        return response()->json([
            'message' => 'Daily bet limit updated successfully.',
            'data'    => $this->service->getStatus($request->user()->fresh()),
        ]);
    }

    /**
     * POST /api/responsible-gaming/cooldown
     */
    public function setCooldown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|in:15,30,60,120,240,480,1440',
        ]);

        $this->service->setCooldown($request->user(), $validated['minutes']);

        $hours = round($validated['minutes'] / 60, 1);

        return response()->json([
            'message' => "Cool-down activated for {$hours} hour(s). You will not be able to place bets during this period.",
            'data'    => $this->service->getStatus($request->user()->fresh()),
        ]);
    }
}
