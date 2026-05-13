<?php

namespace App\Http\Controllers;

use App\Models\Propiedad;
use Illuminate\Http\Request;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        $query = Propiedad::where('estatus', 'en_venta');

        if ($q = $request->get('q')) {
            $query->where(function ($q2) use ($q) {
                $q2->where('titulo',    'like', "%{$q}%")
                   ->orWhere('colonia',  'like', "%{$q}%")
                   ->orWhere('municipio','like', "%{$q}%")
                   ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        if ($municipio = $request->get('municipio')) {
            $query->where('municipio', 'like', "%{$municipio}%");
        }

        if ($min = $request->get('precio_min')) {
            $query->where('precio', '>=', $min);
        }

        if ($max = $request->get('precio_max')) {
            $query->where('precio', '<=', $max);
        }

        if ($request->get('infonavit')) {
            $query->where('acepta_infonavit', true);
        }

        if ($request->get('fovissste')) {
            $query->where('acepta_fovissste', true);
        }

        $perPage     = in_array((int) $request->get('per_page'), [10, 20, 50]) ? (int) $request->get('per_page') : 12;
        $propiedades = $query->latest()->paginate($perPage)->withQueryString();
        $estados     = Propiedad::distinct()->orderBy('estado')->pluck('estado');

        return view('pages.propiedades.index', compact('propiedades', 'estados'));
    }

    public function show(string $slug)
    {
        $propiedad = Propiedad::where('slug', $slug)->firstOrFail();

        return view('pages.propiedades.show', compact('propiedad'));
    }
}
