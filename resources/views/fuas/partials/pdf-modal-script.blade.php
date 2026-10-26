@push('scripts')
<script>
function fuaPdfViewer() {
    return {
        selected: [],
        pdfUrl: '',
        pdfTitle: 'Vista previa de FUA',
        pdfLoading: false,
        objectUrl: null,
        modal: null,
        showModal() {
            this.modal ??= new bootstrap.Modal(document.getElementById('fuaPdfModal'));
            this.modal.show();
        },
        openPdf(url, title) {
            this.releaseObjectUrl();
            this.pdfLoading = true;
            this.pdfTitle = title;
            this.pdfUrl = url;
            this.showModal();
        },
        async openBulkPdf(form, title = null) {
            const formData = new FormData(form);
            this.pdfLoading = true;
            this.pdfTitle = title || `FUA seleccionadas (${this.selected.length})`;
            this.pdfUrl = '';
            this.showModal();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/pdf' },
                });
                if (!response.ok) throw new Error('No se pudo generar el documento.');
                this.releaseObjectUrl();
                this.objectUrl = URL.createObjectURL(await response.blob());
                this.pdfUrl = this.objectUrl;
            } catch (error) {
                this.modal.hide();
                alert(error.message);
            } finally {
                this.pdfLoading = false;
            }
        },
        openCorrectionBulk(scope) {
            const form = document.getElementById('correctionBulkPrint');
            const selected = form.querySelectorAll('.correction-row-check:checked').length;
            if (scope === 'selected' && !selected) {
                alert('Selecciona al menos una FUA de subsanación.');
                return;
            }

            form.querySelector('[name="scope"]').value = scope;
            const correctionModal = document.getElementById('correctionBulkModal');
            const title = scope === 'selected'
                ? `Subsanaciones seleccionadas (${selected})`
                : scope === 'current_page'
                    ? `Subsanaciones · página ${correctionModal.dataset.page}`
                    : `Subsanaciones · bloque filtrado (${correctionModal.dataset.total})`;
            bootstrap.Modal.getOrCreateInstance(correctionModal).hide();
            setTimeout(() => this.openBulkPdf(form, title), 250);
        },
        releaseObjectUrl() {
            if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        },
    };
}
</script>
@endpush
