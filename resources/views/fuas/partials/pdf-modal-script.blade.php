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
        async openBulkPdf(form) {
            this.pdfLoading = true;
            this.pdfTitle = `FUA seleccionadas (${this.selected.length})`;
            this.pdfUrl = '';
            this.showModal();

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
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
        releaseObjectUrl() {
            if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        },
    };
}
</script>
@endpush
