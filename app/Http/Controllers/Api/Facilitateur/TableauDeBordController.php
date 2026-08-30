<?php

namespace App\Http\Controllers\Api\Facilitateur;

use App\Http\Controllers\Controller;
use App\Services\TableauDeBord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le tableau de bord du facilitateur.
 *
 * C'est le MÊME service que celui des délégations, à la cinquième portée : la
 * sienne, c'est-à-dire lui-même. Rien à filtrer à la main, rien à recalculer
 * autrement — c'est précisément la démonstration que le mécanisme de portée
 * tient sur cinq niveaux et pas seulement sur quatre.
 *
 * Il ne descend nulle part : au-dessous d'un facilitateur il n'y a plus de
 * territoire, seulement ses cohortes et ses parents, que ses propres écrans
 * montrent déjà.
 */
class TableauDeBordController extends Controller
{
    public function show(Request $request, TableauDeBord $tableau): JsonResponse
    {
        return response()->json($tableau->pour($request->user()->portee()));
    }
}
