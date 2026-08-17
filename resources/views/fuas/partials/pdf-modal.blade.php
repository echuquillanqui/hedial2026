<div class="modal fade" id="fuaPdfModal" tabindex="-1" aria-labelledby="fuaPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-centered">
        <div class="modal-content" style="height: 94vh">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="fuaPdfModalLabel" x-text="pdfTitle">Vista previa de FUA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 position-relative bg-secondary-subtle">
                <div x-show="pdfLoading" class="position-absolute top-50 start-50 translate-middle text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 fw-semibold">Preparando documento…</div>
                </div>
                <iframe x-ref="pdfFrame" :src="pdfUrl" class="w-100 h-100 border-0" title="Documento FUA" @load="pdfLoading = false"></iframe>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" :disabled="pdfLoading || !pdfUrl" @click="$refs.pdfFrame.contentWindow.print()">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
