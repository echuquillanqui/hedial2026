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
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $this->middleware('permission:warehouse.requests.view')->only(['dashboard', 'index', 'categories', 'materials', 'stocks', 'downloadAlerts']);
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

        $query = WarehouseRequest::query()
            ->with(['fromWarehouse.sede', 'toWarehouse.sede', 'items.material.category', 'requester', 'operationalArea'])
            ->where(function ($q) use ($currentWarehouse, $principalWarehouse) {
                $q->where('from_warehouse_id', $currentWarehouse->id)
                    ->orWhere('to_warehouse_id', $currentWarehouse->id);

                if ($principalWarehouse && $currentWarehouse->is_principal) {
                    $q->orWhere('to_warehouse_id', $principalWarehouse->id);
                }
            });

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

        $requests = $query->latest()->paginate(12)->withQueryString();

        $materials = WarehouseMaterial::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $operationalAreas = OperationalArea::query()
            ->with('sede')
            ->where('sede_id', $currentSedeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $availableWarehouses = Warehouse::query()
            ->with('sede')
            ->where('id', '!=', $currentWarehouse->id)
            ->where('is_active', true)
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

    public function dashboard()
    {
        $this->ensureWarehouseSetup();
        $currentWarehouse = $this->currentWarehouseOrFail();

        $alerts = WarehouseStock::query()
            ->with(['material.category'])
            ->where('warehouse_id', $currentWarehouse->id)
            ->whereColumn('current_qty', '<=', 'min_qty')
            ->orderByRaw('CASE WHEN min_qty = 0 THEN 999999 ELSE (current_qty / min_qty) END ASC')
            ->orderBy('current_qty')
            ->get();

        $totalStocks = WarehouseStock::query()->where('warehouse_id', $currentWarehouse->id)->count();
        $totalAlerts = $alerts->count();
        $pendingRequests = WarehouseRequest::query()
            ->where('from_warehouse_id', $currentWarehouse->id)
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
            'current_qty' => 'required|numeric|min:0',
            'min_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $currentWarehouse) {
            $material = WarehouseMaterial::query()->create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'warehouse_material_category_id' => $validated['warehouse_material_category_id'],
                'is_active' => true,
            ]);

            WarehouseStock::query()->create([
                'warehouse_id' => $currentWarehouse->id,
                'warehouse_material_id' => $material->id,
                'current_qty' => $validated['current_qty'],
                'min_qty' => $validated['min_qty'],
            ]);
        });

        return back()->with('toastr', ['type' => 'success', 'message' => 'Material registrado.']);
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

        $warehouseStock->update($validated);

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

        return view('warehouse.stocks.index', compact('stocks', 'currentWarehouse', 'categories', 'availableWarehouses'));
    }


    public function store(Request $request)
    {
        $currentSedeId = CurrentSede::id();
        $fromWarehouse = Warehouse::query()->where('sede_id', $currentSedeId)->firstOrFail();

        $validated = $request->validate([
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'operational_area_id' => 'nullable|exists:operational_areas,id',
            'observations' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.warehouse_material_id' => 'required|exists:warehouse_materials,id',
            'items.*.qty_requested' => 'required|numeric|min:0.01',
        ]);

        $toWarehouse = Warehouse::query()->findOrFail($validated['to_warehouse_id']);
        abort_if($fromWarehouse->id === $toWarehouse->id, 422, 'Debe seleccionar una sede destino distinta a la sede activa.');
        if (!empty($validated['operational_area_id'])) {
            $belongsToCurrentSede = OperationalArea::query()
                ->whereKey($validated['operational_area_id'])
                ->where('sede_id', $fromWarehouse->sede_id)
                ->exists();
            abort_unless($belongsToCurrentSede, 422, 'El área operativa seleccionada no pertenece a la sede activa.');
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
        abort_unless(in_array($warehouseRequest->status, ['approved', 'partially_dispatched'], true), 422, 'Solo se puede despachar solicitudes aprobadas.');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:warehouse_request_items,id',
            'items.*.qty_sent' => 'required|numeric|min:0',
            'items.*.not_sent_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($warehouseRequest, $validated) {
            $allComplete = true;

            foreach ($validated['items'] as $line) {
                $item = $warehouseRequest->items()->with('material')->findOrFail($line['id']);
                $qtySent = (float) $line['qty_sent'];
                $qtyApproved = (float) $item->qty_approved;
                $qtyRequested = (float) $item->qty_requested;

                $status = 'pending';
                if ($qtySent <= 0) {
                    $status = 'not_sent';
                    $allComplete = false;
                } elseif ($qtySent < $qtyApproved || $qtySent < $qtyRequested) {
                    $status = 'partial';
                    $allComplete = false;
                } else {
                    $status = 'complete';
                }

                $item->update([
                    'qty_sent' => $qtySent,
                    'dispatch_status' => $status,
                    'not_sent_reason' => $line['not_sent_reason'] ?? null,
                ]);

                if ($qtySent > 0) {
                    $this->applyStockMovement(
                        $warehouseRequest->to_warehouse_id,
                        $item->warehouse_material_id,
                        'out',
                        $qtySent,
                        $warehouseRequest->id,
                        'Despacho de solicitud ' . $warehouseRequest->request_code
                    );
                }
            }

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
        abort_unless(in_array($warehouseRequest->status, ['dispatched', 'partially_dispatched', 'partially_received'], true), 422, 'Solo se puede recepcionar solicitudes despachadas.');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:warehouse_request_items,id',
            'items.*.receive_status' => 'required|in:pending,not_received,partial,complete',
            'items.*.qty_received' => 'nullable|numeric|min:0',
            'items.*.not_received_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($warehouseRequest, $validated) {
            $allReceived = true;

            foreach ($validated['items'] as $line) {
                $item = $warehouseRequest->items()->findOrFail($line['id']);
                $qtySent = (float) $item->qty_sent;
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

                $item->update([
                    'qty_received' => $qtyReceived,
                    'receive_status' => $receiveStatus,
                    'not_received_reason' => $line['not_received_reason'] ?? null,
                ]);

                if ($qtyReceived > 0) {
                    $this->applyStockMovement(
                        $warehouseRequest->from_warehouse_id,
                        $item->warehouse_material_id,
                        'in',
                        $qtyReceived,
                        $warehouseRequest->id,
                        'Recepción de solicitud ' . $warehouseRequest->request_code
                    );
                }

                if ($qtyReceived < $qtySent) {
                    $allReceived = false;
                }
                if ($receiveStatus === 'not_received' || $receiveStatus === 'pending') {
                    $allReceived = false;
                }
            }

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
        $warehouseRequest->load(['items.material.category', 'fromWarehouse.sede', 'toWarehouse.sede', 'requester']);

        $pdf = Pdf::loadView('warehouse.requests.print_request', [
            'requestModel' => $warehouseRequest,
            'statusColors' => self::STATUS_COLORS,
        ]);

        return $pdf->stream('Solicitud_' . $warehouseRequest->request_code . '.pdf');
    }

    public function printDispatch(WarehouseRequest $warehouseRequest)
    {
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

        $newQty = $type === 'out'
            ? max(0, (float) $stock->current_qty - $qty)
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
}
