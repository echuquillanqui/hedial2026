@once
@push('scripts')
<script>
document.querySelectorAll('[data-audit-filters]').forEach((form) => {
    let timer;
    form.querySelectorAll('select, input[type="date"]').forEach((field) => field.addEventListener('change', () => form.requestSubmit()));
    form.querySelectorAll('input[type="text"]').forEach((field) => field.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => form.requestSubmit(), 450);
    }));
});
</script>
@endpush
@endonce
