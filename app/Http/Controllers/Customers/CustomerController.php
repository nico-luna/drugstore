<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Domains\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $query = Customer::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('estado')
            ->orderBy('nombre')
            ->limit(200)
            ->get();

        $editing = null;
        $editId = $request->query('edit');
        if ($editId) {
            $editing = Customer::find($editId);
        }

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'editing' => $editing,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ], [
            'nombre.required' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'nombre.max' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'telefono.max' => 'El teléfono admite hasta 30 caracteres.',
            'direccion.max' => 'La dirección admite hasta 200 caracteres.',
        ]);

        Customer::create([
            'nombre' => $validated['nombre'],
            'telefono' => $validated['telefono'] ?? '',
            'direccion' => $validated['direccion'] ?? '',
            'usuario_id' => $request->user()->idusuario,
            'estado' => true,
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ], [
            'nombre.required' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'nombre.max' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'telefono.max' => 'El teléfono admite hasta 30 caracteres.',
            'direccion.max' => 'La dirección admite hasta 200 caracteres.',
        ]);

        $customer->update([
            'nombre' => $validated['nombre'],
            'telefono' => $validated['telefono'] ?? '',
            'direccion' => $validated['direccion'] ?? '',
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado.');
    }

    public function toggle(int $id): RedirectResponse
    {
        if ($id === 1) {
            return back()->with('error', 'El cliente Público en general no puede desactivarse.');
        }

        $customer = Customer::findOrFail($id);
        $customer->update(['estado' => !$customer->estado]);

        return back()->with('success', 'Estado del cliente actualizado.');
    }
}
