<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Tenancy\CurrentTenant;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $query = Product::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('estado')
            ->orderBy('descripcion')
            ->limit(200)
            ->get();

        $editing = null;
        $editId = $request->query('edit');
        if ($editId) {
            $editing = Product::find($editId);
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'editing' => $editing,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('producto', 'codigo')
                    ->where(fn ($query) => $query->where('account_id', $this->tenant->accountId())),
            ],
            'descripcion' => ['required', 'string', 'max:200'],
            'precio' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'existencia' => ['required', 'integer', 'min:0'],
            'controla_stock' => ['nullable', 'boolean'],
        ], [
            'codigo.required' => 'El código es obligatorio y admite hasta 50 caracteres.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'descripcion.required' => 'La descripción es obligatoria y admite hasta 200 caracteres.',
            'precio.required' => 'El precio no es válido.',
            'precio.numeric' => 'El precio no es válido.',
            'precio.min' => 'El precio no es válido.',
            'existencia.required' => 'La existencia debe ser un entero mayor o igual a cero.',
            'existencia.integer' => 'La existencia debe ser un entero mayor o igual a cero.',
            'existencia.min' => 'La existencia debe ser un entero mayor o igual a cero.',
        ]);

        Product::create([
            'codigo' => $validated['codigo'],
            'descripcion' => $validated['descripcion'],
            'precio' => $validated['precio'],
            'existencia' => $validated['existencia'],
            'controla_stock' => !empty($validated['controla_stock']),
            'usuario_id' => $user->idusuario,
            'estado' => true,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto creado.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('producto', 'codigo')
                    ->where(fn ($query) => $query->where('account_id', $this->tenant->accountId()))
                    ->ignore($id, 'codproducto'),
            ],
            'descripcion' => ['required', 'string', 'max:200'],
            'precio' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'existencia' => ['required', 'integer', 'min:0'],
            'controla_stock' => ['nullable', 'boolean'],
        ], [
            'codigo.required' => 'El código es obligatorio y admite hasta 50 caracteres.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'descripcion.required' => 'La descripción es obligatoria y admite hasta 200 caracteres.',
            'precio.required' => 'El precio no es válido.',
            'precio.numeric' => 'El precio no es válido.',
            'precio.min' => 'El precio no es válido.',
            'existencia.required' => 'La existencia debe ser un entero mayor o igual a cero.',
            'existencia.integer' => 'La existencia debe ser un entero mayor o igual a cero.',
            'existencia.min' => 'La existencia debe ser un entero mayor o igual a cero.',
        ]);

        $product->update([
            'codigo' => $validated['codigo'],
            'descripcion' => $validated['descripcion'],
            'precio' => $validated['precio'],
            'existencia' => $validated['existencia'],
            'controla_stock' => !empty($validated['controla_stock']),
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['estado' => !$product->estado]);

        return back()->with('success', 'Estado del producto actualizado.');
    }
}
