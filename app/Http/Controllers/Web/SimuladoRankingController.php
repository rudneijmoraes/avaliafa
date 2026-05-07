<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SimuladoRankingService;
use Illuminate\Http\Request;

class SimuladoRankingController extends Controller
{
    public function __construct(private readonly SimuladoRankingService $rankingService) {}

    public function index(Request $request)
    {
        if (! $this->rankingService->isRankingEnabled()) {
            abort(404);
        }

        $limit = (int) $request->get('limit', 50);
        $limit = max(10, min(100, $limit));

        $clientSystemId = null;
        if ($request->user() && ! $request->user()->isSuperAdmin()) {
            $clientSystemId = $request->user()->client_system_id;
        }

        $ranking = $this->rankingService->getRanking($limit, $clientSystemId);

        return view('simulados.ranking', [
            'ranking' => $ranking,
            'showCpf' => (bool) $request->get('show_cpf', true),
        ]);
    }
}
