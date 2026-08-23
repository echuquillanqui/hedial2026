<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\OperationalArea;
use App\Models\Warehouse;
use App\Models\WarehouseMaterial;
use App\Models\WarehouseMaterialCategory;
use App\Models\WarehouseRequest;
use App\Models\WarehouseRequestStatusLog;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockMovement;
use App\Models\WarehouseStockEntry;
use App\Models\WarehouseSupplier;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class WarehouseRequestController extends Controller
{
    private const STATUS_COLORS = [
        'draft' => 'secondary',
        'submitted' => 'info',
        'approved' => 'primary',
        'received_by_warehouse' => 'secondary',
        'rejected' => 'danger',
        'partially_dispatched' => 'warning',
        'dispatched' => 'success',
        'partially_received' => 'warning',
        'received' => 'success',
        'cancelled' => 'dark',
    ];
    private const STATUS_LABELS = [
        'draft' => 'Borrador',
        'submitted' => 'Enviada',
        'approved' => 'Aprobada',
        'received_by_warehouse' => 'Recibido por almacén',
        'rejected' => 'Rechazada',
        'partially_dispatched' => 'Despachada parcialmente',
        'dispatched' => 'Despachada',
        'partially_received' => 'Recepcionada parcialmente',
        'received' => 'Recepcionada',
        'cancelled' => 'Cancelada',
    ];
    private const DISPATCH_STATUS_LABELS = [
        'pending' => 'Pendiente',
        'not_sent' => 'No enviado',
        'partial' => 'Parcial',
        'complete' => 'Completo',
    ];
    private const RECEIVE_STATUS_LABELS = [
        'pending' => 'Pendiente',
        'not_received' => 'No recepcionado',
        'partial' => 'Recepción parcial',
        'complete' => 'Recepcionado completo',
    ];

    public function __construct()
    {
        $this->middleware('permission:warehouse.requests.view')->only(['dashboard', 'index', 'byArea', 'categories', 'materials', 'stocks', 'movements', 'entries', 'suppliers', 'downloadAlerts']);
        $this->middleware('permission:warehouse.configuration.manage')->only(['configuration', 'updateConfiguration']);
        $this->middleware('permission:warehouse.requests.print')->only(['printRequest', 'printDispatch']);
        $this->middleware('permission:warehouse.requests.create')->only(['store']);
        $this->middleware('permission:warehouse.requests.update.status')->only(['updateStatus']);
        $this->middleware('permission:warehouse.requests.dispatch')->only(['dispatch']);
        $this->middleware('permission:warehouse.requests.receive')->only(['receive']);
    }

    public function index(Request $request)
    {
        $currentSedeId = CurrentSede::id();
        $this->ensureWarehouseSetup();

        $currentWarehouse = Warehouse::query()->where('sede_id', $currentSedeId)->first();
        $principalWarehouse = Warehouse::query()->where('is_principal', true)->first();

        abort_unless($currentWarehouse, 403, 'No existe almacén configurado para la sede activa.');

        $query = $this->visibleRequestsQuery()
            ->with(['fromWarehouse.sede', 'toWarehouse.sede', 'items.material.category', 'requester', 'operationalArea']);

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('request_code', 'like', "%{$term}%")
                    ->orWhereHas('fromWarehouse.sede', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('toWarehouse.sede', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('operational_area_id')) {
            $query->where('operational_area_id', $request->integer('operational_area_id'));
        }

        $operationalAreaFilterOptions = (clone $query)
            ->with('operationalArea.sede')
            ->get()
            ->map(fn ($warehouseRequest) => $warehouseRequest->operationalArea)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $requests = $query->latest()->paginate(12)->withQueryString();

        $materials = WarehouseMaterial::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $operationalAreas = OperationalArea::query()
            ->with('sede')
            ->where('sede_id', $currentSedeId)
            ->when(! $this->isLogisticsUser(), fn ($query) => $query->whereHas('users', fn ($users) => $users->whereKey(Auth::id())))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $availableWarehouses = Warehouse::query()
            ->with('sede')
            ->where('is_active', true)
            ->when(! $currentWarehouse->is_principal, function ($query) {
                $query->where('is_principal', true);
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$currentWarehouse->id])
            ->orderBy('is_principal', 'desc')
            ->orderBy('name')
            ->get();

        $statusColors = self::STATUS_COLORS;
        $statusLabels = self::STATUS_LABELS;
        $dispatchStatusLabels = self::DISPATCH_STATUS_LABELS;
        $receiveStatusLabels = self::RECEIVE_STATUS_LABELS;

        return view('warehouse.requests.index', compact(
            'requests',
            'materials',
            'availableWarehouses',
            'operationalAreaFilterOptions',
            'statusColors',
            'statusLabels',
            'dispatchStatusLabels',
            'receiveStatusLabels',
            'currentWarehouse',
            'principalWarehouse'
            ,
            'operationalAreas'
        ));
    }

    public function byArea(Request $request)
    {
        $currentSedeId = CurrentSede::id();
        $this->ensureWarehouseSetup();

        $currentWarehouse = Warehouse::query()->where('sede_id', $currentSedeId)->first();
        $principalWarehouse = Warehouse::query()->where('is_principal', true)->first();

        abort_unless($currentWarehouse, 403, 'No existe almacén configurado para la sede activa.');

        $query = $this->visibleRequestsQuery()
            ->with(['fromWarehouse.sede', 'toWarehouse.sede', 'items.material.category', 'requester', 'operationalArea']);

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('request_code', 'like', "%{$term}%")
                    ->orWhereHas('operationalArea', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('requester', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('operational_area_id')) {
            $query->where('operational_area_id', $request->integer('operational_area_id'));
        }

        $operationalAreaFilterOptions = (clone $query)
            ->with('operationalArea.sede')
            ->get()
            ->map(fn ($warehouseRequest) => $warehouseRequest->operationalArea)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $requests = $query->latest()->paginate(15)->withQueryString();

        $statusColors = self::STATUS_COLORS;
        $statusLabels = self::STATUS_LABELS;
        $dispatchStatusLabels = self::DISPATCH_STATUS_LABELS;
        $receiveStatusLabels = self::RECEIVE_STATUS_LABELS;

        return view('warehouse.requests.by-area', compact(
            'requests',
            'statusColors',
            'statusLabels',
            'dispatchStatusLabels',
            'receiveStatusLabels',
            'operationalAreaFilterOptions'
        ));
    }

    public function dashboard()
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $alerts = WarehouseStock::query()
            ->with(['material.category', 'material.stockEntries' => fn ($query) => $query
                ->where('warehouse_id', $currentWarehouse->id)
                ->orderBy('expiration_date')])
            ->where('warehouse_id', $currentWarehouse->id)
            ->whereColumn('current_qty', '<=', 'min_qty')
            ->orderByRaw('CASE WHEN min_qty = 0 THEN 999999 ELSE (current_qty / min_qty) END ASC')
            ->orderBy('current_qty')
            ->get();

        $totalStocks = WarehouseStock::query()->where('warehouse_id', $currentWarehouse->id)->count();
        $totalAlerts = $alerts->count();
        $pendingRequests = WarehouseRequest::query()
            ->when(! $this->isLogisticsUser(), fn ($query) => $query->whereIn(
                'operational_area_id',
                Auth::user()->operationalAreas()->pluck('operational_areas.id')
            ))
            ->whereIn('status', ['submitted', 'received_by_warehouse', 'approved', 'partially_dispatched', 'dispatched', 'partially_received'])
            ->count();

        return view('warehouse.dashboard', compact(
            'currentWarehouse',
            'alerts',
            'totalStocks',
            'totalAlerts',
            'pendingRequests'
        ));
    }


    public function storeMaterial(Request $request)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_materials,code',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'warehouse_material_category_id' => 'required|exists:warehouse_material_categories,id',
            'min_qty' => 'required|numeric|min:0.01',
            'automatic_consumption' => 'nullable|boolean',
            'quantity_per_session' => 'nullable|required_if:automatic_consumption,1|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $currentWarehouse) {
            $material = WarehouseMaterial::query()->create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'warehouse_material_category_id' => $validated['warehouse_material_category_id'],
                'is_active' => true,
                'automatic_consumption' => (bool) ($validated['automatic_consumption'] ?? false),
                'quantity_per_session' => $validated['quantity_per_session'] ?? 0,
            ]);

            WarehouseStock::query()->create([
                'warehouse_id' => $currentWarehouse->id,
                'warehouse_material_id' => $material->id,
                'current_qty' => 0,
                'min_qty' => $validated['min_qty'],
            ]);
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Material registrado.']);
    }

    public function updateMaterial(Request $request, WarehouseMaterial $warehouseMaterial)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_materials,code,'.$warehouseMaterial->id,
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'warehouse_material_category_id' => 'required|exists:warehouse_material_categories,id',
            'min_qty' => 'required|numeric|min:0.01',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated, $warehouseMaterial, $currentWarehouse) {
            $warehouseMaterial->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'warehouse_material_category_id' => $validated['warehouse_material_category_id'],
                'is_active' => (bool) $validated['is_active'],
            ]);

            WarehouseStock::query()->updateOrCreate(
                ['warehouse_id' => $currentWarehouse->id, 'warehouse_material_id' => $warehouseMaterial->id],
                ['min_qty' => $validated['min_qty']]
            );
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Material actualizado.']);
    }

    public function entries(Request $request)
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();
        $this->ensurePrincipalWarehouseContext();

        $entries = WarehouseStockEntry::query()
            ->with(['material.category', 'supplier', 'receiver'])
            ->where('warehouse_id', $currentWarehouse->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->where(function ($query) use ($term) {
                    $query->whereHas('material', fn ($material) => $material->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('business_name', 'like', "%{$term}%"))
                        ->orWhere('batch_number', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $materials = WarehouseMaterial::query()->where('is_active', true)->orderBy('name')->get();
        $suppliers = WarehouseSupplier::query()->where('is_active', true)->orderBy('business_name')->get();

        return view('warehouse.entries.index', compact('entries', 'materials', 'suppliers', 'currentWarehouse'));
    }

    public function storeEntry(Request $request)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();
        $warehouse = $this->currentWarehouseOrFail();
        $validated = $request->validate([
            'warehouse_material_id' => 'required|exists:warehouse_materials,id',
            'warehouse_supplier_id' => 'required|exists:warehouse_suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'expiration_date' => 'nullable|date|after_or_equal:today',
            'batch_number' => 'nullable|string|max:100',
            'document_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $warehouse) {
            $entry = WarehouseStockEntry::query()->create($validated + [
                'warehouse_id' => $warehouse->id,
                'received_by' => Auth::id(),
            ]);
            $stock = WarehouseStock::query()->firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'warehouse_material_id' => $validated['warehouse_material_id']],
                ['current_qty' => 0, 'min_qty' => 0]
            );
            $stock = WarehouseStock::query()->lockForUpdate()->findOrFail($stock->id);
            $stock->increment('current_qty', $validated['quantity']);
            WarehouseStockMovement::query()->create([
                'warehouse_id' => $warehouse->id,
                'warehouse_material_id' => $validated['warehouse_material_id'],
                'movement_type' => 'in',
                'qty' => $validated['quantity'],
                'reference_type' => WarehouseStockEntry::class,
                'reference_id' => $entry->id,
                'performed_by' => Auth::id(),
                'notes' => 'Ingreso de proveedor '.$entry->supplier()->value('business_name').($entry->batch_number ? ' · Lote '.$entry->batch_number : ''),
            ]);
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Ingreso registrado y stock actualizado.']);
    }

    public function suppliers(Request $request)
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();
        $this->ensurePrincipalWarehouseContext();
        $suppliers = WarehouseSupplier::query()
            ->withCount('entries')
            ->when($request->filled('search'), fn ($query) => $query->where('business_name', 'like', '%'.trim((string) $request->input('search')).'%')->orWhere('tax_id', 'like', '%'.trim((string) $request->input('search')).'%'))
            ->orderBy('business_name')->paginate(20)->withQueryString();

        return view('warehouse.suppliers.index', compact('suppliers', 'currentWarehouse'));
    }

    public function storeSupplier(Request $request)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'tax_id' => 'required|string|max:20|unique:warehouse_suppliers,tax_id',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);
        WarehouseSupplier::query()->create($validated + ['is_active' => true]);

        return back()->with('toastr', ['type' => 'success', 'message' => 'Proveedor registrado.']);
    }

    public function updateStock(Request $request, WarehouseStock $warehouseStock)
    {
        $this->authorizePermission('warehouse.requests.dispatch');

        abort_unless(
            $warehouseStock->warehouse_id === $this->currentWarehouseOrFail()->id,
            403,
            'No puede actualizar stock de otro almacén.'
        );

        abort_if(
            !$this->isCurrentWarehousePrincipal(),
            403,
            'Solo la sede principal puede ajustar stock manualmente. El stock de sedes secundarias se actualiza por recepción.'
        );

        $validated = $request->validate([
            'current_qty' => 'required|numeric|min:0',
            'min_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($warehouseStock, $validated) {
            $warehouseStock = WarehouseStock::query()->lockForUpdate()->findOrFail($warehouseStock->id);
            $previous = (float) $warehouseStock->current_qty;
            $warehouseStock->update($validated);

            if ($previous !== (float) $validated['current_qty']) {
                WarehouseStockMovement::query()->create([
                    'warehouse_id' => $warehouseStock->warehouse_id,
                    'warehouse_material_id' => $warehouseStock->warehouse_material_id,
                    'movement_type' => 'adjustment',
                    'qty' => (float) $validated['current_qty'] - $previous,
                    'performed_by' => Auth::id(),
                    'notes' => 'Ajuste manual de inventario',
                ]);
            }
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Stock actualizado.']);
    }

    public function categories(Request $request)
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $query = WarehouseMaterialCategory::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$term}%");
        }

        $categories = $query->withCount('materials')->paginate(15)->withQueryString();

        return view('warehouse.categories.index', compact('categories', 'currentWarehouse'));
    }

    public function storeCategory(Request $request)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:warehouse_material_categories,name',
            'description' => 'nullable|string|max:255',
        ]);

        WarehouseMaterialCategory::query()->create($validated + ['is_active' => true]);

        return back()->with('toastr', ['type' => 'success', 'message' => 'Categoría registrada.']);
    }

    public function materials(Request $request)
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $categories = WarehouseMaterialCategory::query()->orderBy('name')->get();

        $query = WarehouseMaterial::query()
            ->with([
                'category',
                'stocks' => fn ($q) => $q->where('warehouse_id', $currentWarehouse->id),
                'stockEntries' => fn ($q) => $q->where('warehouse_id', $currentWarehouse->id)
                    ->orderByRaw('expiration_date IS NULL')
                    ->orderBy('expiration_date'),
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('warehouse_material_category_id', $request->integer('category_id'));
        }

        $materials = $query->paginate(15)->withQueryString();

        return view('warehouse.materials.index', compact('materials', 'categories', 'currentWarehouse'));
    }

    public function updateAutomaticConsumption(Request $request, WarehouseMaterial $warehouseMaterial)
    {
        $this->authorizePermission('warehouse.requests.create');
        $this->ensurePrincipalWarehouseContext();

        $validated = $request->validate([
            'automatic_consumption' => 'nullable|boolean',
            'quantity_per_session' => 'nullable|required_if:automatic_consumption,1|numeric|min:0.01',
        ]);

        $warehouseMaterial->update([
            'automatic_consumption' => (bool) ($validated['automatic_consumption'] ?? false),
            'quantity_per_session' => $validated['quantity_per_session'] ?? 0,
        ]);

        return back()->with('toastr', ['type' => 'success', 'message' => 'Consumo automático actualizado.']);
    }

    public function stocks(Request $request)
    {
        $this->ensureWarehouseSetup();

        $currentWarehouse = Warehouse::query()->where('sede_id', CurrentSede::id())->firstOrFail();
        $categories = WarehouseMaterialCategory::query()->orderBy('name')->get();
        $selectedWarehouseId = $request->integer('warehouse_id');
        $availableWarehouses = collect();

        $stocks = WarehouseStock::query()
            ->with(['material.category', 'warehouse.sede'])
            ->when($currentWarehouse->is_principal, function ($query) use ($selectedWarehouseId, &$availableWarehouses) {
                $availableWarehouses = Warehouse::query()
                    ->with('sede')
                    ->where('is_active', true)
                    ->orderByDesc('is_principal')
                    ->orderBy('name')
                    ->get();

                if ($selectedWarehouseId > 0) {
                    $query->where('warehouse_id', $selectedWarehouseId);
                }
            }, function ($query) use ($currentWarehouse) {
                $query->where('warehouse_id', $currentWarehouse->id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->whereHas('material', function ($q) use ($term) {
                    $q->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->whereHas('material', function ($q) use ($request) {
                    $q->where('warehouse_material_category_id', $request->integer('category_id'));
                });
            })
            ->orderByDesc('current_qty')
            ->paginate(20)
            ->withQueryString();

        $stockSummary = WarehouseStock::query()
            ->select('warehouse_id')
            ->selectRaw('COUNT(*) as products_count')
            ->selectRaw('SUM(CASE WHEN current_qty <= min_qty THEN 1 ELSE 0 END) as alerts_count')
            ->selectRaw('SUM(CASE WHEN current_qty < 0 THEN 1 ELSE 0 END) as negative_count')
            ->with('warehouse.sede')
            ->when(! $currentWarehouse->is_principal, fn ($query) => $query->where('warehouse_id', $currentWarehouse->id))
            ->groupBy('warehouse_id')
            ->get();

        return view('warehouse.stocks.index', compact('stocks', 'currentWarehouse', 'categories', 'availableWarehouses', 'stockSummary'));
    }

    public function movements(Request $request)
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();
        $availableWarehouses = $currentWarehouse->is_principal
            ? Warehouse::query()->with('sede')->where('is_active', true)->orderByDesc('is_principal')->get()
            : collect([$currentWarehouse->load('sede')]);
        $warehouseId = $currentWarehouse->is_principal && $request->integer('warehouse_id')
            ? $request->integer('warehouse_id')
            : $currentWarehouse->id;

        $movements = WarehouseStockMovement::query()
            ->with(['material.category', 'warehouse.sede'])
            ->where('warehouse_id', $warehouseId)
            ->when($request->filled('type'), fn ($query) => $query->where('movement_type', $request->input('type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->whereHas('material', fn ($material) => $material
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('warehouse.movements.index', compact('movements', 'currentWarehouse', 'availableWarehouses', 'warehouseId'));
    }


    public function store(Request $request)
    {
        $currentSedeId = CurrentSede::id();
        $fromWarehouse = Warehouse::query()->where('sede_id', $currentSedeId)->firstOrFail();

        $validated = $request->validate([
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'operational_area_id' => 'required|exists:operational_areas,id',
            'observations' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.warehouse_material_id' => 'required|exists:warehouse_materials,id',
            'items.*.qty_requested' => 'required|numeric|min:0.01',
        ]);

        $toWarehouse = Warehouse::query()->findOrFail($validated['to_warehouse_id']);
        abort_if(
            ! $fromWarehouse->is_principal && $fromWarehouse->id === $toWarehouse->id,
            422,
            'Las sedes secundarias deben enviar solicitudes al almacén principal.'
        );
        abort_if(
            $fromWarehouse->id !== $toWarehouse->id && ! $fromWarehouse->is_principal && ! $toWarehouse->is_principal,
            422,
            'Las sedes secundarias solo pueden enviar solicitudes al almacén principal.'
        );
        if (!empty($validated['operational_area_id'])) {
            $belongsToCurrentSede = OperationalArea::query()
                ->whereKey($validated['operational_area_id'])
                ->where('sede_id', $fromWarehouse->sede_id)
                ->exists();
            abort_unless($belongsToCurrentSede, 422, 'El área operativa seleccionada no pertenece a la sede activa.');
            $this->ensureUserCanManageArea((int) $validated['operational_area_id']);
        }

        DB::transaction(function () use ($validated, $fromWarehouse, $toWarehouse) {
            $nextId = (WarehouseRequest::max('id') ?? 0) + 1;
            $code = 'SOL-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

            $requestModel = WarehouseRequest::create([
                'request_code' => $code,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'operational_area_id' => $validated['operational_area_id'] ?? null,
                'status' => 'submitted',
                'requested_by' => Auth::id(),
                'observations' => $validated['observations'] ?? null,
            ]);

            foreach ($validated['items'] as $row) {
                $requestModel->items()->create([
                    'warehouse_material_id' => $row['warehouse_material_id'],
                    'qty_requested' => $row['qty_requested'],
                    'qty_approved' => $row['qty_requested'],
                ]);
            }

            $this->registerStatusLog($requestModel, null, 'submitted', 'Solicitud creada y enviada');
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Solicitud registrada correctamente.']);
    }

    public function updateStatus(Request $request, WarehouseRequest $warehouseRequest)
    {
        abort_unless($this->isLogisticsUser(), 403, 'Solo el personal de LOGÍSTICA puede gestionar el estado de las solicitudes.');
        $this->ensureRequestIsVisible($warehouseRequest);

        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,received_by_warehouse,approved,rejected,cancelled',
            'comment' => 'nullable|string|max:500',
        ]);

        $old = $warehouseRequest->status;

        DB::transaction(function () use ($warehouseRequest, $validated, $old) {
            $payload = ['status' => $validated['status']];

            if ($validated['status'] === 'approved') {
                $payload['approved_by'] = Auth::id();
                $payload['approved_at'] = now();
            }

            $warehouseRequest->update($payload);
            $this->registerStatusLog($warehouseRequest, $old, $validated['status'], $validated['comment'] ?? null);
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Estado actualizado.']);
    }

    public function dispatch(Request $request, WarehouseRequest $warehouseRequest)
    {
        abort_unless($this->isLogisticsUser(), 403, 'Solo el personal de LOGÍSTICA puede aprobar y despachar solicitudes.');
        abort_unless(in_array($warehouseRequest->status, ['approved', 'partially_dispatched'], true), 422, 'Solo se puede despachar solicitudes aprobadas.');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:warehouse_request_items,id',
            'items.*.qty_sent' => 'required|numeric|min:0',
            'items.*.not_sent_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($warehouseRequest, $validated) {
            foreach ($validated['items'] as $line) {
                $item = $warehouseRequest->items()->with('material')->lockForUpdate()->findOrFail($line['id']);
                $qtySent = (float) $line['qty_sent'];
                $qtyApproved = (float) $item->qty_approved;
                $qtyRequested = (float) $item->qty_requested;
                $previousQtySent = (float) $item->qty_sent;

                abort_if($qtySent < $previousQtySent, 422, 'La cantidad despachada no puede reducirse.');
                abort_if($qtySent > $qtyApproved, 422, 'La cantidad despachada no puede superar la cantidad aprobada.');

                $status = 'pending';
                if ($qtySent <= 0) {
                    $status = 'not_sent';
                } elseif ($qtySent < $qtyApproved || $qtySent < $qtyRequested) {
                    $status = 'partial';
                } else {
                    $status = 'complete';
                }

                $item->update([
                    'qty_sent' => $qtySent,
                    'dispatch_status' => $status,
                    'not_sent_reason' => $line['not_sent_reason'] ?? null,
                ]);

                $qtyToMove = $qtySent - $previousQtySent;
                if ($qtyToMove > 0) {
                    $this->applyStockMovement(
                        $warehouseRequest->to_warehouse_id,
                        $item->warehouse_material_id,
                        'out',
                        $qtyToMove,
                        $warehouseRequest->id,
                        'Despacho de solicitud ' . $warehouseRequest->request_code
                    );
                }
            }

            $allComplete = ! $warehouseRequest->items()
                ->whereColumn('qty_sent', '<', 'qty_approved')
                ->exists();
            $newStatus = $allComplete ? 'dispatched' : 'partially_dispatched';
            $old = $warehouseRequest->status;
            $warehouseRequest->update([
                'status' => $newStatus,
                'dispatched_by' => Auth::id(),
                'dispatched_at' => now(),
            ]);

            $this->registerStatusLog($warehouseRequest, $old, $newStatus, 'Despacho registrado');
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Despacho actualizado.']);
    }

    public function receive(Request $request, WarehouseRequest $warehouseRequest)
    {
        abort_unless($warehouseRequest->operational_area_id, 422, 'La solicitud no tiene un área responsable asignada.');
        $this->ensureUserCanManageArea((int) $warehouseRequest->operational_area_id, false);
        abort_unless(
            $warehouseRequest->from_warehouse_id === $this->currentWarehouseOrFail()->id,
            403,
            'Solo el almacén solicitante puede recepcionar esta solicitud.'
        );
        abort_unless(in_array($warehouseRequest->status, ['dispatched', 'partially_dispatched', 'partially_received'], true), 422, 'Solo se puede recepcionar solicitudes despachadas.');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:warehouse_request_items,id',
            'items.*.receive_status' => 'required|in:pending,not_received,partial,complete',
            'items.*.qty_received' => 'nullable|numeric|min:0',
            'items.*.not_received_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($warehouseRequest, $validated) {
            foreach ($validated['items'] as $line) {
                $item = $warehouseRequest->items()->with('material')->lockForUpdate()->findOrFail($line['id']);
                $qtySent = (float) $item->qty_sent;
                $previousQtyReceived = (float) $item->qty_received;
                $receiveStatus = $line['receive_status'];
                $rawQtyReceived = (float) ($line['qty_received'] ?? 0);

                abort_if(
                    $item->receive_status === 'complete',
                    422,
                    'El material "' . ($item->material?->name ?? 'seleccionado') . '" ya fue recepcionado completamente y no se puede editar.'
                );

                $qtyReceived = match ($receiveStatus) {
                    'complete' => $qtySent,
                    'not_received' => 0.0,
                    default => $rawQtyReceived,
                };
                $qtyReceived = min($qtySent, max(0, $qtyReceived));

                abort_if($qtyReceived < $previousQtyReceived, 422, 'La cantidad recepcionada no puede reducirse.');

                $item->update([
                    'qty_received' => $qtyReceived,
                    'receive_status' => $receiveStatus,
                    'not_received_reason' => $line['not_received_reason'] ?? null,
                ]);

                $qtyToMove = $qtyReceived - $previousQtyReceived;
                if ($qtyToMove > 0) {
                    $this->applyStockMovement(
                        $warehouseRequest->from_warehouse_id,
                        $item->warehouse_material_id,
                        'in',
                        $qtyToMove,
                        $warehouseRequest->id,
                        'Recepción de solicitud ' . $warehouseRequest->request_code
                    );
                }

            }

            $allReceived = ! $warehouseRequest->items()
                ->whereColumn('qty_received', '<', 'qty_sent')
                ->exists();
            $newStatus = $allReceived ? 'received' : 'partially_received';
            $old = $warehouseRequest->status;

            $warehouseRequest->update([
                'status' => $newStatus,
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);

            $this->registerStatusLog($warehouseRequest, $old, $newStatus, 'Recepción registrada');
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Recepción registrada.']);
    }

    public function printRequest(WarehouseRequest $warehouseRequest)
    {
        $this->ensureRequestIsVisible($warehouseRequest);
        $warehouseRequest->load(['items.material.category', 'fromWarehouse.sede', 'toWarehouse.sede', 'requester']);

        $pdf = Pdf::loadView('warehouse.requests.print_request', [
            'requestModel' => $warehouseRequest,
            'statusColors' => self::STATUS_COLORS,
        ]);

        return $pdf->stream('Solicitud_' . $warehouseRequest->request_code . '.pdf');
    }

    public function printDispatch(WarehouseRequest $warehouseRequest)
    {
        $this->ensureRequestIsVisible($warehouseRequest);
        abort_unless(in_array($warehouseRequest->status, ['approved', 'partially_dispatched', 'dispatched', 'partially_received', 'received'], true), 422, 'El pedido debe estar aprobado para imprimir despacho.');

        $warehouseRequest->load(['items.material.category', 'fromWarehouse.sede', 'toWarehouse.sede', 'requester']);

        $pdf = Pdf::loadView('warehouse.requests.print_dispatch', [
            'requestModel' => $warehouseRequest,
            'statusColors' => self::STATUS_COLORS,
        ]);

        return $pdf->stream('Despacho_' . $warehouseRequest->request_code . '.pdf');
    }

    public function downloadAlerts()
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $alerts = WarehouseStock::query()
            ->with(['material.category'])
            ->where('warehouse_id', $currentWarehouse->id)
            ->whereColumn('current_qty', '<=', 'min_qty')
            ->orderBy('current_qty')
            ->get();

        $filename = 'alertas_stock_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($alerts, $currentWarehouse) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Sede', 'Almacén', 'Código', 'Material', 'Categoría', 'Stock actual', 'Stock mínimo', 'Unidad']);
            foreach ($alerts as $alert) {
                fputcsv($handle, [
                    session('current_sede_name'),
                    $currentWarehouse->name,
                    $alert->material->code,
                    $alert->material->name,
                    $alert->material->category?->name ?? 'Sin categoría',
                    number_format((float) $alert->current_qty, 2, '.', ''),
                    number_format((float) $alert->min_qty, 2, '.', ''),
                    $alert->material->unit,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function dispatchStatusLabel(string $status): string
    {
        return self::DISPATCH_STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function receiveStatusLabel(string $status): string
    {
        return self::RECEIVE_STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public function configuration()
    {
        $this->ensureWarehouseSetup();
        $sedes = Sede::query()->with('warehouse')->where('is_active', true)->orderBy('name')->get();
        $principalWarehouse = Warehouse::query()->with('sede')->where('is_principal', true)->first();

        return view('warehouse.configuration', compact('sedes', 'principalWarehouse'));
    }

    public function updateConfiguration(Request $request)
    {
        $validated = $request->validate(['principal_sede_id' => 'required|exists:sedes,id']);
        $sede = Sede::query()->where('is_active', true)->findOrFail($validated['principal_sede_id']);

        DB::transaction(function () use ($sede) {
            Sede::query()->update(['is_principal' => false]);
            Warehouse::query()->update(['is_principal' => false]);
            $sede->update(['is_principal' => true]);
            Warehouse::query()->updateOrCreate(
                ['sede_id' => $sede->id],
                ['name' => 'Almacén '.$sede->name, 'is_principal' => true, 'is_active' => true]
            );
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Almacén principal actualizado correctamente.']);
    }


    private function authorizePermission(string $permission): void
    {
        abort_unless(Auth::user()?->can($permission), 403);
    }

    private function applyStockMovement(int $warehouseId, int $materialId, string $type, float $qty, int $requestId, string $notes): void
    {
        $stock = WarehouseStock::query()->firstOrCreate([
            'warehouse_id' => $warehouseId,
            'warehouse_material_id' => $materialId,
        ], [
            'current_qty' => 0,
            'min_qty' => 0,
        ]);
        $stock = WarehouseStock::query()->lockForUpdate()->findOrFail($stock->id);

        abort_if(
            $type === 'out' && $qty > (float) $stock->current_qty,
            422,
            'No existe stock suficiente para completar el despacho.'
        );

        $newQty = $type === 'out'
            ? (float) $stock->current_qty - $qty
            : (float) $stock->current_qty + $qty;

        $stock->update(['current_qty' => $newQty]);

        WarehouseStockMovement::query()->create([
            'warehouse_id' => $warehouseId,
            'warehouse_material_id' => $materialId,
            'movement_type' => $type,
            'qty' => $qty,
            'reference_type' => WarehouseRequest::class,
            'reference_id' => $requestId,
            'performed_by' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    private function registerStatusLog(WarehouseRequest $warehouseRequest, ?string $from, string $to, ?string $comment = null): void
    {
        WarehouseRequestStatusLog::query()->create([
            'warehouse_request_id' => $warehouseRequest->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => Auth::id(),
            'comment' => $comment,
        ]);
    }

    private function ensureWarehouseSetup(): void
    {
        $sedes = Sede::query()->orderBy('id')->get();

        foreach ($sedes as $sede) {
            Warehouse::query()->updateOrCreate(
                ['sede_id' => $sede->id],
                ['name' => 'Almacén ' . $sede->name, 'is_principal' => (bool) $sede->is_principal, 'is_active' => true]
            );
        }

        if (!Warehouse::query()->where('is_principal', true)->exists()) {
            $principalSede = Sede::query()->where('is_principal', true)->first();

            if ($principalSede) {
                Warehouse::query()->where('sede_id', $principalSede->id)->update(['is_principal' => true]);
            } else {
                $first = Warehouse::query()->orderBy('id')->first();
                if ($first) {
                    $first->update(['is_principal' => true]);
                }
            }
        }
    }

    private function currentWarehouseOrFail(): Warehouse
    {
        return Warehouse::query()->where('sede_id', CurrentSede::id())->firstOrFail();
    }

    private function isCurrentWarehousePrincipal(): bool
    {
        return (bool) $this->currentWarehouseOrFail()->is_principal;
    }

    private function ensurePrincipalWarehouseContext(): void
    {
        abort_if(
            !$this->isCurrentWarehousePrincipal(),
            403,
            'Solo la sede principal puede registrar categorías y materiales.'
        );
    }

    private function visibleRequestsQuery(): Builder
    {
        $query = WarehouseRequest::query();

        if ($this->isLogisticsUser()) {
            return $query;
        }

        $areaIds = Auth::user()->operationalAreas()->pluck('operational_areas.id');

        return $query->whereIn('operational_area_id', $areaIds);
    }

    private function isLogisticsUser(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['logistica', 'almacen', 'superadmin']);
    }

    private function ensureRequestIsVisible(WarehouseRequest $warehouseRequest): void
    {
        abort_unless($this->visibleRequestsQuery()->whereKey($warehouseRequest->id)->exists(), 403, 'No tiene acceso a la solicitud o a su área.');
    }

    private function ensureUserCanManageArea(int $areaId, bool $allowLogistics = true): void
    {
        if ($allowLogistics && $this->isLogisticsUser()) {
            return;
        }

        abort_unless(
            Auth::user()?->operationalAreas()->whereKey($areaId)->exists(),
            403,
            'Solo un usuario asignado al área solicitante puede realizar esta acción.'
        );
    }
}
